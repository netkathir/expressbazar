<?php

namespace App\Services;

use App\Jobs\SyncEposStockJob;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\Subcategory;
use App\Models\Tax;
use App\Models\Vendor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class ProductBulkImportService
{
    public function import(UploadedFile $file, array $options = []): array
    {
        $rows = $this->readRows($file);
        $created = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;

            try {
                $data = $this->normalizeRow($row, $options);

                DB::transaction(function () use ($data, $options) {
                    $inventory = [
                        'stock_quantity' => (int) ($data['stock_quantity'] ?? 0),
                        'low_stock_threshold' => $data['low_stock_threshold'] ?? null,
                    ];

                    unset($data['stock_quantity'], $data['low_stock_threshold']);

                    $product = Product::create($data);

                    ProductInventory::updateOrCreate(
                        ['product_id' => $product->id],
                        [
                            'inventory_mode' => $product->inventory_mode,
                            'stock_quantity' => $product->inventory_mode === 'internal' ? $inventory['stock_quantity'] : 0,
                            'unit' => $product->unit,
                            'low_stock_threshold' => $inventory['low_stock_threshold'],
                            'sync_status' => $product->inventory_mode === 'epos' ? 'managed via EPOS' : 'internal',
                            'last_synced_at' => now(),
                        ]
                    );

                    app(InventoryService::class)->notifyIfLowStock($product->inventory()->first());

                    if (($options['queue_epos_sync'] ?? false) && $product->inventory_mode === 'epos') {
                        $product->inventory?->update(['sync_status' => 'pending']);
                        SyncEposStockJob::dispatch($product->id, 0);
                    }
                });

                $created++;
            } catch (ValidationException $exception) {
                $errors[] = 'Row '.$line.': '.collect($exception->errors())->flatten()->implode(' ');
            } catch (\Throwable $exception) {
                $errors[] = 'Row '.$line.': '.$exception->getMessage();
            }
        }

        return [
            'created' => $created,
            'errors' => $errors,
        ];
    }

    private function readRows(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return $extension === 'xlsx'
            ? $this->readXlsx($file)
            : $this->readCsv($file);
    }

    private function readCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if (! $handle) {
            throw ValidationException::withMessages(['file' => 'Unable to read the uploaded CSV file.']);
        }

        $header = fgetcsv($handle);
        if (! is_array($header)) {
            fclose($handle);
            throw ValidationException::withMessages(['file' => 'The CSV file must include a header row.']);
        }

        $header = array_map(fn ($value) => $this->normalizeHeader((string) $value), $header);
        $rows = [];

        while (($line = fgetcsv($handle)) !== false) {
            if (collect($line)->every(fn ($value) => trim((string) $value) === '')) {
                continue;
            }

            $row = [];
            foreach ($header as $index => $key) {
                if ($key !== '') {
                    $row[$key] = trim((string) ($line[$index] ?? ''));
                }
            }
            $rows[] = $row;
        }

        fclose($handle);

        if (empty($rows)) {
            throw ValidationException::withMessages(['file' => 'The CSV file does not contain any product rows.']);
        }

        return $rows;
    }

    private function readXlsx(UploadedFile $file): array
    {
        $zip = new ZipArchive();
        if ($zip->open($file->getRealPath()) !== true) {
            throw ValidationException::withMessages(['file' => 'Unable to read the uploaded Excel file.']);
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            $zip->close();
            throw ValidationException::withMessages(['file' => 'The Excel file must include a Products sheet.']);
        }

        $sharedStrings = $this->readSharedStrings($zip);
        $zip->close();

        $worksheet = simplexml_load_string($sheetXml);
        if (! $worksheet) {
            throw ValidationException::withMessages(['file' => 'Unable to parse the uploaded Excel file.']);
        }

        $rawRows = [];
        foreach ($worksheet->sheetData->row as $rowNode) {
            $row = [];
            foreach ($rowNode->c as $cellNode) {
                $attributes = $cellNode->attributes();
                $cellRef = (string) ($attributes['r'] ?? '');
                $column = $this->columnNumberFromCell($cellRef);
                if ($column < 1) {
                    continue;
                }

                $row[$column - 1] = $this->xlsxCellValue($cellNode, $sharedStrings);
            }

            if (collect($row)->some(fn ($value) => trim((string) $value) !== '')) {
                ksort($row);
                $rawRows[] = $row;
            }
        }

        $header = array_map(fn ($value) => $this->normalizeHeader((string) $value), array_values(array_shift($rawRows) ?? []));
        if (empty($header)) {
            throw ValidationException::withMessages(['file' => 'The Excel file must include a header row.']);
        }

        $rows = [];
        foreach ($rawRows as $rawRow) {
            $row = [];
            foreach ($header as $index => $key) {
                if ($key !== '') {
                    $row[$key] = trim((string) ($rawRow[$index] ?? ''));
                }
            }

            if (collect($row)->some(fn ($value) => trim((string) $value) !== '')) {
                $rows[] = $row;
            }
        }

        if (empty($rows)) {
            throw ValidationException::withMessages(['file' => 'The Excel file does not contain any product rows.']);
        }

        return $rows;
    }

    private function normalizeRow(array $row, array $options): array
    {
        $row = $this->normalizeAliases($row);
        $vendor = $options['vendor'] ?? null;
        $vendorId = $vendor ? (int) $vendor->id : $this->resolveVendorId($row);
        $categoryId = $this->resolveCategoryId($row);
        $subcategoryId = $this->resolveSubcategoryId($row, $categoryId);
        $taxId = $this->resolveTaxId($row, $vendor);
        $inventoryMode = $vendor?->inventory_mode ?: ($this->normalizeEnum($row['inventory_mode'] ?? '', [
            'internal' => 'internal',
            'inhouse' => 'internal',
            'in_house' => 'internal',
            'epos' => 'epos',
        ]) ?: 'internal');
        $discountType = $this->normalizeEnum($row['discount_type'] ?? '', [
            'percentage' => 'percentage',
            'percent' => 'percentage',
            '%' => 'percentage',
            'fixed' => 'fixed',
            'amount' => 'fixed',
            'flat' => 'fixed',
        ]) ?: null;
        $discountValue = ($row['discount_value'] ?? '') !== '' ? $this->normalizeNumber($row['discount_value'] ?? null) : null;
        $price = $this->normalizeNumber($row['price'] ?? null);

        $data = [
            'product_name' => trim((string) ($row['product_name'] ?? $row['name'] ?? '')),
            'description' => $row['description'] ?? null,
            'category_id' => $categoryId,
            'subcategory_id' => $subcategoryId,
            'vendor_id' => $vendorId,
            'tax_id' => $taxId,
            'price' => $price,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'discount_start_date' => $this->normalizeDate($row['discount_start_date'] ?? null),
            'discount_end_date' => $this->normalizeDate($row['discount_end_date'] ?? null),
            'inventory_mode' => $inventoryMode,
            'unit' => ($row['unit'] ?? '') ?: null,
            'stock_quantity' => ($row['stock_quantity'] ?? '') !== '' ? $this->normalizeInteger($row['stock_quantity']) : null,
            'low_stock_threshold' => ($row['low_stock_threshold'] ?? '') !== '' ? $this->normalizeInteger($row['low_stock_threshold']) : null,
            'status' => $this->normalizeEnum($row['status'] ?? '', [
                'active' => 'active',
                'enabled' => 'active',
                'yes' => 'active',
                'inactive' => 'inactive',
                'disabled' => 'inactive',
                'no' => 'inactive',
            ]) ?: 'active',
            'created_by' => $options['created_by'] ?? null,
            'updated_by' => $options['updated_by'] ?? null,
        ];
        $uniqueProductName = Rule::unique('products', 'product_name')
            ->where('vendor_id', $vendorId);

        $validator = Validator::make($data, [
            'product_name' => [
                'required',
                'string',
                'max:255',
                $uniqueProductName,
            ],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'exists:subcategories,id'],
            'vendor_id' => ['required', 'exists:vendors,id'],
            'tax_id' => ['nullable', 'exists:taxes,id'],
            'price' => ['required', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'discount_type' => ['nullable', Rule::in(['percentage', 'fixed'])],
            'discount_value' => ['nullable', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'discount_start_date' => ['nullable', 'date'],
            'discount_end_date' => ['nullable', 'date', 'after_or_equal:discount_start_date'],
            'inventory_mode' => ['required', Rule::in(['internal', 'epos'])],
            'stock_quantity' => ['required_if:inventory_mode,internal', 'integer', 'min:0'],
            'unit' => ['nullable', Rule::in(['kg', 'nos', 'pieces'])],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $validator->after(function ($validator) use ($data) {
            if (($data['discount_type'] ?? null) === 'percentage' && (float) ($data['discount_value'] ?? 0) > 100) {
                $validator->errors()->add('discount_value', 'Percentage discount cannot exceed 100.');
            }

            if (($data['discount_type'] ?? null) === 'fixed' && (float) ($data['discount_value'] ?? 0) > (float) ($data['price'] ?? 0)) {
                $validator->errors()->add('discount_value', 'Fixed discount cannot exceed the product price.');
            }
        });

        $validated = $validator->validate();

        if (empty($validated['discount_type']) || empty($validated['discount_value'])) {
            $validated['discount_type'] = null;
            $validated['discount_value'] = null;
            $validated['discount_start_date'] = null;
            $validated['discount_end_date'] = null;
        }

        $validated['final_price'] = $this->calculateFinalPrice($validated);

        return $validated;
    }

    private function resolveVendorId(array $row): ?int
    {
        if (! empty($row['vendor_id'])) {
            return (int) $row['vendor_id'];
        }

        $query = Vendor::query()->where('status', 'active');

        if (! empty($row['vendor_email'])) {
            return $query->whereRaw('LOWER(email) = ?', [$this->lower($row['vendor_email'])])->value('id');
        }

        if (! empty($row['vendor_name'])) {
            return $query->whereRaw('LOWER(vendor_name) = ?', [$this->lower($row['vendor_name'])])->value('id');
        }

        return null;
    }

    private function resolveCategoryId(array $row): ?int
    {
        if (! empty($row['category_id'])) {
            return (int) $row['category_id'];
        }

        if (! empty($row['category_name'])) {
            return Category::query()
                ->whereRaw('LOWER(category_name) = ?', [$this->lower($row['category_name'])])
                ->value('id');
        }

        return null;
    }

    private function resolveSubcategoryId(array $row, ?int $categoryId): ?int
    {
        if (! empty($row['subcategory_id'])) {
            return (int) $row['subcategory_id'];
        }

        if (empty($row['subcategory_name'])) {
            return null;
        }

        return Subcategory::query()
            ->whereRaw('LOWER(subcategory_name) = ?', [$this->lower($row['subcategory_name'])])
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->value('id');
    }

    private function resolveTaxId(array $row, ?Vendor $vendor): ?int
    {
        if (! empty($row['tax_id'])) {
            return (int) $row['tax_id'];
        }

        if (empty($row['tax_name'])) {
            return null;
        }

        return Tax::query()
            ->whereRaw('LOWER(tax_name) = ?', [$this->lower($row['tax_name'])])
            ->when($vendor?->country_id, fn ($query) => $query->where('country_id', $vendor->country_id))
            ->where('status', 'active')
            ->value('id');
    }

    private function calculateFinalPrice(array $data): string
    {
        $price = (float) $data['price'];
        $discountType = $data['discount_type'] ?? null;
        $discountValue = (float) ($data['discount_value'] ?? 0);
        $discount = 0;

        if ($discountType === 'percentage') {
            $discount = $price * ($discountValue / 100);
        } elseif ($discountType === 'fixed') {
            $discount = $discountValue;
        }

        return number_format(max(0, $price - $discount), 2, '.', '');
    }

    private function normalizeHeader(string $header): string
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '_', $header), '_'));
    }

    private function normalizeAliases(array $row): array
    {
        $aliases = [
            'name' => 'product_name',
            'product' => 'product_name',
            'category' => 'category_name',
            'subcategory' => 'subcategory_name',
            'sub_category' => 'subcategory_name',
            'vendor' => 'vendor_name',
            'seller' => 'vendor_name',
            'tax' => 'tax_name',
            'mrp' => 'price',
            'selling_price' => 'price',
            'amount' => 'price',
            'discount' => 'discount_value',
            'discount_amount' => 'discount_value',
            'offer' => 'discount_value',
            'offer_type' => 'discount_type',
            'discount_percent' => 'discount_value',
            'stock' => 'stock_quantity',
            'quantity' => 'stock_quantity',
            'low_stock' => 'low_stock_threshold',
            'uom' => 'unit',
        ];

        foreach ($aliases as $from => $to) {
            if (! array_key_exists($to, $row) && array_key_exists($from, $row)) {
                $row[$to] = $row[$from];
            }
        }

        $row['unit'] = $this->normalizeEnum($row['unit'] ?? '', [
            'kg' => 'kg',
            'kgs' => 'kg',
            'kilogram' => 'kg',
            'kilograms' => 'kg',
            'nos' => 'nos',
            'no' => 'nos',
            'number' => 'nos',
            'numbers' => 'nos',
            'piece' => 'pieces',
            'pieces' => 'pieces',
            'pcs' => 'pieces',
            'pc' => 'pieces',
        ]) ?: null;

        if (($row['discount_type'] ?? '') === '' && str_contains((string) ($row['discount_percent'] ?? ''), '%')) {
            $row['discount_type'] = 'percentage';
        }

        return $row;
    }

    private function normalizeEnum(?string $value, array $map): ?string
    {
        $normalized = strtolower(trim((string) $value));
        $normalized = preg_replace('/\s+/', '_', $normalized);

        return $map[$normalized] ?? null;
    }

    private function normalizeNumber(mixed $value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        $raw = str_replace(',', '', $raw);
        $raw = preg_replace('/[^0-9.\-]/', '', $raw);

        if ($raw === '' || $raw === '-' || ! is_numeric($raw)) {
            return (string) $value;
        }

        return number_format((float) $raw, 2, '.', '');
    }

    private function normalizeInteger(mixed $value): ?int
    {
        $number = $this->normalizeNumber($value);

        return is_numeric($number) ? max(0, (int) round((float) $number)) : null;
    }

    private function readSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $strings = [];
        $shared = simplexml_load_string($xml);
        foreach ($shared->si ?? [] as $item) {
            $text = '';
            if (isset($item->t)) {
                $text = (string) $item->t;
            } elseif (isset($item->r)) {
                foreach ($item->r as $run) {
                    $text .= (string) $run->t;
                }
            }
            $strings[] = $text;
        }

        return $strings;
    }

    private function xlsxCellValue(\SimpleXMLElement $cellNode, array $sharedStrings): string
    {
        $attributes = $cellNode->attributes();
        $type = (string) ($attributes['t'] ?? '');

        if ($type === 's') {
            return (string) ($sharedStrings[(int) $cellNode->v] ?? '');
        }

        if ($type === 'inlineStr') {
            return (string) ($cellNode->is->t ?? '');
        }

        return (string) ($cellNode->v ?? '');
    }

    private function columnNumberFromCell(string $cell): int
    {
        preg_match('/^[A-Z]+/i', $cell, $matches);
        $letters = strtoupper($matches[0] ?? '');
        $number = 0;

        for ($index = 0; $index < strlen($letters); $index++) {
            $number = ($number * 26) + (ord($letters[$index]) - 64);
        }

        return $number;
    }

    private function normalizeDate(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return now()->setDate(1899, 12, 30)->startOfDay()->addDays((int) $value)->format('Y-m-d');
        }

        foreach (['Y-m-d', 'd-m-y', 'd-m-Y', 'm/d/Y', 'm/d/y'] as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        return $value;
    }

    private function lower(string $value): string
    {
        return function_exists('mb_strtolower') ? mb_strtolower(trim($value)) : strtolower(trim($value));
    }
}
