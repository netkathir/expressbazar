@extends('layouts.admin')

@section('content')
    <div class="card shell-card mb-4">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h3 mb-1">Contact Inquiries</h1>
                <p class="text-secondary mb-0">Messages submitted from the storefront contact form.</p>
            </div>
        </div>
    </div>

    <div class="card shell-card mb-4">
        <div class="card-body p-4">
            @if (! empty($contactTableMissing))
                <div class="alert alert-warning border-0 rounded-3 mb-3">
                    Contact inquiries table is not available yet. Run <code>php artisan migrate</code> to enable saving and viewing Contact Us submissions.
                </div>
            @endif
            <form class="row g-3 align-items-end" method="GET">
                <div class="col-md-6">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Name, email, phone or subject">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="new" @selected(request('status') === 'new')>New</option>
                        <option value="read" @selected(request('status') === 'read')>Read</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button class="btn btn-dark" type="submit">Filter</button>
                    <a href="{{ route('admin.contacts.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shell-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Received</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($inquiries as $contact)
                        <tr>
                            <td>{{ \App\Support\StoreDate::dateTime($contact->created_at) }}</td>
                            <td class="fw-semibold">{{ $contact->name }}</td>
                            <td><a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a></td>
                            <td>{{ $contact->phone ?: '-' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($contact->subject, 50) }}</td>
                            <td>
                                <span class="badge text-bg-{{ $contact->status === 'new' ? 'warning' : 'success' }}">{{ ucfirst($contact->status) }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.contacts.show', $contact) }}" class="btn btn-sm btn-outline-secondary" aria-label="View contact inquiry" title="View contact inquiry">
                                    <i class="ti ti-eye"></i>
                                </a>
                                @canRoute('admin.contacts.destroy', 'DELETE')
                                    <form action="{{ route('admin.contacts.destroy', $contact) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this contact inquiry?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Delete contact inquiry" title="Delete contact inquiry">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                @endcanRoute
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-5">
                                {{ ! empty($contactTableMissing) ? 'Contact inquiries are not available until the database migration is run.' : 'No contact inquiries found.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">
            {{ $inquiries->links() }}
        </div>
    </div>
@endsection
