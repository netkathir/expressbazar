@extends('layouts.admin')

@section('content')
    <div class="card shell-card">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">{{ $mode === 'create' ? 'Add Notification Template' : 'Edit Notification Template' }}</h1>
                </div>
                <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary">Back</a>
            </div>

            <form method="POST" action="{{ $mode === 'create' ? route('admin.notifications.store') : route('admin.notifications.update', $template) }}" class="row g-3">
                @csrf
                @if ($mode === 'edit')
                    @method('PUT')
                @endif

                <div class="col-md-6">
                    <label class="form-label">Template Name</label>
                    <input type="text" name="template_name" value="{{ old('template_name', $template->template_name) }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Type</label>
                    <input type="text" name="notification_type" value="{{ old('notification_type', $template->notification_type) }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Channel</label>
                    <select name="channel" class="form-select" required>
                        @foreach (['email', 'sms', 'push'] as $channel)
                            <option value="{{ $channel }}" @selected(old('channel', $template->channel ?: 'email') === $channel)>{{ strtoupper($channel) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Subject</label>
                    <input type="text" name="subject" value="{{ old('subject', $template->subject) }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="active" @selected(old('status', $template->status ?: 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $template->status) === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Message Body</label>
                    <textarea name="message_body" rows="6" class="form-control" required>{{ old('message_body', $template->message_body) }}</textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">{{ $mode === 'create' ? 'Save Template' : 'Update Template' }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
