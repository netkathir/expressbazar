<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubcategoryController extends Controller
{
    public function index(Request $request)
    {
        $subcategories = Subcategory::query()
            ->with('category')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->string('search'));
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('subcategory_name', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.subcategories.index', [
            'title' => 'Subcategory Master',
            'activeMenu' => 'subcategories',
            'subcategories' => $subcategories,
            'categories' => Category::orderBy('category_name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.subcategories.form', [
            'title' => 'Add Subcategory',
            'activeMenu' => 'subcategories',
            'subcategory' => new Subcategory(),
            'categories' => Category::orderBy('category_name')->get(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateSubcategory($request);
        $data['created_by'] = $request->user()?->id;
        $data['updated_by'] = $request->user()?->id;

        Subcategory::create($data);

        return redirect()->route('admin.subcategories.index')->with('success', 'Subcategory created successfully.');
    }

    public function edit(Subcategory $subcategory)
    {
        return view('admin.subcategories.form', [
            'title' => 'Edit Subcategory',
            'activeMenu' => 'subcategories',
            'subcategory' => $subcategory,
            'categories' => Category::orderBy('category_name')->get(),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Subcategory $subcategory)
    {
        $data = $this->validateSubcategory($request, $subcategory);
        $data['updated_by'] = $request->user()?->id;

        $subcategory->update($data);

        return redirect()->route('admin.subcategories.index')->with('success', 'Subcategory updated successfully.');
    }

    public function destroy(Subcategory $subcategory)
    {
        $this->deleteFromDatabase($subcategory);

        return redirect()->route('admin.subcategories.index')->with('success', 'Subcategory deleted successfully.');
    }

    private function validateSubcategory(Request $request, ?Subcategory $subcategory = null): array
    {
        return $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'subcategory_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('subcategories', 'subcategory_name')
                    ->where(fn ($query) => $query->where('category_id', $request->integer('category_id')))
                    ->ignore($subcategory?->id),
            ],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
    }
}
