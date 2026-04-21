@php($editing = isset($product))
@php($isActive = old('is_active', $product->is_active ?? true))

<div class="admin-grid cols-2">
    <label class="field">
        <span>Vendor</span>
        <select name="vendor_id" class="form-control" required>
            <option value="">Select vendor</option>
            @foreach ($vendors as $vendor)
                <option value="{{ $vendor->id }}" @selected(old('vendor_id', $product->vendor_id ?? '') == $vendor->id)>{{ $vendor->name }}</option>
            @endforeach
        </select>
    </label>

    <label class="field">
        <span>Subcategory</span>
        <select name="subcategory_id" class="form-control" required>
            <option value="">Select subcategory</option>
            @foreach ($subcategories as $subcategory)
                <option value="{{ $subcategory->id }}" @selected(old('subcategory_id', $product->subcategory_id ?? '') == $subcategory->id)>{{ $subcategory->category->name }} - {{ $subcategory->name }}</option>
            @endforeach
        </select>
    </label>

    <label class="field">
        <span>Name</span>
        <input type="text" name="name" value="{{ old('name', $product->name ?? '') }}" class="form-control" required>
    </label>

    <label class="field">
        <span>Slug</span>
        <input type="text" name="slug" value="{{ old('slug', $product->slug ?? '') }}" class="form-control" placeholder="auto-generated if blank">
    </label>

    <label class="field">
        <span>SKU</span>
        <input type="text" name="sku" value="{{ old('sku', $product->sku ?? '') }}" class="form-control" required>
    </label>

    <label class="field">
        <span>Image URL</span>
        <input type="text" name="image_url" value="{{ old('image_url', $product->image_url ?? '') }}" class="form-control" placeholder="admin/assets/images/product-1.png">
    </label>

    <label class="field">
        <span>Price</span>
        <input type="number" step="1" name="price" value="{{ old('price', $product->price ?? '') }}" class="form-control" required>
    </label>

    <label class="field">
        <span>MRP</span>
        <input type="number" step="1" name="mrp" value="{{ old('mrp', $product->mrp ?? '') }}" class="form-control" required>
    </label>

    <label class="field">
        <span>Rating</span>
        <input type="number" step="0.1" min="0" max="5" name="rating" value="{{ old('rating', $product->rating ?? 4.5) }}" class="form-control">
    </label>

    <label class="field">
        <span>Unit</span>
        <input type="text" name="unit" value="{{ old('unit', $product->unit ?? '') }}" class="form-control" placeholder="1 kg, 250 ml">
    </label>

    <label class="field">
        <span>Deal Text</span>
        <input type="text" name="deal_text" value="{{ old('deal_text', $product->deal_text ?? '') }}" class="form-control" placeholder="20% OFF">
    </label>

    <label class="field">
        <span>Accent Color</span>
        <input type="text" name="accent_color" value="{{ old('accent_color', $product->accent_color ?? '#2563eb') }}" class="form-control">
    </label>

    <label class="field">
        <span>Background Color</span>
        <input type="text" name="background_color" value="{{ old('background_color', $product->background_color ?? '#eef2ff') }}" class="form-control">
    </label>

    <label class="field full">
        <span>Description</span>
        <textarea name="description" rows="4" class="form-control">{{ old('description', $product->description ?? '') }}</textarea>
    </label>

    <label class="field">
        <span>Active</span>
        <select name="is_active" class="form-control">
            <option value="1" @selected(in_array((string) $isActive, ['1', 'true', 'on'], true))>Yes</option>
            <option value="0" @selected(in_array((string) $isActive, ['0', 'false', 'off'], true))>No</option>
        </select>
    </label>
</div>
