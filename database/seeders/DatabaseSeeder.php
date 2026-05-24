<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminEmail = env('ADMIN_EMAIL');
        $adminPassword = env('ADMIN_PASSWORD');

        if (!$adminEmail || !$adminPassword) {
            if (app()->environment('local')) {
                $adminEmail = 'admin@example.com';
                $adminPassword = 'password';
            } else {
                $this->command->error('ADMIN_EMAIL or ADMIN_PASSWORD is not set in .env');
                return;
            }
        }

        User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => env('ADMIN_NAME', 'System Admin'),
                'password' => Hash::make($adminPassword),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
    }
}
