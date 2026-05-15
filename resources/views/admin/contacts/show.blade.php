@extends('layouts.admin')

@section('content')
    <div class="card shell-card mb-4">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h1 class="h3 mb-1">{{ $contact->subject }}</h1>
                    <p class="text-secondary mb-0">Received {{ \App\Support\StoreDate::dateTime($contact->created_at) }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.contacts.index') }}" class="btn btn-outline-secondary">Back</a>
                    @canRoute('admin.contacts.destroy', 'DELETE')
                        <form action="{{ route('admin.contacts.destroy', $contact) }}" method="POST" onsubmit="return confirm('Delete this contact inquiry?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger">Delete</button>
                        </form>
                    @endcanRoute
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-5">
            <div class="card shell-card h-100">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Sender</h2>
                    <ul class="list-unstyled mb-0 info-list">
                        <li><strong>Name:</strong> {{ $contact->name }}</li>
                        <li><strong>Email:</strong> <a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a></li>
                        <li><strong>Phone:</strong> {{ $contact->phone ?: '-' }}</li>
                        <li><strong>Customer:</strong> {{ $contact->user ? $contact->user->name.' (#'.$contact->user->id.')' : '-' }}</li>
                        <li><strong>IP Address:</strong> {{ $contact->ip_address ?: '-' }}</li>
                        <li><strong>Status:</strong> {{ ucfirst($contact->status) }}</li>
                        <li><strong>Read At:</strong> {{ \App\Support\StoreDate::dateTime($contact->read_at) }}</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-7">
            <div class="card shell-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                        <h2 class="h5 mb-0">Message</h2>
                        @canRoute('admin.contacts.update', 'PATCH')
                            <form action="{{ route('admin.contacts.update', $contact) }}" method="POST" class="d-flex gap-2">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="{{ $contact->status === 'new' ? 'read' : 'new' }}">
                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                    Mark {{ $contact->status === 'new' ? 'Read' : 'New' }}
                                </button>
                            </form>
                        @endcanRoute
                    </div>
                    <div class="soft-card p-3">
                        {!! nl2br(e($contact->message)) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
