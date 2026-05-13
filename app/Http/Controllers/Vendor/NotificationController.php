<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Notifications\LowStockNotification;
use App\Notifications\VendorOrderNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
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
            ->whereIn('type', $this->notificationTypes());

        $notifications = (clone $notificationsQuery)->latest()->limit(5)->get();

        return response()->json([
            'count' => (clone $notificationsQuery)->count(),
            'items' => $notifications->map(fn ($notification) => [
                'id' => $notification->id,
                'message' => $notification->data['message'] ?? 'Notification',
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
            ->whereIn('type', $this->notificationTypes())
            ->firstOrFail();

        $notification->markAsRead();

        return redirect()->to($this->notificationRedirectUrl($vendor, $notification));
    }

    private function notificationRedirectUrl($vendor, $notification): string
    {
        $routeName = match ($notification->type) {
            VendorOrderNotification::class => ! empty($notification->data['order_id'])
                ? 'vendor.orders.show'
                : 'vendor.orders.index',
            LowStockNotification::class => 'vendor.inventory.index',
            default => 'vendor.notifications.index',
        };

        if (! Route::has($routeName)) {
            return route('vendor.notifications.index');
        }

        if (method_exists($vendor, 'canAccessVendorRoute') && ! $vendor->canAccessVendorRoute($routeName, 'GET')) {
            return route('vendor.dashboard');
        }

        return route($routeName, $this->notificationRouteParams($notification, $routeName));
    }

    private function notificationRouteParams($notification, string $routeName): array
    {
        if ($routeName === 'vendor.orders.show' && ! empty($notification->data['order_id'])) {
            return ['order' => $notification->data['order_id']];
        }

        if ($routeName === 'vendor.inventory.index') {
            return array_filter([
                'product_id' => $notification->data['product_id'] ?? null,
                'low_stock' => 1,
            ], fn ($value) => $value !== null && $value !== '');
        }

        return [];
    }

    private function notificationTypes(): array
    {
        return [
            VendorOrderNotification::class,
            LowStockNotification::class,
        ];
    }
}
