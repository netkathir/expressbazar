<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Notifications\LowStockNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    public function index()
    {
        return view('admin.notifications.index', [
            'title' => 'Notification Management',
            'activeMenu' => 'notifications',
            'templates' => NotificationTemplate::latest()->paginate(10),
            'logs' => NotificationLog::with('template')->latest()->limit(10)->get(),
        ]);
    }

    public function create()
    {
        return view('admin.notifications.form', [
            'title' => 'Add Notification Template',
            'activeMenu' => 'notifications',
            'template' => new NotificationTemplate(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateTemplate($request);
        NotificationTemplate::create($data);

        return redirect()->route('admin.notifications.index')->with('success', 'Notification template created successfully.');
    }

    public function edit(NotificationTemplate $notification)
    {
        return view('admin.notifications.form', [
            'title' => 'Edit Notification Template',
            'activeMenu' => 'notifications',
            'template' => $notification,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, NotificationTemplate $notification)
    {
        $data = $this->validateTemplate($request, $notification);
        $notification->update($data);

        return redirect()->route('admin.notifications.index')->with('success', 'Notification template updated successfully.');
    }

    public function destroy(NotificationTemplate $notification)
    {
        $notification->delete();

        return redirect()->route('admin.notifications.index')->with('success', 'Notification template deleted successfully.');
    }

    public function logs()
    {
        return view('admin.notifications.logs', [
            'title' => 'Notification Logs',
            'activeMenu' => 'notifications',
            'logs' => NotificationLog::with('template')->latest()->paginate(15),
        ]);
    }

    public function alerts(Request $request): JsonResponse
    {
        if (! Schema::hasTable('notifications')) {
            return response()->json([
                'count' => 0,
                'items' => [],
            ]);
        }

        $notificationsQuery = $request->user()
            ?->unreadNotifications()
            ->where('type', LowStockNotification::class);

        $notifications = collect();
        $count = 0;

        if ($notificationsQuery) {
            $notifications = (clone $notificationsQuery)->latest()->limit(5)->get();
            $count = (clone $notificationsQuery)->count();
        }

        return response()->json([
            'count' => $count,
            'items' => $notifications->map(fn ($notification) => [
                'id' => $notification->id,
                'message' => $notification->data['message'] ?? 'Notification',
                'url' => route('admin.notifications.read', $notification->id),
            ])->values(),
        ]);
    }

    public function read(Request $request, string $id): RedirectResponse
    {
        abort_if(! $request->user() || ! Schema::hasTable('notifications'), 404);

        $notification = $request->user()
            ->notifications()
            ->whereKey($id)
            ->firstOrFail();

        $notification->markAsRead();

        return redirect()->to($this->notificationRedirectUrl($request, $notification));
    }

    public function readAll(Request $request): JsonResponse
    {
        abort_if(! $request->user(), 403);

        if (! Schema::hasTable('notifications')) {
            return response()->json(['success' => true]);
        }

        $request->user()
            ->unreadNotifications()
            ->where('type', LowStockNotification::class)
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    private function notificationRedirectUrl(Request $request, $notification): string
    {
        $routeName = $this->notificationRouteName($notification);

        if (! $routeName || ! Route::has($routeName)) {
            return route('admin.notifications.index');
        }

        if (method_exists($request->user(), 'canAccessAdminRoute') && ! $request->user()->canAccessAdminRoute($routeName, 'GET')) {
            return route('admin.dashboard');
        }

        return route($routeName, $this->notificationRouteParams($notification, $routeName));
    }

    private function notificationRouteName($notification): ?string
    {
        $data = $notification->data ?? [];

        if (! empty($data['route_name']) && str_starts_with((string) $data['route_name'], 'admin.')) {
            return (string) $data['route_name'];
        }

        if (! empty($data['module'])) {
            return $this->moduleRouteName((string) $data['module']);
        }

        return match ($notification->type) {
            LowStockNotification::class => 'admin.inventory.index',
            default => 'admin.notifications.index',
        };
    }

    private function notificationRouteParams($notification, string $routeName): array
    {
        $data = $notification->data ?? [];

        if (! empty($data['route_params']) && is_array($data['route_params'])) {
            return $data['route_params'];
        }

        if ($routeName === 'admin.inventory.index') {
            return array_filter([
                'product_id' => $data['product_id'] ?? null,
                'low_stock' => 1,
            ], fn ($value) => $value !== null && $value !== '');
        }

        return [];
    }

    private function moduleRouteName(string $module): ?string
    {
        return match (trim($module)) {
            'vendors' => 'admin.vendors.index',
            'categories' => 'admin.categories.index',
            'subcategories' => 'admin.subcategories.index',
            'customers' => 'admin.customers.index',
            'taxes' => 'admin.taxes.index',
            'countries' => 'admin.countries.index',
            'cities' => 'admin.cities.index',
            'zones' => 'admin.zones.index',
            'products' => 'admin.products.index',
            'inventory' => 'admin.inventory.index',
            'orders' => 'admin.orders.index',
            'payments' => 'admin.payments.index',
            'coupons' => 'admin.coupons.index',
            'delivery' => 'admin.delivery.index',
            'notifications' => 'admin.notifications.index',
            'reports' => 'admin.reports.index',
            'roles' => 'admin.roles.index',
            'users' => 'admin.users.index',
            'config' => 'admin.system-config.edit',
            default => null,
        };
    }

    private function validateTemplate(Request $request, ?NotificationTemplate $template = null): array
    {
        $normalizedType = Str::of((string) $request->input('notification_type'))
            ->trim()
            ->replaceMatches('/[^A-Za-z0-9]+/', '_')
            ->trim('_')
            ->upper()
            ->toString();

        $request->merge([
            'template_name' => trim((string) $request->input('template_name')),
            'notification_type' => $normalizedType,
            'subject' => trim((string) $request->input('subject')),
            'message_body' => trim((string) $request->input('message_body')),
        ]);

        return $request->validate([
            'template_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('notification_templates', 'template_name')->ignore($template?->id),
            ],
            'notification_type' => [
                'required',
                'string',
                'max:255',
                'regex:/\A[A-Z][A-Z0-9_]*\z/',
                Rule::unique('notification_templates', 'notification_type')
                    ->where(fn ($query) => $query->where('channel', $request->input('channel')))
                    ->ignore($template?->id),
            ],
            'channel' => ['required', Rule::in(['email', 'sms', 'push'])],
            'subject' => ['nullable', 'string', 'max:255'],
            'message_body' => ['required', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ], [
            'template_name.unique' => 'A notification template with this name already exists.',
            'notification_type.regex' => 'Type must use uppercase letters, numbers, and underscores only.',
            'notification_type.unique' => 'A notification template with this type and channel already exists.',
            'message_body.required' => 'Message body is required.',
        ]);
    }
}
