<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemConfig;
use Illuminate\Http\Request;

class SystemConfigController extends Controller
{
    public function edit()
    {
        $settings = SystemConfig::all()->pluck('config_value', 'config_key')->all();

        return view('admin.system-config.edit', [
            'title' => 'System Configuration',
            'activeMenu' => 'config',
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'application_name' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:50'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'default_country' => ['nullable', 'string', 'max:255'],
            'default_city' => ['nullable', 'string', 'max:255'],
            'default_zone' => ['nullable', 'string', 'max:255'],
            'minimum_order_value' => ['nullable', 'numeric', 'min:0'],
            'free_delivery_threshold' => ['nullable', 'numeric', 'min:0'],
            'delivery_time_estimate' => ['nullable', 'string', 'max:255'],
            'online_payment_enabled' => ['nullable'],
            'cod_enabled' => ['nullable'],
            'vendor_registration_enabled' => ['nullable'],
            'epos_enabled' => ['nullable'],
            'email_notifications_enabled' => ['nullable'],
            'sms_notifications_enabled' => ['nullable'],
        ]);

        foreach ($data as $key => $value) {
            SystemConfig::updateOrCreate(
                ['config_key' => $key],
                ['config_value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value]
            );
        }

        foreach (['online_payment_enabled', 'cod_enabled', 'vendor_registration_enabled', 'epos_enabled', 'email_notifications_enabled', 'sms_notifications_enabled'] as $flag) {
            SystemConfig::updateOrCreate(
                ['config_key' => $flag],
                ['config_value' => $request->boolean($flag) ? '1' : '0']
            );
        }

        return redirect()->route('admin.system-config.edit')->with('success', 'System configuration updated successfully.');
    }
}
