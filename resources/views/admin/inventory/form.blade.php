@extends('layouts.admin')

@section('content')
    @php
        $selectedProductId = old('product_id', $inventory->product_id);
        $selectedProduct = $products->firstWhere('id', (int) $selectedProductId);
        $currentStock = $mode === 'edit'
            ? (int) $inventory->stock_quantity
            : (int) ($selectedProduct?->inventory?->stock_quantity ?? 0);
    @endphp

    <div class="card shell-card">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">{{ $mode === 'create' ? 'Adjust Stock' : 'Edit Stock' }}</h1>
                </div>
                <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-secondary" data-dirty-back>Back</a>
            </div>

            <form method="POST" action="{{ $mode === 'create' ? route('admin.inventory.store') : route('admin.inventory.update', $inventory) }}" class="row g-3" data-dirty-check>
                @csrf
                @if ($mode === 'edit')
                    @method('PUT')
                @endif

                <div class="col-md-6">
                    <label class="form-label">Product</label>
                    @if ($mode === 'create')
                        <select name="product_id" class="form-select" id="inventoryProductSelect" required>
                            <option value="">Select product</option>
                            @foreach ($products as $product)
                                <option
                                    value="{{ $product->id }}"
                                    data-stock="{{ (int) ($product->inventory?->stock_quantity ?? 0) }}"
                                    @selected((string) old('product_id') === (string) $product->id)
                                >
                                    {{ $product->product_name }} ({{ strtoupper($product->inventory_mode) }})
                                </option>
                            @endforeach
                        </select>
                    @else
                        <input type="hidden" name="product_id" value="{{ $inventory->product_id }}">
                        <input type="text" class="form-control" value="{{ $inventory->product?->product_name }}" readonly>
                    @endif
                </div>
                <div class="col-md-3">
                    <label class="form-label">Current Stock</label>
                    <input type="text" class="form-control" id="currentStockDisplay" value="{{ $currentStock }}" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Adjustment Type</label>
                    <select name="adjustment_type" class="form-select" required>
                        <option value="add" @selected(old('adjustment_type') === 'add')>Add</option>
                        <option value="reduce" @selected(old('adjustment_type') === 'reduce')>Reduce</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Quantity</label>
                    <input type="number" min="1" name="quantity" value="{{ old('quantity', 1) }}" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Reason</label>
                    <input type="text" name="reason" value="{{ old('reason') }}" class="form-control" placeholder="Optional note">
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">{{ $mode === 'create' ? 'Save Adjustment' : 'Update Stock' }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const productSelect = document.getElementById('inventoryProductSelect');
            const stockDisplay = document.getElementById('currentStockDisplay');

            if (!productSelect || !stockDisplay) {
                return;
            }

            const syncCurrentStock = () => {
                const selectedOption = productSelect.options[productSelect.selectedIndex];
                stockDisplay.value = selectedOption?.dataset.stock || '0';
            };

            productSelect.addEventListener('change', syncCurrentStock);
            syncCurrentStock();
        });
    </script>
@endpush
