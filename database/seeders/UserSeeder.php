<?php

namespace Database\Seeders;

use App\Data\Xendit\Platform\CreateAccount\CreateAccountRequestData;
use App\Models\User;
use App\Services\Xendits\Platform\XenPlatformService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class UserSeeder extends Seeder
{
    public function __construct(
        protected XenPlatformService $service
    ) {}

    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        User::truncate();
        Schema::enableForeignKeyConstraints();

        // Super admin
        User::factory()->create([
            'name'  => 'Super Admin LokaBersih',
            'email' => 'super_admin@lokabersih.com',
            'role'  => 'super_admin',
        ]);

        // Admins
        $admins = collect([
            ['name' => 'Admin 1 LokaBersih', 'email' => 'admin1@lokabersih.com'],
            ['name' => 'Admin 2 LokaBersih', 'email' => 'admin2@lokabersih.com'],
            ['name' => fake()->name(),        'email' => fake()->unique()->safeEmail()],
            ['name' => fake()->name(),        'email' => fake()->unique()->safeEmail()],
            ['name' => fake()->name(),        'email' => fake()->unique()->safeEmail()],
        ])->map(fn($a) => User::factory()->create([
            'name'  => $a['name'],
            'email' => $a['email'],
            'role'  => 'admin',
        ]));

        // Create Xendit account for each admin (skip on failure)
        $admins->each(fn(User $u) => $this->ensureXenditAccount($u));

        // Petugas
        User::factory()->create([
            'name'  => 'Petugas 1 LokaBersih',
            'email' => 'petugas@lokabersih.com',
            'role'  => 'petugas',
        ]);
        User::factory(5)->create(['role' => 'petugas']);

        // Customers
        User::factory()->create([
            'email' => 'customer@lokabersih.com',
            'role'  => 'customer',
        ]);
        User::factory(10)->create(['role' => 'customer']);
    }

    /**
     * Create Xendit sub-account and persist for_user_id.
     * Silently skips on any failure.
     */
    protected function ensureXenditAccount(User $user): void
    {
        try {
            $dto  = $this->buildCreateAccountDto($user);
            $resp = $this->service->createAccount($dto);

            // Try common response shapes. Adjust if your service wraps differently.
            $forId = $resp['for_user_id']
                ?? $resp['id']
                ?? ($resp['data']['for_user_id'] ?? null)
                ?? ($resp['data']['id'] ?? null);

            if ($forId) {
                // If xendit_for_user_id is guarded, forceFill handles it.
                $user->forceFill(['xendit_for_user_id' => $forId])->save();
            }
        } catch (\Throwable $e) {
            // Log but don't blow up seeding
            logger()->warning('Xendit createAccount failed for user ' . $user->id, [
                'message' => $e->getMessage(),
            ]);
        }
    }

    protected function buildCreateAccountDto(User $user): CreateAccountRequestData
    {
        return CreateAccountRequestData::from([
            'email' => $user->email,
            // 'type'  => 'CUSTOM',
            'public_profile' => [
                'name'          => $user->name,
                'description'   => fake()->sentence(),
                'business_name' => fake()->company(),
            ],
            'configurations' => [
                'payment_settings_follow_platform' => false,
                'has_withdrawal'                   => true,
                'has_dashboard'                    => true,
            ],
        ]);
    }
}
