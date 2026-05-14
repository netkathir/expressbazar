<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Tax;
use App\Models\Vendor;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ZipArchive;

class ProductBulkTemplateService
{
    public function vendorTemplate(Vendor $vendor): string
    {
        $categories = Category::query()
            ->with(['subcategories' => fn ($query) => $query->orderBy('subcategory_name')])
            ->where('status', 'active')
            ->orderBy('category_name')
            ->get();

        $taxes = Tax::query()
            ->where('status', 'active')
            ->when($vendor->country_id, fn ($query) => $query->where('country_id', $vendor->country_id))
            ->orderBy('tax_name')
            ->pluck('tax_name')
            ->filter()
            ->values();

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

        $categoryNames = $categories->pluck('category_name')->filter()->values();
        $subcategoryNames = $categories->flatMap(fn ($category) => $category->subcategories->pluck('subcategory_name'))->unique()->values();

        $listColumns = [
            'A' => $categoryNames,
            'B' => $subcategoryNames,
            'C' => $taxes,
            'D' => collect(['percentage', 'fixed']),
            'E' => collect(['internal', 'epos']),
            'F' => collect(['kg', 'nos', 'pieces']),
            'G' => collect(['active', 'inactive']),
        ];

        $sampleCategory = $categoryNames->first() ?: 'Type exact category name';
        $sampleSubcategory = $subcategoryNames->first() ?: '';
        $sampleTax = $taxes->first() ?: '';

        $rows = [
            $headers,
            ['Sample Rice', 'Long grain rice', $sampleCategory, $sampleSubcategory, $sampleTax, '120.00', 'percentage', '5', '2026-01-01', '2026-01-31', 'internal', '25', 'kg', '5', 'active'],
        ];

        $path = tempnam(sys_get_temp_dir(), 'product_bulk_template_');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelsXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheetXml($rows, [
            'C' => 'Lists!$A$2:$A$'.max(2, $categoryNames->count() + 1),
            'D' => 'Lists!$B$2:$B$'.max(2, $subcategoryNames->count() + 1),
            'E' => 'Lists!$C$2:$C$'.max(2, $taxes->count() + 1),
            'G' => 'Lists!$D$2:$D$3',
            'K' => 'Lists!$E$2:$E$3',
            'M' => 'Lists!$F$2:$F$4',
            'O' => 'Lists!$G$2:$G$3',
        ]));
        $zip->addFromString('xl/worksheets/sheet2.xml', $this->listsSheetXml($listColumns));
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->close();

        return $path;
    }

    private function sheetXml(array $rows, array $validations): string
    {
        $sheetData = '';
        foreach ($rows as $rowIndex => $row) {
            $number = $rowIndex + 1;
            $sheetData .= '<row r="'.$number.'">';
            foreach ($row as $columnIndex => $value) {
                $cell = $this->cellName($columnIndex + 1, $number);
                $sheetData .= '<c r="'.$cell.'" t="inlineStr"><is><t>'.$this->xml((string) $value).'</t></is></c>';
            }
            $sheetData .= '</row>';
        }

        $dataValidations = '';
        foreach ($validations as $column => $formula) {
            $dataValidations .= '<dataValidation type="list" allowBlank="1" showErrorMessage="1" sqref="'.$column.'2:'.$column.'500"><formula1>'.$formula.'</formula1></dataValidation>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0"/></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="15"/>'
            .'<cols><col min="1" max="15" width="22" customWidth="1"/></cols>'
            .'<sheetData>'.$sheetData.'</sheetData>'
            .'<dataValidations count="'.count($validations).'">'.$dataValidations.'</dataValidations>'
            .'</worksheet>';
    }

    private function listsSheetXml(array $columns): string
    {
        $maxRows = collect($columns)->map(fn (Collection $values) => $values->count())->max() + 1;
        $headings = [
            'A' => 'Categories',
            'B' => 'Subcategories',
            'C' => 'Taxes',
            'D' => 'Discount Types',
            'E' => 'Inventory Modes',
            'F' => 'Units',
            'G' => 'Statuses',
        ];

        $sheetData = '';
        for ($row = 1; $row <= $maxRows; $row++) {
            $sheetData .= '<row r="'.$row.'">';
            foreach ($headings as $column => $heading) {
                $value = $row === 1 ? $heading : (string) ($columns[$column]->values()->get($row - 2) ?? '');
                $sheetData .= '<c r="'.$column.$row.'" t="inlineStr"><is><t>'.$this->xml($value).'</t></is></c>';
            }
            $sheetData .= '</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0"/></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="15"/>'
            .'<cols><col min="1" max="7" width="28" customWidth="1"/></cols>'
            .'<sheetData>'.$sheetData.'</sheetData>'
            .'</worksheet>';
    }

    private function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'
            .'<sheet name="Products" sheetId="1" r:id="rId1"/>'
            .'<sheet name="Lists" sheetId="2" r:id="rId2"/>'
            .'</sheets>'
            .'</workbook>';
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>'
            .'<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>'
            .'</styleSheet>';
    }

    private function cellName(int $column, int $row): string
    {
        $name = '';
        while ($column > 0) {
            $column--;
            $name = chr(65 + ($column % 26)).$name;
            $column = intdiv($column, 26);
        }

        return $name.$row;
    }

    private function xml(string $value): string
    {
        return htmlspecialchars(Str::limit($value, 32000, ''), ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
