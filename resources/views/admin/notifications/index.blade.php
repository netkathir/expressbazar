@extends('layouts.admin')

@section('content')
    <div class="card shell-card mb-4">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h3 mb-1">Notification Management</h1>
                <p class="text-secondary mb-0">Create templates and review the latest delivery logs.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.notifications.logs') }}" class="btn btn-outline-secondary">View Logs</a>
                <a href="{{ route('admin.notifications.create') }}" class="btn btn-primary">Add Template</a>
            </div>
        </div>
    </div>

    <div class="card shell-card mb-4">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Template</th>
                            <th>Type</th>
                            <th>Channel</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($templates as $template)
                            <tr>
                                <td class="fw-semibold">{{ $template->template_name }}</td>
                                <td>{{ $template->notification_type }}</td>
                                <td>{{ strtoupper($template->channel) }}</td>
                                <td><span class="badge text-bg-{{ $template->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($template->status) }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('admin.notifications.edit', $template) }}" class="btn btn-sm btn-outline-primary" aria-label="Edit notification template" title="Edit notification template">
                                        <i class="ti ti-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.notifications.destroy', $template) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this template?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Delete notification template" title="Delete notification template">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-5">No notification templates found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $templates->links() }}
            </div>
        </div>
    </div>

    <div class="card shell-card">
        <div class="card-body p-4">
            <h2 class="h5 mb-3">Recent Logs</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Recipient</th>
                            <th>Template</th>
                            <th>Channel</th>
                            <th>Status</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td>{{ $log->recipient_type }} #{{ $log->recipient_id }}</td>
                                <td>{{ $log->template?->template_name ?? '-' }}</td>
                                <td>{{ strtoupper($log->channel) }}</td>
                                <td>{{ ucfirst($log->status) }}</td>
                                <td>{{ $log->created_at?->format('M d, Y h:i A') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-4">No notification logs yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
