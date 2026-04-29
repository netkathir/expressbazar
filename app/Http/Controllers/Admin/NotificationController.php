<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Notifications\LowStockNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
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
                'message' => $notification->data['message'] ?? 'Notification',
            ])->values(),
        ]);
    }

    private function validateTemplate(Request $request, ?NotificationTemplate $template = null): array
    {
        return $request->validate([
            'template_name' => ['required', 'string', 'max:255'],
            'notification_type' => ['required', 'string', 'max:255'],
            'channel' => ['required', Rule::in(['email', 'sms', 'push'])],
            'subject' => ['nullable', 'string', 'max:255'],
            'message_body' => ['required', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
    }
}
