<?php

namespace App\Actions\Visit;

use App\Actions\Sample\CreateSampleAction;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateVisitWithSampleAction
{
    public function execute(array $data, User $user): Visit
    {
        return DB::transaction(function () use ($data, $user) {
            // The visit and its one sample either both commit or both roll back.
            // Reuse stable client operation IDs so a retry cannot duplicate them.
            $visit = app(CreateVisitAction::class)->execute($data, $user);

            app(CreateSampleAction::class)->execute([
                'visit_id' => $visit->public_id,
                'product_id' => $data['product_id'],
                'quantity' => (int) $data['quantity'],
                'description' => $data['sample_description'] ?? null,
                'client_operation_id' => $data['sample_client_operation_id'] ?? (
                    !empty($data['client_operation_id'])
                        ? substr($data['client_operation_id'] . '-sample', 0, 64)
                        : (string) Str::uuid()
                ),
            ], $user);

            return $visit->fresh(Visit::DEFAULT_RELATIONS);
        });
    }
}
