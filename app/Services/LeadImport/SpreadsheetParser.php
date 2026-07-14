<?php

namespace App\Services\LeadImport;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class SpreadsheetParser
{
    public const MAX_ROWS = 5000;

    public const MAX_BYTES = 10 * 1024 * 1024;

    /**
     * Header aliases for the Bangladesh org contact sample format.
     *
     * @var array<string, list<string>>
     */
    private const HEADER_ALIASES = [
        'organization_name' => ['organization name', 'organization', 'company', 'company name', 'org name'],
        'contact_name' => ['md/ceo', 'md / ceo', 'ceo', 'md', 'name', 'full name', 'contact', 'contact name'],
        'emails' => ['email', 'emails', 'e-mail', 'e-mails', 'email address', 'email addresses'],
        'phones' => ['cell/phone', 'cell / phone', 'cell', 'phone', 'phones', 'mobile', 'telephone'],
        'address' => ['address', 'company address', 'location'],
    ];

    private const IGNORED_HEADERS = ['sl no', 'sl', 'serial', 'serial no', 'status', '#'];

    public function __construct(
        private readonly ContactFieldSanitizer $sanitizer = new ContactFieldSanitizer,
    ) {}

    /**
     * @return array{headers: list<string>, rows: list<array<string, mixed>>, map: array<string, int|null>}
     */
    public function parse(UploadedFile $file): array
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());

        if (! in_array($extension, ['csv', 'xlsx', 'xls'], true)) {
            throw new RuntimeException('Only CSV, XLSX, and XLS files are supported.');
        }

        if ($file->getSize() !== false && $file->getSize() > self::MAX_BYTES) {
            throw new RuntimeException('File size must be 10 MB or less.');
        }

        $rows = $extension === 'csv'
            ? $this->readCsv($file->getRealPath())
            : $this->readSpreadsheet($file->getRealPath());

        if ($rows === []) {
            throw new RuntimeException('The file is empty.');
        }

        $headers = array_map(fn ($h) => trim((string) $h), array_shift($rows));
        $map = $this->buildColumnMap($headers);

        if ($map['organization_name'] === null && $map['contact_name'] === null && $map['emails'] === null) {
            throw new RuntimeException('Could not detect required columns (Organization Name, MD/CEO, or Email).');
        }

        $dataRows = [];
        foreach ($rows as $index => $row) {
            if ($index >= self::MAX_ROWS) {
                throw new RuntimeException('Maximum of '.self::MAX_ROWS.' data rows allowed per import.');
            }

            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $dataRows[] = $this->mapRow($row, $map);
        }

        if ($dataRows === []) {
            throw new RuntimeException('No data rows found in the file.');
        }

        return [
            'headers' => $headers,
            'rows' => $dataRows,
            'map' => $map,
        ];
    }

    /**
     * @param  list<string>  $headers
     * @return array{organization_name: int|null, contact_name: int|null, emails: int|null, phones: int|null, address: int|null}
     */
    public function buildColumnMap(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $index => $header) {
            $key = strtolower(trim(preg_replace('/\s+/', ' ', $header) ?? ''));
            $normalized[$index] = $key;
        }

        $map = [
            'organization_name' => null,
            'contact_name' => null,
            'emails' => null,
            'phones' => null,
            'address' => null,
        ];

        foreach ($normalized as $index => $header) {
            if ($header === '' || in_array($header, self::IGNORED_HEADERS, true)) {
                continue;
            }

            foreach (self::HEADER_ALIASES as $field => $aliases) {
                if ($map[$field] !== null) {
                    continue;
                }

                if (in_array($header, $aliases, true)) {
                    $map[$field] = $index;
                }
            }
        }

        return $map;
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  array{organization_name: int|null, contact_name: int|null, emails: int|null, phones: int|null, address: int|null}  $map
     * @return array{organization_name: ?string, contact_name: ?string, emails: list<string>, phones: list<string>, address: ?string}
     */
    private function mapRow(array $row, array $map): array
    {
        $get = function (?int $index) use ($row): ?string {
            if ($index === null || ! array_key_exists($index, $row)) {
                return null;
            }

            $value = $row[$index];
            if ($value === null) {
                return null;
            }

            return is_scalar($value) ? (string) $value : null;
        };

        return [
            'organization_name' => $this->sanitizer->cleanText($get($map['organization_name'])),
            'contact_name' => $this->sanitizer->cleanText($get($map['contact_name'])),
            'emails' => $this->sanitizer->extractEmails($get($map['emails'])),
            'phones' => $this->sanitizer->extractPhones($get($map['phones'])),
            'address' => $this->sanitizer->cleanText($get($map['address'])),
        ];
    }

    /**
     * @return list<list<mixed>>
     */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Unable to open the uploaded file.');
        }

        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            $rows[] = $data;
        }
        fclose($handle);

        return $rows;
    }

    /**
     * @return list<list<mixed>>
     */
    private function readSpreadsheet(string $path): array
    {
        if (! class_exists(IOFactory::class)) {
            throw new RuntimeException('Excel support is not installed. Please upload a CSV file, or install phpoffice/phpspreadsheet.');
        }

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        return array_values(array_map(fn ($row) => array_values($row), $rows));
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (is_string($cell) && trim($cell) !== '') {
                return false;
            }
            if (is_numeric($cell)) {
                return false;
            }
        }

        return true;
    }
}
