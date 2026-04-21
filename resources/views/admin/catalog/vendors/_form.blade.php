@php($editing = isset($vendor))
@php($vendorLocations = isset($vendor) ? $vendor->locations->pluck('id')->all() : [])
@php($selectedLocations = collect(old('location_ids', $vendorLocations))->map(fn ($id) => (string) $id)->all())

<div class="admin-grid cols-2">
    <label class="field">
        <span>Name</span>
        <input type="text" name="name" value="{{ old('name', $vendor->name ?? '') }}" class="form-control" required>
    </label>

    <label class="field">
        <span>Slug</span>
        <input type="text" name="slug" value="{{ old('slug', $vendor->slug ?? '') }}" class="form-control" placeholder="auto-generated if blank">
    </label>

    <label class="field">
        <span>Phone</span>
        <input type="text" name="phone" value="{{ old('phone', $vendor->phone ?? '') }}" class="form-control">
    </label>

    <label class="field">
        <span>Email</span>
        <input type="email" name="email" value="{{ old('email', $vendor->email ?? '') }}" class="form-control">
    </label>

    <label class="field">
        <span>Address</span>
        <input type="text" name="address" value="{{ old('address', $vendor->address ?? '') }}" class="form-control">
    </label>

    <label class="field">
        <span>Service Radius (km)</span>
        <input type="number" step="0.01" name="service_radius_km" value="{{ old('service_radius_km', $vendor->service_radius_km ?? 20) }}" class="form-control">
    </label>

    <label class="field">
        <span>Latitude</span>
        <input type="number" step="0.0000001" name="latitude" value="{{ old('latitude', $vendor->latitude ?? '') }}" class="form-control">
    </label>

    <label class="field">
        <span>Longitude</span>
        <input type="number" step="0.0000001" name="longitude" value="{{ old('longitude', $vendor->longitude ?? '') }}" class="form-control">
    </label>

    <label class="field">
        <span>Rating</span>
        <input type="number" step="0.1" min="0" max="5" name="rating" value="{{ old('rating', $vendor->rating ?? 4.5) }}" class="form-control">
    </label>

    <label class="field">
        <span>Status</span>
        <select name="is_active" class="form-control">
            <option value="1" @selected(old('is_active', $vendor->is_active ?? true))>Active</option>
            <option value="0" @selected(!old('is_active', $vendor->is_active ?? true))>Inactive</option>
        </select>
    </label>

    <label class="field full">
        <span>Delivery Locations</span>
        <select name="location_ids[]" class="form-control" multiple size="6">
            @foreach ($locations as $location)
                <option value="{{ $location->id }}" @selected(in_array((string) $location->id, $selectedLocations, true))>{{ $location->city }} - {{ $location->pincode }}</option>
            @endforeach
        </select>
    </label>

    <label class="field full">
        <span>Description</span>
        <textarea name="description" rows="4" class="form-control">{{ old('description', $vendor->description ?? '') }}</textarea>
    </label>
</div>
