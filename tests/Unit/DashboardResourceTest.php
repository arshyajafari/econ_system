<?php

namespace Tests\Unit;

use App\Http\Resources\DashboardResource;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class DashboardResourceTest extends TestCase
{
    public function test_it_exposes_the_complete_dashboard_contract(): void
    {
        $resource = new DashboardResource([
            'sales' => ['today' => 100, 'month' => 200, 'year' => 300],
            'orders' => ['today' => 4, 'month' => 10],
            'payments' => ['today' => 50, 'month' => 150],
            'receivables' => ['total' => 500],
            'returns' => ['pending' => 1, 'confirmed' => 2],
            'deliveries' => ['pending' => 3, 'shipped' => 4],
            'visits' => ['today' => 5, 'month' => 20],
            'samples' => ['today' => 6, 'month' => 30],
            'inventory' => [
                'batches' => 7,
                'quantity' => 80,
                'reserved_quantity' => 10,
                'available_quantity' => 70,
                'expired_batches' => 2,
                'near_expire_batches' => 3,
            ],
            'recent' => [
                'orders' => [['id' => 'order-1']],
                'payments' => [['id' => 'payment-1']],
                'returns' => [['id' => 'return-1']],
                'visits' => [['id' => 'visit-1']],
            ],
        ]);

        $data = $resource->toArray(Request::create('/dashboard', 'GET'));

        $this->assertSame(100, $data['sales']['today']);
        $this->assertSame(10, $data['orders']['month']);
        $this->assertSame(500, $data['receivables']['total']);
        $this->assertSame([
            'batches' => 7,
            'quantity' => 80,
            'reserved_quantity' => 10,
            'available_quantity' => 70,
            'expired_batches' => 2,
            'near_expire_batches' => 3,
        ], $data['inventory']);
        $this->assertCount(1, $data['recent']['orders']);
        $this->assertCount(1, $data['recent']['payments']);
        $this->assertCount(1, $data['recent']['returns']);
        $this->assertCount(1, $data['recent']['visits']);
    }

    public function test_missing_dashboard_sections_use_stable_defaults(): void
    {
        $resource = new DashboardResource([]);

        $data = $resource->toArray(Request::create('/dashboard', 'GET'));

        $this->assertSame(0, $data['sales']['today']);
        $this->assertSame(0, $data['inventory']['quantity']);
        $this->assertSame(0, $data['inventory']['available_quantity']);
        $this->assertSame([], $data['recent']['orders']);
    }
}
