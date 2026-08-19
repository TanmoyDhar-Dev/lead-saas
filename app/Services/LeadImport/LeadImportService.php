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
    /**
     * Human-readable labels for mapped import columns.
     *
     * @var array<string, string>
     */
    private const COLUMN_LABELS = [
        'organization_name' => 'Organization Name',
        'contact_name' => 'Contact Name (MD/CEO)',
        'salutation' => 'Salutation',
        'emails' => 'Email',
        'phones' => 'Phone',
        'address' => 'Address',
    ];

    private const MAX_MISSING_ISSUES = 250;

    public function __construct(
        private readonly SpreadsheetParser $parser = new SpreadsheetParser,
    ) {}

    /**
     * Detect missing data grouped by row, plus valid/skip counts.
     *
     * @return array{issues: list<array{row: int, columns: list<string>}>, total_rows: int, valid_count: int, skip_count: int, truncated: bool}
     */
    public function detectMissingDataIssues(UploadedFile $file): array
    {
        $parsed = $this->parser->parse($file);
        $rows = $parsed['rows'];
        $map = $parsed['map'];
        $headers = $parsed['headers'];
        $issues = [];
        $skipCount = 0;

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $missingColumns = [];

            foreach (self::COLUMN_LABELS as $field => $label) {
                $columnIndex = $map[$field];
                if ($columnIndex === null) {
                    continue;
                }

                if ($this->rowValueIsEmpty($row, $field)) {
                    $headerName = isset($headers[$columnIndex]) ? trim((string) $headers[$columnIndex]) : '';
                    $missingColumns[] = $headerName !== '' ? $headerName : $label;
                }
            }

            if ($missingColumns !== []) {
                $issues[] = [
                    'row' => $rowNumber,
                    'columns' => $missingColumns,
                ];
            }

            $willSkip = ($row['contact_name'] ?? null) === null
                || ($row['emails'] ?? []) === [];
            if ($willSkip) {
                $skipCount++;
            }
        }

        $totalRows = count($rows);
        $issueRows = count($issues);
        $truncated = $issueRows > self::MAX_MISSING_ISSUES;

        if ($truncated) {
            $issues = array_slice($issues, 0, self::MAX_MISSING_ISSUES);
        }

        return [
            'issues' => $issues,
            'issue_rows' => $issueRows,
            'total_rows' => $totalRows,
            'valid_count' => $totalRows - $skipCount,
            'skip_count' => $skipCount,
            'truncated' => $truncated,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function rowValueIsEmpty(array $row, string $field): bool
    {
        if ($field === 'emails' || $field === 'phones') {
            return ($row[$field] ?? []) === [];
        }

        $value = $row[$field] ?? null;

        return $value === null || trim((string) $value) === '';
    }

    /**
     * @param  list<string>  $categoryIds
     * @return array{batch: ImportBatch, created: int, skipped: int, errors: int, error_samples: list<array<string, mixed>>}
     */
    public function import(User $user, UploadedFile $file, array $categoryIds = []): array
    {
        $parsed = $this->parser->parse($file);
        $rows = $parsed['rows'];
        $categoryIds = array_values(array_unique(array_filter($categoryIds)));

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
        $createdLeadIds = [];

        try {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // header is row 1

                try {
                    // Organization is optional during import (previously required per row).
                    // if (($row['organization_name'] ?? null) === null) {
                    //     $skipped++;
                    //     $this->pushError($errorSamples, $rowNumber, 'Missing organization name.');
                    //     continue;
                    // }

                    if (($row['contact_name'] ?? null) === null) {
                        $skipped++;
                        $this->pushError($errorSamples, $rowNumber, 'Missing contact name (MD/CEO).');
                        continue;
                    }

                    if (($row['emails'] ?? []) === []) {
                        $skipped++;
                        $invalid = $row['invalid_emails'] ?? [];
                        $detail = $invalid === []
                            ? 'No valid email address found.'
                            : 'No valid email address found. Invalid: '.implode(', ', array_slice($invalid, 0, 5));
                        $this->pushError($errorSamples, $rowNumber, $detail);
                        continue;
                    }

                    if (($row['invalid_emails'] ?? []) !== []) {
                        $invalid = array_slice($row['invalid_emails'], 0, 5);
                        $this->pushError(
                            $errorSamples,
                            $rowNumber,
                            'Imported with valid email(s); ignored invalid: '.implode(', ', $invalid)
                        );
                    }

                    DB::transaction(function () use ($user, $batch, $file, $row, &$created, &$createdLeadIds) {
                        $lead = ImportedLead::create([
                            'user_id' => $user->id,
                            'import_batch_id' => $batch->id,
                            'organization_name' => $row['organization_name'] ?? null,
                            'contact_name' => $row['contact_name'],
                            'salutation' => $row['salutation'] ?? null,
                            'address' => $row['address'],
                            'original_filename' => $file->getClientOriginalName(),
                        ]);

                        foreach ($row['emails'] as $emailIndex => $email) {
                            $this->insertEmail($lead->id, $email, $emailIndex === 0);
                        }

                        foreach ($row['phones'] as $phoneIndex => $phone) {
                            $this->insertPhone($lead->id, $phone, $phoneIndex === 0);
                        }

                        $createdLeadIds[] = $lead->id;
                        $created++;
                    });
                } catch (Throwable $e) {
                    $errors++;
                    $this->pushError($errorSamples, $rowNumber, $e->getMessage());
                }
            }

            $this->attachCategoriesBulk($createdLeadIds, $categoryIds);

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

    /**
     * Bulk-insert pivot rows for performance on large imports.
     *
     * @param  list<string>  $leadIds
     * @param  list<string>  $categoryIds
     */
    private function attachCategoriesBulk(array $leadIds, array $categoryIds): void
    {
        if ($leadIds === [] || $categoryIds === []) {
            return;
        }

        $rows = [];
        foreach ($leadIds as $leadId) {
            foreach ($categoryIds as $categoryId) {
                $rows[] = [
                    'lead_category_id' => $categoryId,
                    'imported_lead_id' => $leadId,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('category_imported_lead')->insertOrIgnore($chunk);
        }
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
