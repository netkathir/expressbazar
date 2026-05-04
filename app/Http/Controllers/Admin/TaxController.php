<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaxController extends Controller
{
    public function index(Request $request)
    {
        $taxes = Tax::query()
            ->with('country')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->string('search'));
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('tax_name', 'like', "%{$search}%");
                });
                $this->prioritizePrefixSearch($query, ['tax_name'], $search);
            })
            ->when($request->filled('country_id'), fn ($query) => $query->where('country_id', $request->integer('country_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.taxes.index', [
            'title' => 'Tax Master',
            'activeMenu' => 'taxes',
            'taxes' => $taxes,
            'countries' => Country::orderBy('country_name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.taxes.form', [
            'title' => 'Add Tax',
            'activeMenu' => 'taxes',
            'tax' => new Tax(),
            'countries' => Country::orderBy('country_name')->get(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateTax($request);
        $data['created_by'] = $request->user()?->id;
        $data['updated_by'] = $request->user()?->id;

        Tax::create($data);

        return redirect()->route('admin.taxes.index')->with('success', 'Tax created successfully.');
    }

    public function edit(Tax $tax)
    {
        return view('admin.taxes.form', [
            'title' => 'Edit Tax',
            'activeMenu' => 'taxes',
            'tax' => $tax,
            'countries' => Country::orderBy('country_name')->get(),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Tax $tax)
    {
        $data = $this->validateTax($request, $tax);
        $data['updated_by'] = $request->user()?->id;

        $tax->update($data);

        return redirect()->route('admin.taxes.index')->with('success', 'Tax updated successfully.');
    }

    public function destroy(Tax $tax)
    {
        $this->deleteFromDatabase($tax);

        return redirect()->route('admin.taxes.index')->with('success', 'Tax deleted successfully.');
    }

    private function validateTax(Request $request, ?Tax $tax = null): array
    {
        return $request->validate([
            'tax_name' => ['required', 'string', 'max:255', Rule::unique('taxes', 'tax_name')->ignore($tax?->id)],
            'tax_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'country_id' => ['nullable', 'exists:countries,id'],
            'region_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
    }
}
