@extends('layouts.storefront')

@section('content')
    <main class="sf-page">
        <section class="container-fluid px-3 px-lg-4 py-4">
            <nav class="sf-breadcrumbs">
                <a href="{{ url('/') }}">Home</a>
                <span>&rsaquo;</span>
                Contact Us
            </nav>

            <div class="sf-detail-grid">
                <div class="sf-info-card">
                    <h1 class="h3 fw-bold mb-3">Contact Us</h1>
                    <form method="POST" action="{{ url('/contact-us') }}" class="row g-3" data-dirty-check>
                        @csrf
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" value="{{ old('name', auth()->user()->name ?? '') }}" class="form-control" required maxlength="120">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" value="{{ old('email', auth()->user()->email ?? '') }}" class="form-control" required maxlength="150">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" value="{{ old('phone') }}" class="form-control" maxlength="30">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Subject</label>
                            <input type="text" name="subject" value="{{ old('subject') }}" class="form-control" required maxlength="160">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Message</label>
                            <textarea name="message" rows="6" class="form-control" required maxlength="2000">{{ old('message') }}</textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-danger rounded-pill px-4">Submit</button>
                        </div>
                    </form>
                </div>

                <div class="sf-info-card">
                    <h4 class="mb-3">Customer Service</h4>
                    <dl class="sf-specs mb-0">
                        <dt>Email</dt><dd>support@expressbazaar.local</dd>
                        <dt>Address</dt><dd>73 Colby Street, Southampton, SO16 9RU, United Kingdom</dd>
                    </dl>
                </div>
            </div>
        </section>
    </main>
@endsection
