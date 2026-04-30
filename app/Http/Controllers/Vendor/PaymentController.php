<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $vendorId = Auth::guard('vendor')->id();

        $payments = Payment::query()
            ->with(['order.customer', 'order.vendor'])
            ->whereHas('order', fn ($query) => $query->where('vendor_id', $vendorId))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->string('search'));
                $query->where('transaction_id', 'like', "%{$search}%");
            })
            ->when($request->filled('payment_method'), fn ($query) => $query->where('payment_method', $request->string('payment_method')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.payments.index', [
            'title' => 'Payment Management',
            'activeMenu' => 'payments',
            'payments' => $payments,
            'routePrefix' => 'vendor.payments',
            'isVendorPanel' => true,
        ]);
    }
}
