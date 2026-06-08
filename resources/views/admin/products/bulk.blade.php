@extends('layouts.admin')

@section('content')
    @php
        $routePrefix = $routePrefix ?? 'admin.products';
        $isVendorPanel = $isVendorPanel ?? false;
        $vendors = $vendors ?? collect();
        $categories = $categories ?? collect();
        $previewColumns = $isVendorPanel
            ? ['product_name', 'category_name', 'subcategory_name', 'price', 'inventory_mode', 'stock_quantity', 'unit', 'status']
            : ['product_name', 'vendor', 'category_name', 'subcategory_name', 'price', 'inventory_mode', 'stock_quantity', 'unit', 'status'];
    @endphp

    <div class="card shell-card mb-4">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">Bulk Import Products</h1>
                    <p class="text-secondary mb-0">Upload a CSV file to create multiple products at once.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route($routePrefix.'.bulk.template') }}" class="btn btn-outline-primary">
                        <i class="ti ti-download me-1"></i> Download CSV Template
                    </a>
                    <a href="{{ route($routePrefix.'.index') }}" class="btn btn-outline-secondary">Back</a>
                </div>
            </div>

            <form method="POST" action="{{ route($routePrefix.'.bulk.store') }}" enctype="multipart/form-data" class="row g-3" id="bulkProductForm">
                @csrf
                <div class="col-12 col-lg-7">
                    <label class="form-label">CSV File</label>
                    <input type="file" name="file" class="form-control" accept=".csv,text/csv" required id="bulkProductFile">
                    <div class="form-text">Maximum file size: 5 MB. Use the CSV template format. Product images are not imported in bulk.</div>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary bulk-product-submit" type="submit" disabled>Import Previewed Products</button>
                </div>
            </form>
        </div>
    </div>

    @if (session('bulk_errors'))
        <div class="card shell-card mb-4 border-warning">
            <div class="card-body p-4">
                <h2 class="h6 mb-3">Rows skipped</h2>
                <ul class="mb-0 text-secondary">
                    @foreach (session('bulk_errors') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="card shell-card mt-4 d-none" id="bulkPreviewCard">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                <div>
                    <h2 class="h5 mb-1">Products Preview</h2>
                    <p class="text-secondary mb-0" id="bulkPreviewSummary">Review products before importing.</p>
                </div>
                <span class="badge text-bg-light" id="bulkPreviewBadge">0 rows</span>
            </div>
            <div class="alert alert-warning d-none" id="bulkPreviewAlert"></div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            @foreach ($previewColumns as $column)
                                <th>{{ \Illuminate\Support\Str::headline($column) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody id="bulkPreviewRows"></tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-3">
                <button class="btn btn-primary bulk-product-submit" type="submit" form="bulkProductForm" disabled>
                    Import Previewed Products
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const input = document.getElementById('bulkProductFile');
            const submitButtons = document.querySelectorAll('.bulk-product-submit');
            const card = document.getElementById('bulkPreviewCard');
            const rowsBody = document.getElementById('bulkPreviewRows');
            const summary = document.getElementById('bulkPreviewSummary');
            const badge = document.getElementById('bulkPreviewBadge');
            const alert = document.getElementById('bulkPreviewAlert');
            const previewColumns = @json($previewColumns);

            const setSubmitState = (disabled) => {
                submitButtons.forEach((button) => {
                    button.disabled = disabled;
                });
            };

            const escapeHtml = (value) => {
                const div = document.createElement('div');
                div.textContent = String(value ?? '');
                return div.innerHTML;
            };

            const parseCsv = (text) => {
                const rows = [];
                let row = [];
                let cell = '';
                let quoted = false;

                for (let index = 0; index < text.length; index++) {
                    const char = text[index];
                    const next = text[index + 1];

                    if (char === '"' && quoted && next === '"') {
                        cell += '"';
                        index++;
                    } else if (char === '"') {
                        quoted = !quoted;
                    } else if (char === ',' && !quoted) {
                        row.push(cell.trim());
                        cell = '';
                    } else if ((char === '\n' || char === '\r') && !quoted) {
                        if (char === '\r' && next === '\n') {
                            index++;
                        }
                        row.push(cell.trim());
                        if (row.some((value) => value !== '')) {
                            rows.push(row);
                        }
                        row = [];
                        cell = '';
                    } else {
                        cell += char;
                    }
                }

                row.push(cell.trim());
                if (row.some((value) => value !== '')) {
                    rows.push(row);
                }

                return rows;
            };

            const normalizeHeader = (value) => String(value || '').toLowerCase().trim().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');

            input?.addEventListener('change', async () => {
                const file = input.files?.[0];
                rowsBody.innerHTML = '';
                alert.classList.add('d-none');
                setSubmitState(true);

                if (!file) {
                    card.classList.add('d-none');
                    return;
                }

                const text = await file.text();
                const parsed = parseCsv(text);
                const headers = (parsed.shift() || []).map(normalizeHeader);
                const records = parsed.map((values) => {
                    const record = {};
                    headers.forEach((header, index) => {
                        record[header] = values[index] || '';
                    });
                    return record;
                });

                card.classList.remove('d-none');
                badge.textContent = `${records.length} row${records.length === 1 ? '' : 's'}`;
                summary.textContent = records.length > 25
                    ? `Showing first 25 of ${records.length} products from the selected CSV.`
                    : `Showing ${records.length} product${records.length === 1 ? '' : 's'} from the selected CSV.`;

                if (records.length === 0) {
                    alert.textContent = 'No product rows were found in this CSV.';
                    alert.classList.remove('d-none');
                    return;
                }

                rowsBody.innerHTML = records.slice(0, 25).map((record) => {
                    const cells = previewColumns.map((column) => {
                        const value = column === 'vendor' ? (record.vendor || record.vendor_name) : record[column];

                        return `<td>${escapeHtml(value || '-')}</td>`;
                    }).join('');
                    return `<tr>${cells}</tr>`;
                }).join('');

                setSubmitState(false);
            });
        })();
    </script>
@endpush
