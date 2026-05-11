@extends('layouts.storefront')

@section('content')
    <main class="sf-page">
        <section class="container-fluid px-3 px-lg-4 py-4">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-8 col-xl-6">
                    <div class="sf-info-card p-4 p-md-5">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                            <div>
                                <h3 class="mb-1">Edit Profile</h3>
                                <p class="text-secondary mb-0">Update your contact details without changing your existing account access.</p>
                            </div>
                            <a href="{{ route('storefront.account') }}" class="btn btn-outline-dark rounded-pill btn-sm">Back to Account</a>
                        </div>

                        <form method="POST" action="{{ route('storefront.profile.update') }}" class="row g-3">
                            @csrf
                            @method('PUT')

                            <div class="col-12">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="active" @selected(old('status', $user->status ?: 'active') === 'active')>Active</option>
                                    <option value="inactive" @selected(old('status', $user->status) === 'inactive')>Inactive</option>
                                </select>
                            </div>

                            <div class="col-12 d-grid d-md-flex justify-content-md-end gap-2 pt-2">
                                <a href="{{ route('storefront.account') }}" class="btn btn-light rounded-pill px-4 js-profile-cancel">Cancel</a>
                                <button class="btn btn-danger rounded-pill px-4">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <div class="modal fade" id="profileCancelModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-4 border-0">
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <h5 class="modal-title fw-bold">Cancel profile changes?</h5>
                            <div class="text-secondary small">Any unsaved profile changes will be lost.</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex flex-wrap justify-content-end gap-2">
                            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Keep Editing</button>
                            <a href="{{ route('storefront.account') }}" class="btn btn-danger rounded-pill px-4">Cancel Changes</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        document.querySelector('.js-profile-cancel')?.addEventListener('click', function (event) {
            const modalEl = document.getElementById('profileCancelModal');

            if (!modalEl || !window.bootstrap) {
                return;
            }

            event.preventDefault();
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });
    </script>
@endpush
