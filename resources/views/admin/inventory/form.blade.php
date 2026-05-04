@extends('layouts.admin')

@section('content')
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
                        <select name="product_id" class="form-select" required>
                            <option value="">Select product</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" @selected((string) old('product_id') === (string) $product->id)>{{ $product->product_name }} ({{ strtoupper($product->inventory_mode) }})</option>
                            @endforeach
                        </select>
                    @else
                        <input type="hidden" name="product_id" value="{{ $inventory->product_id }}">
                        <input type="text" class="form-control" value="{{ $inventory->product?->product_name }}" readonly>
                    @endif
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
