<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactInquiry;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ContactInquiryController extends Controller
{
    public function index(Request $request)
    {
        if (! Schema::hasTable('contact_inquiries')) {
            return view('admin.contacts.index', [
                'title' => 'Contact Inquiries',
                'activeMenu' => 'contacts',
                'inquiries' => new LengthAwarePaginator([], 0, 15, $request->integer('page', 1), [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]),
                'contactTableMissing' => true,
            ]);
        }

        $inquiries = ContactInquiry::query()
            ->with('user')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->string('search'));

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.contacts.index', [
            'title' => 'Contact Inquiries',
            'activeMenu' => 'contacts',
            'inquiries' => $inquiries,
            'contactTableMissing' => false,
        ]);
    }

    public function show(ContactInquiry $contact)
    {
        if (! $contact->read_at) {
            $contact->update([
                'status' => 'read',
                'read_at' => now(),
            ]);
        }

        $contact->loadMissing('user');

        return view('admin.contacts.show', [
            'title' => 'Contact Inquiry Details',
            'activeMenu' => 'contacts',
            'contact' => $contact,
        ]);
    }

    public function update(Request $request, ContactInquiry $contact)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['new', 'read'])],
        ]);

        $contact->update([
            'status' => $data['status'],
            'read_at' => $data['status'] === 'read' ? ($contact->read_at ?: now()) : null,
        ]);

        return back()->with('success', 'Contact inquiry status updated.');
    }

    public function destroy(Request $request, ContactInquiry $contact)
    {
        $contact->delete();

        return $this->redirectToIndex($request, 'admin.contacts.index', 'Contact inquiry deleted successfully.');
    }
}
