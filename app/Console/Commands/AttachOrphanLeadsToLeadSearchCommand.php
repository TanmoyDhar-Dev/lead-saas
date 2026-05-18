<?php

namespace App\Console\Commands;

use App\Models\LeadSearch;
use Illuminate\Console\Command;

class AttachOrphanLeadsToLeadSearchCommand extends Command
{
    protected $signature = 'lead-search:attach-orphans
                            {leadSearch : UUID of the lead_searches row}
                            {--limit=250 : Max leads to attach per batch}
                            {--iterations=20 : Max number of batches (stops early when nothing left)}';

    protected $description = 'Attach orphan leads to a lead search in bounded batches. Use when n8n saved leads without lead_search_id.';

    public function handle(): int
    {
        $id = (string) $this->argument('leadSearch');
        $leadSearch = LeadSearch::query()->find($id);

        if (! $leadSearch) {
            $this->error('Lead search not found.');

            return self::FAILURE;
        }

        $limit = max(1, min((int) $this->option('limit'), 5000));
        $iterations = max(1, min((int) $this->option('iterations'), 100));

        $total = 0;
        for ($i = 0; $i < $iterations; $i++) {
            $n = $leadSearch->attachOrphanLeads($limit);
            if ($n === 0) {
                break;
            }
            $total += $n;
            $this->line('Batch '.($i + 1).": attached {$n} lead(s) (running total: {$total}).");
        }

        $this->info("Finished. Total leads attached in this run: {$total}.");

        return self::SUCCESS;
    }
}
