@extends('layouts.admin')

@section('content')
    <div class="card shell-card">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">System Configuration</h1>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.system-config.update') }}" class="row g-3">
                @csrf
                @method('PUT')

                <div class="col-md-6">
                    <label class="form-label">Application Name</label>
                    <input type="text" name="application_name" value="{{ old('application_name', $settings['application_name'] ?? 'EXPRESS BAZAAR') }}" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Company Name</label>
                    <input type="text" name="company_name" value="{{ old('company_name', $settings['company_name'] ?? 'AMAZE FARMS LIMITED') }}" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Support Email</label>
                    <input type="email" name="support_email" value="{{ old('support_email', $settings['support_email'] ?? '') }}" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Support Phone</label>
                    <input type="text" name="support_phone" value="{{ old('support_phone', $settings['support_phone'] ?? '') }}" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Address Line 1</label>
                    <input type="text" name="address_line_1" value="{{ old('address_line_1', $settings['address_line_1'] ?? '') }}" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Address Line 2</label>
                    <input type="text" name="address_line_2" value="{{ old('address_line_2', $settings['address_line_2'] ?? '') }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Default Country</label>
                    <input type="text" name="default_country" value="{{ old('default_country', $settings['default_country'] ?? '') }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Default City</label>
                    <input type="text" name="default_city" value="{{ old('default_city', $settings['default_city'] ?? '') }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Default Zone</label>
                    <input type="text" name="default_zone" value="{{ old('default_zone', $settings['default_zone'] ?? '') }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Minimum Order Value</label>
                    <input type="number" step="0.01" min="0" name="minimum_order_value" value="{{ old('minimum_order_value', $settings['minimum_order_value'] ?? '') }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Free Delivery Threshold</label>
                    <input type="number" step="0.01" min="0" name="free_delivery_threshold" value="{{ old('free_delivery_threshold', $settings['free_delivery_threshold'] ?? '') }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Delivery Time Estimate</label>
                    <input type="text" name="delivery_time_estimate" value="{{ old('delivery_time_estimate', $settings['delivery_time_estimate'] ?? '') }}" class="form-control">
                </div>

                <div class="col-12">
                    <div class="card shell-card">
                        <div class="card-body p-4">
                            <h2 class="h5 mb-3">Feature Toggles</h2>
                            <div class="row g-3">
                                @foreach ([
                                    'online_payment_enabled' => 'Online Payment',
                                    'cod_enabled' => 'Cash on Delivery',
                                    'vendor_registration_enabled' => 'Vendor Registration',
                                    'epos_enabled' => 'EPOS Integration',
                                    'email_notifications_enabled' => 'Email Notifications',
                                    'sms_notifications_enabled' => 'SMS Notifications',
                                ] as $key => $label)
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input type="checkbox" name="{{ $key }}" value="1" class="form-check-input" id="{{ $key }}" @checked(old($key, $settings[$key] ?? false))>
                                            <label class="form-check-label" for="{{ $key }}">{{ $label }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <button class="btn btn-primary" type="submit">Save Configuration</button>
                </div>
            </form>
        </div>
    </div>
@endsection
