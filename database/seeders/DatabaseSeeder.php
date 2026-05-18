<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\EmailBodyTemplate;
use App\Models\EmailSignatureTemplate;
use App\Models\SenderIdentity;
use Illuminate\Database\Seeder;

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

        $user = User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => env('ADMIN_NAME', 'System Admin'),
                'password' => \Illuminate\Support\Facades\Hash::make($adminPassword),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        if ($user) {
            EmailBodyTemplate::firstOrCreate([
                'user_id' => $user->id,
                'name' => 'Default Introduction',
            ], [
                'subject' => 'Quick Question regarding {{companyName}}',
                'content' => "Hi {{firstName}},\n\nI noticed you at {{companyName}} and was impressed by your recent growth.\n\nWe help companies like yours scale. Would you be open to a brief chat next week?\n\nBest,\n{{senderName}}",
                'is_default' => true,
            ]);

            EmailSignatureTemplate::firstOrCreate([
                'user_id' => $user->id,
                'name' => 'Default Signature',
            ], [
                'content' => "---\n{{senderName}}\n{{senderRole}} at {{senderCompany}}\n{{senderWebsite}}",
                'is_default' => true,
            ]);

            SenderIdentity::firstOrCreate([
                'user_id' => $user->id,
                'name' => 'Primary Identity',
            ], [
                'sender_name' => $user->name,
                'sender_role' => 'Founder',
                'sender_company' => 'Acme Corp',
                'sender_region' => 'Global',
                'sender_industry' => 'Technology',
                'sender_linkedin' => 'https://linkedin.com/in/example',
                'sender_website' => 'https://example.com',
                'is_default' => true,
                'status' => 'active',
            ]);
        }
    }
}
