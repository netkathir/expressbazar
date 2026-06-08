<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Tax;
use App\Models\Vendor;

class ProductBulkTemplateService
{
    public function adminTemplate(): string
    {
        return $this->buildTemplate();
    }

    public function vendorTemplate(Vendor $vendor): string
    {
        return $this->buildTemplate($vendor);
    }

    private function buildTemplate(?Vendor $vendor = null): string
    {
        $categories = Category::query()
            ->with(['subcategories' => fn ($query) => $query->orderBy('subcategory_name')])
            ->where('status', 'active')
            ->orderBy('category_name')
            ->get();

        $taxes = Tax::query()
            ->where('status', 'active')
            ->when($vendor?->country_id, fn ($query) => $query->where('country_id', $vendor->country_id))
            ->orderBy('tax_name')
            ->pluck('tax_name')
            ->filter()
            ->values();

        $vendorNames = $vendor
            ? collect()
            : Vendor::query()->where('status', 'active')->orderBy('vendor_name')->pluck('vendor_name')->filter()->values();

        $headers = [
            'product_name',
            'description',
            'category_name',
            'subcategory_name',
            'tax_name',
            'price',
            'discount_type',
            'discount_value',
            'discount_start_date',
            'discount_end_date',
            'inventory_mode',
            'stock_quantity',
            'unit',
            'low_stock_threshold',
            'status',
        ];

        if (! $vendor) {
            array_splice($headers, 1, 0, 'vendor');
        }

        $categoryNames = $categories->pluck('category_name')->filter()->values();
        $subcategoryNames = $categories->flatMap(fn ($category) => $category->subcategories->pluck('subcategory_name'))->unique()->values();

        $sampleVendor = $vendorNames->first() ?: 'Type exact vendor name';
        $sampleCategory = $categoryNames->first() ?: 'Type exact category name';
        $sampleSubcategory = $subcategoryNames->first() ?: '';
        $sampleTax = $taxes->first() ?: '';

        $sampleRow = $vendor
            ? ['Sample Rice', 'Long grain rice', $sampleCategory, $sampleSubcategory, $sampleTax, '120.00', 'percentage', '5', '2026-01-01', '2026-01-31', 'internal', '25', 'kg', '5', 'active']
            : ['Sample Rice', $sampleVendor, 'Long grain rice', $sampleCategory, $sampleSubcategory, $sampleTax, '120.00', 'percentage', '5', '2026-01-01', '2026-01-31', 'internal', '25', 'kg', '5', 'active'];

        $rows = [$headers, $sampleRow];

        $path = tempnam(sys_get_temp_dir(), 'product_bulk_template_');
        $handle = fopen($path, 'w');

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);

        return $path;
    }

}
