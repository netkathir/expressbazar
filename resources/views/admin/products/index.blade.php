@extends('layouts.admin')

@section('content')
    <div class="card shell-card mb-4">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h3 mb-1">Product Management</h1>
            </div>
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary">Add Product</a>
        </div>
    </div>

    <div class="card shell-card mb-4">
        <div class="card-body p-4">
            <form class="row g-3 align-items-end" method="GET">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Product name">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">All</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->category_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Vendor</label>
                    <select name="vendor_id" class="form-select">
                        <option value="">All</option>
                        @foreach ($vendors as $vendor)
                            <option value="{{ $vendor->id }}" @selected((string) request('vendor_id') === (string) $vendor->id)>{{ $vendor->vendor_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Mode</label>
                    <select name="inventory_mode" class="form-select">
                        <option value="">All</option>
                        <option value="internal" @selected(request('inventory_mode') === 'internal')>Internal</option>
                        <option value="epos" @selected(request('inventory_mode') === 'epos')>EPOS</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-dark" type="submit">Filter</button>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shell-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Images</th>
                        <th>Category</th>
                        <th>Tax</th>
                        <th>Vendor</th>
                        <th>Price</th>
                        <th>Inventory</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td class="fw-semibold">{{ $product->product_name }}</td>
                            <td>
                                @if ($product->images->isNotEmpty())
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach ($product->images->take(2) as $image)
                                            <img src="{{ asset($image->image_path) }}" alt="{{ $product->product_name }}" style="width: 44px; height: 44px; object-fit: cover; border-radius: 10px;">
                                        @endforeach
                                        @if ($product->images->count() > 2)
                                            <span class="small text-secondary align-self-center">+{{ $product->images->count() - 2 }} more</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-secondary">-</span>
                                @endif
                            </td>
                            <td>
                                {{ $product->category?->category_name ?? '-' }}
                                <div class="small text-secondary">{{ $product->subcategory?->subcategory_name ?? '' }}</div>
                            </td>
                            <td>{{ $product->tax?->tax_name ?? '-' }}</td>
                            <td>{{ $product->vendor?->vendor_name ?? '-' }}</td>
                            <td>{{ number_format((float) $product->price, 2) }}</td>
                            <td>
                                <span class="badge text-bg-{{ $product->inventory_mode === 'epos' ? 'info' : 'primary' }}">{{ strtoupper($product->inventory_mode) }}</span>
                                <div class="small text-secondary">
                                    {{ $product->inventory?->stock_quantity ?? 0 }} {{ $product->unit ?: $product->inventory?->unit }}
                                </div>
                            </td>
                            <td><span class="badge text-bg-{{ $product->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($product->status) }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-outline-primary" aria-label="Edit product" title="Edit product">
                                    <i class="ti ti-pencil"></i>
                                </a>
                                <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this product?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Delete product" title="Delete product">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-secondary py-5">{{ config('ui_messages.no_products') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">
            {{ $products->links() }}
        </div>
    </div>
@endsection
