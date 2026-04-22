@extends('layouts.admin')

@section('content')
    <div class="card shell-card mb-4">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h3 mb-1">Notification Logs</h1>
                <p class="text-secondary mb-0">Delivery history for email, SMS and push notifications.</p>
            </div>
            <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary">Back to templates</a>
        </div>
    </div>

    <div class="card shell-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Recipient</th>
                        <th>Template</th>
                        <th>Channel</th>
                        <th>Status</th>
                        <th>Error</th>
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
                            <td>{{ $log->error_message ?? '-' }}</td>
                            <td>{{ $log->created_at?->format('M d, Y h:i A') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-5">No logs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">
            {{ $logs->links() }}
        </div>
    </div>
@endsection
