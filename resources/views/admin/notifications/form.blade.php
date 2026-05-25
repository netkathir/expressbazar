@extends('layouts.admin')

@section('content')
    @php
        $normalizedNotificationType = \Illuminate\Support\Str::of((string) $template->notification_type)
            ->trim()
            ->replaceMatches('/[^A-Za-z0-9]+/', '_')
            ->trim('_')
            ->upper()
            ->toString();
    @endphp
    <div class="card shell-card">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">{{ $mode === 'create' ? 'Add Notification Template' : 'Edit Notification Template' }}</h1>
                </div>
                <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary" data-dirty-back>Back</a>
            </div>

            <form method="POST" action="{{ $mode === 'create' ? route('admin.notifications.store') : route('admin.notifications.update', $template) }}" class="row g-3" data-dirty-check>
                @csrf
                @if ($mode === 'edit')
                    @method('PUT')
                @endif

                <div class="col-md-6">
                    <label class="form-label">Template Name</label>
                    <input type="text" name="template_name" value="{{ old('template_name', $template->template_name) }}" class="form-control @error('template_name') is-invalid @enderror" required>
                    @error('template_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @else
                        <div class="invalid-feedback">Template name is required.</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Type</label>
                    <input
                        type="text"
                        name="notification_type"
                        value="{{ old('notification_type', $normalizedNotificationType) }}"
                        class="form-control @error('notification_type') is-invalid @enderror"
                        required
                        pattern="[A-Z][A-Z0-9_]*"
                        title="Use uppercase letters, numbers, and underscores, for example ORDER_CONFIRMED."
                    >
                    <div class="form-text">Use uppercase snake case, for example ORDER_CONFIRMED.</div>
                    @error('notification_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @else
                        <div class="invalid-feedback">Type is required and must use uppercase snake case.</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Channel</label>
                    <select name="channel" class="form-select @error('channel') is-invalid @enderror" required>
                        @foreach (['email', 'sms', 'push'] as $channel)
                            <option value="{{ $channel }}" @selected(old('channel', $template->channel ?: 'email') === $channel)>{{ strtoupper($channel) }}</option>
                        @endforeach
                    </select>
                    @error('channel')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Subject</label>
                    <input type="text" name="subject" value="{{ old('subject', $template->subject) }}" class="form-control @error('subject') is-invalid @enderror">
                    @error('subject')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                        <option value="active" @selected(old('status', $template->status ?: 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $template->status) === 'inactive')>Inactive</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Message Body</label>
                    <textarea name="message_body" rows="6" class="form-control @error('message_body') is-invalid @enderror" required>{{ old('message_body', $template->message_body) }}</textarea>
                    @error('message_body')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @else
                        <div class="invalid-feedback">Message body is required.</div>
                    @enderror
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">{{ $mode === 'create' ? 'Save Template' : 'Update Template' }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const typeInput = document.querySelector('input[name="notification_type"]');
            if (!typeInput) {
                return;
            }

            const normalizeType = () => {
                typeInput.value = typeInput.value
                    .trim()
                    .replace(/[^A-Za-z0-9]+/g, '_')
                    .replace(/^_+|_+$/g, '')
                    .toUpperCase();
            };

            typeInput.addEventListener('blur', normalizeType);
            typeInput.form?.addEventListener('submit', normalizeType);
        })();
    </script>
@endpush
