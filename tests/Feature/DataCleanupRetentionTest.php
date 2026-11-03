<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DataCleanupRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_device_cleanup_also_revokes_its_unexpired_sanctum_token(): void
    {
        [$admin, $adminToken] = $this->createAdmin();
        $deviceId = $this->createDevice($admin, now()->subDays(40));

        $deviceToken = $admin->createToken($deviceId)->accessToken;
        $deviceToken->forceFill([
            'created_at' => now()->subDays(40),
            'last_used_at' => now()->subDays(40),
            'expires_at' => null,
        ])->save();

        $this->withToken($adminToken)
            ->postJson('/api/v1/admin/data-cleanup/execute', [
                'categories' => ['devices'],
                'reason' => 'پاک‌سازی دستگاه بلااستفاده',
                'confirmation' => 'DELETE',
            ])
            ->assertOk()
            ->assertJsonPath('data.deleted_counts.devices', 1)
            ->assertJsonPath('data.deleted_counts.device_tokens', 1);

        $this->assertDatabaseMissing('devices', ['device_id' => $deviceId]);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $admin->id,
            'name' => $deviceId,
        ]);
    }

    public function test_device_with_recently_used_token_is_not_removed_even_if_device_last_seen_is_old(): void
    {
        [$admin, $adminToken] = $this->createAdmin();
        $deviceId = $this->createDevice($admin, now()->subDays(40));

        $deviceToken = $admin->createToken($deviceId)->accessToken;
        $deviceToken->forceFill([
            'created_at' => now()->subDays(40),
            'last_used_at' => now()->subDays(2),
            'expires_at' => null,
        ])->save();

        $this->withToken($adminToken)
            ->postJson('/api/v1/admin/data-cleanup/execute', [
                'categories' => ['devices'],
                'reason' => 'بررسی حفظ دستگاه فعال',
                'confirmation' => 'DELETE',
            ])
            ->assertOk()
            ->assertJsonPath('data.deleted_counts.devices', 0)
            ->assertJsonPath('data.deleted_counts.device_tokens', 0);

        $this->assertDatabaseHas('devices', ['device_id' => $deviceId]);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $admin->id,
            'name' => $deviceId,
        ]);
    }

    public function test_personal_access_token_without_expiry_is_pruned_after_32_days_of_inactivity(): void
    {
        [$admin, $adminToken] = $this->createAdmin();
        $staleToken = $admin->createToken('legacy-inactive-token')->accessToken;
        $staleToken->forceFill([
            'created_at' => now()->subDays(40),
            'last_used_at' => null,
            'expires_at' => null,
        ])->save();

        $this->withToken($adminToken)
            ->postJson('/api/v1/admin/data-cleanup/execute', [
                'categories' => ['personal_access_tokens'],
                'reason' => 'حذف توکن بلااستفاده',
                'confirmation' => 'DELETE',
            ])
            ->assertOk()
            ->assertJsonPath('data.deleted_counts.personal_access_tokens', 1);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $staleToken->id,
        ]);
        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'cleanup-admin',
            'tokenable_id' => $admin->id,
        ]);
    }

    /**
     * @return array{0: User, 1: string}
     */
    private function createAdmin(): array
    {
        Role::query()->firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin = User::query()->create([
            'login' => 'cleanup-admin-' . Str::lower(Str::random(12)),
            'password' => 'test-password',
            'status' => 'active',
        ]);

        $admin->assignRole('admin');

        return [$admin, $admin->createToken('cleanup-admin')->plainTextToken];
    }

    private function createDevice(User $user, $lastSeenAt): string
    {
        $deviceId = (string) Str::uuid();

        DB::table('devices')->insert([
            'public_id' => (string) Str::ulid(),
            'user_id' => $user->id,
            'device_id' => $deviceId,
            'platform' => 'web',
            'status' => 'active',
            'last_seen_at' => $lastSeenAt,
            'created_at' => $lastSeenAt,
            'updated_at' => $lastSeenAt,
        ]);

        return $deviceId;
    }
}
