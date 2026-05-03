@extends('layouts.admin')

@section('content')
    @php
        $routePrefix = $routePrefix ?? 'admin.products';
        $isVendorPanel = $isVendorPanel ?? false;
    @endphp
    <div class="card shell-card">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">{{ $mode === 'create' ? 'Add Product' : 'Edit Product' }}</h1>
                </div>
                <a href="{{ route($routePrefix.'.index') }}" class="btn btn-outline-secondary">Back</a>
            </div>

            <form method="POST" action="{{ $mode === 'create' ? route($routePrefix.'.store') : route($routePrefix.'.update', $product) }}" class="row g-3" enctype="multipart/form-data">
                @csrf
                @if ($mode === 'edit')
                    @method('PUT')
                @endif
                <input type="hidden" name="remove_image_ids" id="remove_image_ids" value="{{ old('remove_image_ids', '') }}">

                <div class="col-md-6">
                    <label class="form-label">Product Name</label>
                    <input type="text" name="product_name" value="{{ old('product_name', $product->product_name) }}" class="form-control" required>
                </div>
                @if ($isVendorPanel)
                    <input type="hidden" name="vendor_id" value="{{ auth('vendor')->id() }}">
                @else
                    <div class="col-md-6">
                        <label class="form-label">Vendor</label>
                        <select name="vendor_id" class="form-select" required>
                            <option value="">Select vendor</option>
                            @foreach ($vendors as $vendor)
                                <option value="{{ $vendor->id }}" @selected((string) old('vendor_id', $product->vendor_id) === (string) $vendor->id)>{{ $vendor->vendor_name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-6">
                    <label class="form-label">Tax</label>
                    <select name="tax_id" class="form-select">
                        <option value="">Optional</option>
                        @foreach ($taxes as $tax)
                            <option value="{{ $tax->id }}" @selected((string) old('tax_id', $product->tax_id) === (string) $tax->id)>{{ $tax->tax_name }} ({{ $tax->tax_percentage }}%)</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select" required>
                        <option value="">Select category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id) === (string) $category->id)>{{ $category->category_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Subcategory</label>
                    <select name="subcategory_id" class="form-select">
                        <option value="">Optional</option>
                        @foreach ($subcategories as $subcategory)
                            <option value="{{ $subcategory->id }}" @selected((string) old('subcategory_id', $product->subcategory_id) === (string) $subcategory->id)>{{ $subcategory->subcategory_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="active" @selected(old('status', $product->status ?: 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $product->status) === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="4" class="form-control">{{ old('description', $product->description) }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Product Images</label>
                    <input type="file" name="images[]" class="form-control" multiple accept="image/*">
                    <div class="form-text">You can upload up to 5 images. Leave blank to keep the current images on edit.</div>
                </div>
                @if ($mode === 'edit' && $product->images->isNotEmpty())
                    <div class="col-12">
                        <div class="row g-3">
                            @foreach ($product->images as $image)
                                <div class="col-6 col-md-3">
                                    <div class="border rounded-3 p-2 h-100" id="product-image-preview-{{ $image->id }}">
                                        <img src="{{ asset($image->image_path) }}" alt="{{ $product->product_name }}" class="img-fluid rounded-2" style="aspect-ratio: 1 / 1; object-fit: cover;">
                                        <button type="button" class="btn btn-sm btn-outline-danger w-100 mt-2" onclick="removeProductImage({{ $image->id }})">Delete Image</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                <div class="col-md-3">
                    <label class="form-label">Price</label>
                    <input type="number" step="0.01" min="0" name="price" value="{{ old('price', $product->price) }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Discount Type</label>
                    <select name="discount_type" class="form-select" id="discountType">
                        <option value="">None</option>
                        <option value="percentage" @selected(old('discount_type', $product->discount_type) === 'percentage')>Percentage</option>
                        <option value="fixed" @selected(old('discount_type', $product->discount_type) === 'fixed')>Fixed Amount</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Discount Value</label>
                    <input type="number" step="0.01" min="0" name="discount_value" value="{{ old('discount_value', $product->discount_value) }}" class="form-control" id="discountValue">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Inventory Mode</label>
                    <select name="inventory_mode" class="form-select" required>
                        <option value="internal" @selected(old('inventory_mode', $product->inventory_mode ?: 'internal') === 'internal')>Internal</option>
                        <option value="epos" @selected(old('inventory_mode', $product->inventory_mode) === 'epos')>EPOS</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Discount Start</label>
                    <input type="date" name="discount_start_date" value="{{ old('discount_start_date', optional($product->discount_start_date)->format('Y-m-d')) }}" class="form-control" id="discountStartDate">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Discount End</label>
                    <input type="date" name="discount_end_date" value="{{ old('discount_end_date', optional($product->discount_end_date)->format('Y-m-d')) }}" class="form-control" id="discountEndDate">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Stock Qty</label>
                    <input type="number" min="0" name="stock_quantity" value="{{ old('stock_quantity', $product->inventory?->stock_quantity) }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Unit</label>
                    @php($selectedUnit = old('unit', $product->unit ?: $product->inventory?->unit))
                    <select name="unit" class="form-select">
                        <option value="">Select unit</option>
                        <option value="kg" @selected($selectedUnit === 'kg')>kg</option>
                        <option value="nos" @selected($selectedUnit === 'nos')>nos</option>
                        <option value="pieces" @selected($selectedUnit === 'pieces')>pieces</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Low Stock</label>
                    <input type="number" min="0" name="low_stock_threshold" value="{{ old('low_stock_threshold', $product->inventory?->low_stock_threshold) }}" class="form-control">
                </div>

                <div class="col-12">
                    <button class="btn btn-primary" type="submit">{{ $mode === 'create' ? 'Save Product' : 'Update Product' }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function removeProductImage(imageId) {
            if (!confirm('Delete this image?')) {
                return;
            }

            const input = document.getElementById('remove_image_ids');
            const preview = document.getElementById('product-image-preview-' + imageId);
            const ids = input.value ? input.value.split(',').filter(Boolean) : [];

            if (!ids.includes(String(imageId))) {
                ids.push(String(imageId));
            }

            input.value = ids.join(',');

            if (preview) {
                preview.style.display = 'none';
            }
        }

        (() => {
            const discountType = document.getElementById('discountType');
            const discountValue = document.getElementById('discountValue');
            const dateFields = [
                document.getElementById('discountStartDate'),
                document.getElementById('discountEndDate'),
            ].filter(Boolean);

            const syncDiscountDates = () => {
                const hasDiscount = Boolean(discountType?.value) && Number(discountValue?.value || 0) > 0;

                dateFields.forEach((field) => {
                    field.disabled = !hasDiscount;
                    if (!hasDiscount) {
                        field.value = '';
                        field.classList.remove('is-invalid');
                        field.removeAttribute('aria-invalid');
                    }
                });
            };

            discountType?.addEventListener('change', syncDiscountDates);
            discountValue?.addEventListener('input', syncDiscountDates);
            syncDiscountDates();
        })();
    </script>
@endpush
