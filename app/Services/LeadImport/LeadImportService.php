<?php

namespace App\Services\LeadImport;

use App\Models\ImportBatch;
use App\Models\ImportedLead;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class LeadImportService
{
    public function __construct(
        private readonly SpreadsheetParser $parser = new SpreadsheetParser,
    ) {}

    /**
     * @return array{batch: ImportBatch, created: int, skipped: int, errors: int, error_samples: list<array<string, mixed>>}
     */
    public function import(User $user, UploadedFile $file): array
    {
        $parsed = $this->parser->parse($file);
        $rows = $parsed['rows'];

        $storedPath = $file->store('imports/'.$user->id, 'local');

        $batch = ImportBatch::create([
            'user_id' => $user->id,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'status' => 'processing',
            'total_rows' => count($rows),
        ]);

        $created = 0;
        $skipped = 0;
        $errors = 0;
        $errorSamples = [];

        try {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // header is row 1

                try {
                    if (($row['organization_name'] ?? null) === null
                        && ($row['contact_name'] ?? null) === null) {
                        $skipped++;
                        $this->pushError($errorSamples, $rowNumber, 'Missing organization and contact name.');
                        continue;
                    }

                    if (($row['emails'] ?? []) === []) {
                        $skipped++;
                        $this->pushError($errorSamples, $rowNumber, 'No valid email address found.');
                        continue;
                    }

                    DB::transaction(function () use ($user, $batch, $file, $row, &$created) {
                        $lead = ImportedLead::create([
                            'user_id' => $user->id,
                            'import_batch_id' => $batch->id,
                            'organization_name' => $row['organization_name'],
                            'contact_name' => $row['contact_name'],
                            'address' => $row['address'],
                            'original_filename' => $file->getClientOriginalName(),
                        ]);

                        foreach ($row['emails'] as $emailIndex => $email) {
                            $this->insertEmail($lead->id, $email, $emailIndex === 0);
                        }

                        foreach ($row['phones'] as $phoneIndex => $phone) {
                            $this->insertPhone($lead->id, $phone, $phoneIndex === 0);
                        }

                        $created++;
                    });
                } catch (Throwable $e) {
                    $errors++;
                    $this->pushError($errorSamples, $rowNumber, $e->getMessage());
                }
            }

            $batch->update([
                'status' => 'completed',
                'created_count' => $created,
                'skipped_count' => $skipped,
                'error_count' => $errors,
                'error_report' => $errorSamples === [] ? null : $errorSamples,
                'completed_at' => now(),
            ]);
        } catch (Throwable $e) {
            $batch->update([
                'status' => 'failed',
                'created_count' => $created,
                'skipped_count' => $skipped,
                'error_count' => $errors + 1,
                'error_report' => array_merge($errorSamples, [[
                    'row' => null,
                    'message' => $e->getMessage(),
                ]]),
                'completed_at' => now(),
            ]);

            throw new RuntimeException('Import failed: '.$e->getMessage(), 0, $e);
        }

        return [
            'batch' => $batch->fresh(),
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors,
            'error_samples' => $errorSamples,
        ];
    }

    private function insertEmail(string $leadId, string $email, bool $isPrimary): void
    {
        DB::table('imported_lead_emails')->insert([
            'imported_lead_id' => $leadId,
            'email' => $email,
            'is_primary' => DB::raw($isPrimary ? 'true' : 'false'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertPhone(string $leadId, string $phone, bool $isPrimary): void
    {
        DB::table('imported_lead_phones')->insert([
            'imported_lead_id' => $leadId,
            'phone' => $phone,
            'is_primary' => DB::raw($isPrimary ? 'true' : 'false'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $errorSamples
     */
    private function pushError(array &$errorSamples, int $rowNumber, string $message): void
    {
        if (count($errorSamples) >= 25) {
            return;
        }

        $errorSamples[] = [
            'row' => $rowNumber,
            'message' => $message,
        ];
    }
}
