@extends('layouts.admin')

@section('content')
    <div class="card shell-card">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">{{ $mode === 'create' ? 'Add Tax' : 'Edit Tax' }}</h1>
                </div>
                <a href="{{ route('admin.taxes.index') }}" class="btn btn-outline-secondary" data-dirty-back>Back</a>
            </div>

            <form method="POST" action="{{ $mode === 'create' ? route('admin.taxes.store') : route('admin.taxes.update', $tax) }}" class="row g-3" data-dirty-check>
                @csrf
                @if ($mode === 'edit')
                    @method('PUT')
                @endif

                <div class="col-md-6">
                    <label class="form-label">Tax Name</label>
                    <input type="text" name="tax_name" value="{{ old('tax_name', $tax->tax_name) }}" class="form-control" required pattern="^(?=.*[A-Za-z0-9])[A-Za-z0-9 .&'()\/-]+$">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Percentage</label>
                    <input type="number" name="tax_percentage" step="0.01" min="0" max="100" value="{{ old('tax_percentage', $tax->tax_percentage) }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="active" @selected(old('status', $tax->status ?: 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $tax->status) === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Country</label>
                    <select name="country_id" class="form-select js-tax-country" required>
                        <option value="">Select country</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}" @selected((string) old('country_id', $tax->country_id) === (string) $country->id)>{{ $country->country_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Region</label>
                    @php($selectedRegion = old('region_name', $tax->region_name))
                    <select name="region_name" class="form-select js-tax-region" required>
                        <option value="">Select region</option>
                        @foreach ($regions as $region)
                            <option
                                value="{{ $region->zone_name }}"
                                data-country="{{ $region->country_id }}"
                                @selected($selectedRegion === $region->zone_name)
                            >
                                {{ $region->zone_name }}{{ $region->country?->country_name ? ' - '.$region->country->country_name : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">{{ $mode === 'create' ? 'Save Tax' : 'Update Tax' }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const country = document.querySelector('.js-tax-country');
            const region = document.querySelector('.js-tax-region');

            if (!country || !region) {
                return;
            }

            const filterRegions = () => {
                const countryId = country.value;

                region.querySelectorAll('option[data-country]').forEach((option) => {
                    option.hidden = countryId !== '' && option.dataset.country !== countryId;
                });

                const selectedOption = region.selectedOptions[0];
                if (selectedOption?.hidden) {
                    region.value = '';
                }
            };

            country.addEventListener('change', filterRegions);
            filterRegions();
        })();
    </script>
@endpush
