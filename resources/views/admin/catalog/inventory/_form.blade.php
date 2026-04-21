@php($editing = isset($inventory))

<div class="admin-grid cols-2">
    <label class="field">
        <span>Vendor</span>
        <select name="vendor_id" class="form-control" required>
            <option value="">Select vendor</option>
            @foreach ($vendors as $vendor)
                <option value="{{ $vendor->id }}" @selected(old('vendor_id', $inventory->vendor_id ?? '') == $vendor->id)>{{ $vendor->name }}</option>
            @endforeach
        </select>
    </label>

    <label class="field">
        <span>Product</span>
        <select name="product_id" class="form-control" required>
            <option value="">Select product</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}" @selected(old('product_id', $inventory->product_id ?? '') == $product->id)>{{ $product->name }} @if($product->subcategory) - {{ $product->subcategory->name }} @endif</option>
            @endforeach
        </select>
    </label>

    <label class="field">
        <span>Price</span>
        <input type="number" step="1" name="price" value="{{ old('price', $inventory->price ?? '') }}" class="form-control" required>
    </label>

    <label class="field">
        <span>Stock</span>
        <input type="number" name="stock" value="{{ old('stock', $inventory->stock ?? 0) }}" class="form-control" required>
    </label>

    <label class="field">
        <span>Status</span>
        <select name="is_active" class="form-control">
            <option value="1" @selected(old('is_active', $inventory->is_active ?? true))>Active</option>
            <option value="0" @selected(!old('is_active', $inventory->is_active ?? true))>Inactive</option>
        </select>
    </label>
</div>
