<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SystemTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\EmailTemplate::updateOrCreate(
            ['name' => 'B2B Outreach Guide (Sample)'],
            [
                'user_id' => 1,
                'subject' => 'Quick question regarding {{companyName}}',
                'body' => "Hi {{fullName}},\n\n{{hyperline}}\n\nTypically, when leaders are driving that kind of growth, they run into massive bottlenecks with [Insert the problem your SaaS solves, e.g., manual lead tracking / operational overhead].\n\nWe built a platform that automates that exact process, usually saving teams about [Insert Metric, e.g., 10 hours a week / 30% in operational costs].\n\nWould you be completely opposed to a brief chat next week to see if it’s a fit for {{companyName}}?",
                'is_system_sample' => true,
            ]
        );
    }
}
