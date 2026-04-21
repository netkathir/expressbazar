@php($editing = isset($subcategory))

<div class="admin-grid cols-2">
    <label class="field">
        <span>Category</span>
        <select name="category_id" class="form-control" required>
            <option value="">Select category</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $subcategory->category_id ?? '') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </label>

    <label class="field">
        <span>Name</span>
        <input type="text" name="name" value="{{ old('name', $subcategory->name ?? '') }}" class="form-control" required>
    </label>

    <label class="field">
        <span>Slug</span>
        <input type="text" name="slug" value="{{ old('slug', $subcategory->slug ?? '') }}" class="form-control" placeholder="auto-generated if blank">
    </label>

    <label class="field full">
        <span>Description</span>
        <textarea name="description" rows="4" class="form-control">{{ old('description', $subcategory->description ?? '') }}</textarea>
    </label>
</div>
