<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Notifications\VendorOrderNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class NotificationController extends Controller
{
    public function alerts(Request $request): JsonResponse
    {
        $vendor = Auth::guard('vendor')->user();

        if (! $vendor || ! Schema::hasTable('notifications')) {
            return response()->json([
                'count' => 0,
                'items' => [],
            ]);
        }

        $notificationsQuery = $vendor->unreadNotifications()
            ->where('type', VendorOrderNotification::class);

        $notifications = (clone $notificationsQuery)->latest()->limit(5)->get();

        return response()->json([
            'count' => (clone $notificationsQuery)->count(),
            'items' => $notifications->map(fn ($notification) => [
                'id' => $notification->id,
                'message' => $notification->data['message'] ?? 'Order received',
                'url' => route('vendor.notifications.read', $notification->id),
            ])->values(),
        ]);
    }

    public function read(string $id): RedirectResponse
    {
        $vendor = Auth::guard('vendor')->user();

        abort_if(! $vendor || ! Schema::hasTable('notifications'), 404);

        $notification = $vendor->notifications()
            ->whereKey($id)
            ->where('type', VendorOrderNotification::class)
            ->firstOrFail();

        $notification->markAsRead();

        return redirect()->route('vendor.orders.index');
    }
}
