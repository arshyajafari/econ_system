<?php

namespace App\Actions\ScientificVisitorInventory;

use App\Models\ScientificVisitorInventory;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ListScientificVisitorInventoryTransfersAction
{
    public function execute(array $filters, User $user): LengthAwarePaginator
    {
        $isManager = $user->hasAnyRole(['admin', 'accountant']);
        $employeeId = $isManager ? null : $user->employee?->id;

        $ledgers = ScientificVisitorInventory::query()
            ->with(ScientificVisitorInventory::DEFAULT_RELATIONS)
            ->when($employeeId !== null, fn ($q) => $q->where('employee_id', $employeeId))
            ->when(!$isManager && $employeeId === null, fn ($q) => $q->whereRaw('1 = 0'))
            ->when(!empty($filters['employee_id']), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('public_id', $filters['employee_id'])))
            ->when(!empty($filters['product_id']), fn ($q) => $q->whereHas('product', fn ($p) => $p->where('public_id', $filters['product_id'])))
            ->get();

        if ($ledgers->isEmpty()) {
            return new LengthAwarePaginator([], 0, max(1, (int) ($filters['per_page'] ?? 20)), max(1, (int) ($filters['page'] ?? 1)));
        }

        $ledgerIds = $ledgers->pluck('id')->all();
        $transfers = DB::table('scientific_visitor_inventory_transfers as t')
            ->join('inventory_batches as b', 'b.id', '=', 't.inventory_batch_id')
            ->join('products as p', 'p.id', '=', 'b.product_id')
            ->whereIn('t.scientific_visitor_inventory_id', $ledgerIds)
            ->select([
                't.id as transfer_id', 't.scientific_visitor_inventory_id as ledger_id',
                't.inventory_batch_id', 't.quantity', 't.used_quantity', 't.returned_quantity',
                't.created_at', 't.updated_at', 'b.batch_number',
                'p.public_id as product_public_id', 'p.code as product_code', 'p.title as product_title',
            ])->orderByDesc('t.created_at')->orderByDesc('t.id')->get();

        $ledgerById = $ledgers->keyBy('id');
        $rows = collect();
        $trackedByLedger = [];

        foreach ($transfers as $transfer) {
            $ledger = $ledgerById->get($transfer->ledger_id);
            if (!$ledger) continue;

            $active = max(0, (int) $transfer->quantity - (int) $transfer->returned_quantity);
            $used = (int) $transfer->used_quantity;
            $available = max(0, $active - $used);
            $trackedByLedger[$ledger->id]['received'] = ($trackedByLedger[$ledger->id]['received'] ?? 0) + $active;
            $trackedByLedger[$ledger->id]['used'] = ($trackedByLedger[$ledger->id]['used'] ?? 0) + $used;

            $createdAt = $transfer->created_at;
            if ($available === 0 && $createdAt && now()->parse($createdAt)->lte(now()->subDays(32))) continue;

            $rows->push([
                'id' => 'transfer-'.$transfer->transfer_id,
                'transfer_id' => (int) $transfer->transfer_id,
                'ledger_id' => $ledger->public_id,
                'is_legacy' => false,
                'employee' => $ledger->employee ? ['id' => $ledger->employee->public_id, 'code' => $ledger->employee->code, 'name' => $ledger->employee->full_name] : null,
                'product' => ['id' => $transfer->product_public_id, 'code' => $transfer->product_code, 'title' => $transfer->product_title, 'sale_price' => null],
                'received_quantity' => $active,
                'used_quantity' => $used,
                'available_quantity' => $available,
                'last_received_at' => $createdAt,
                'description' => $ledger->description,
                'batch_number' => $transfer->batch_number,
            ]);
        }

        foreach ($ledgers as $ledger) {
            $trackedReceived = (int) ($trackedByLedger[$ledger->id]['received'] ?? 0);
            $trackedUsed = (int) ($trackedByLedger[$ledger->id]['used'] ?? 0);
            $legacyReceived = max(0, (int) $ledger->received_quantity - $trackedReceived);
            $legacyUsed = max(0, (int) $ledger->used_quantity - $trackedUsed);
            if ($legacyReceived === 0 && $legacyUsed === 0) continue;

            $available = max(0, $legacyReceived - $legacyUsed);
            if ($available === 0 && $ledger->last_received_at && $ledger->last_received_at->lte(now()->subDays(32))) continue;

            $rows->push([
                'id' => 'legacy-'.$ledger->public_id,
                'transfer_id' => null,
                'ledger_id' => $ledger->public_id,
                'is_legacy' => true,
                'employee' => $ledger->employee ? ['id' => $ledger->employee->public_id, 'code' => $ledger->employee->code, 'name' => $ledger->employee->full_name] : null,
                'product' => $ledger->product ? ['id' => $ledger->product->public_id, 'code' => $ledger->product->code, 'title' => $ledger->product->title, 'sale_price' => null] : null,
                'received_quantity' => $legacyReceived,
                'used_quantity' => $legacyUsed,
                'available_quantity' => $available,
                // Legacy ledger retains the original registration date even when
                // batch-level transfer tracking was introduced later.
                'last_received_at' => $ledger->last_received_at?->toISOString(),
                'description' => 'موجودی تاریخی؛ تاریخ و بچ تحویل در سیستم قدیمی ثبت نشده است.',
                'batch_number' => null,
            ]);
        }

        if (!empty($filters['search'])) {
            $needle = mb_strtolower(trim((string) $filters['search']));
            $rows = $rows->filter(fn ($row) =>
                str_contains(mb_strtolower((string) ($row['product']['title'] ?? '')), $needle)
                || str_contains(mb_strtolower((string) ($row['employee']['name'] ?? '')), $needle)
                || str_contains(mb_strtolower((string) ($row['description'] ?? '')), $needle)
            );
        }
        if (filter_var($filters['available_only'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $rows = $rows->filter(fn ($row) => $row['available_quantity'] > 0);
        }

        $sort = ltrim((string) ($filters['sort'] ?? '-last_received_at'), '-');
        $descending = str_starts_with((string) ($filters['sort'] ?? '-last_received_at'), '-');
        if ($sort === 'received_quantity') $rows = $descending ? $rows->sortByDesc('received_quantity') : $rows->sortBy('received_quantity');
        elseif ($sort === 'used_quantity') $rows = $descending ? $rows->sortByDesc('used_quantity') : $rows->sortBy('used_quantity');
        else $rows = $descending ? $rows->sortByDesc(fn ($row) => $row['last_received_at'] ?? '') : $rows->sortBy(fn ($row) => $row['last_received_at'] ?? '');

        $rows = $rows->values();
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($filters['per_page'] ?? 20)));
        return new LengthAwarePaginator(
            $rows->slice(($page - 1) * $perPage, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => request()->query()]
        );
    }
}
