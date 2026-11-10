<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $email = config('marketplace.admin_email');
        $passwords = [config('marketplace.admin_password'), ...config('marketplace.test_passwords')];
        if (! $email || count(array_filter($passwords)) !== 4) {
            throw new \RuntimeException('Set POC_ADMIN_EMAIL and all four POC_*_PASSWORD secrets before seeding.');
        }

        foreach ([$email, 'cli@example.invalid', 'mac@example.invalid', 'windows@example.invalid'] as $index => $address) {
            if (! User::where('email', $address)->exists()) {
                $user = new User;
                $user->forceFill([
                    'name' => ['PoC Admin', 'Claude Code Tester', 'Mac Desktop Tester', 'Windows Desktop Tester'][$index],
                    'email' => $address,
                    'email_verified_at' => now(),
                    'password' => $passwords[$index],
                    'is_admin' => $index === 0,
                ])->save();
            }
        }
    }
}
