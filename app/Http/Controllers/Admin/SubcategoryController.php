<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubcategoryController extends Controller
{
    public function index(): View
    {
        $subcategories = Subcategory::query()
            ->with('category')
            ->withCount('products')
            ->orderBy('name')
            ->paginate(12);

        return view('admin.catalog.subcategories.index', [
            'pageTitle' => 'Subcategories | Admin | ExpressBazar',
            'subcategories' => $subcategories,
        ]);
    }

    public function create(): View
    {
        return view('admin.catalog.subcategories.create', [
            'pageTitle' => 'Create Subcategory | Admin | ExpressBazar',
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:subcategories,slug'],
            'description' => ['nullable', 'string'],
        ]);

        Subcategory::create($data);

        return redirect()->route('admin.subcategories')->with('status', 'Subcategory created successfully.');
    }

    public function edit(Subcategory $subcategory): View
    {
        return view('admin.catalog.subcategories.edit', [
            'pageTitle' => 'Edit Subcategory | Admin | ExpressBazar',
            'subcategory' => $subcategory->load('category'),
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Subcategory $subcategory): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:subcategories,slug,' . $subcategory->id],
            'description' => ['nullable', 'string'],
        ]);

        $subcategory->update($data);

        return redirect()->route('admin.subcategories')->with('status', 'Subcategory updated successfully.');
    }

    public function destroy(Subcategory $subcategory): RedirectResponse
    {
        $subcategory->delete();

        return redirect()->route('admin.subcategories')->with('status', 'Subcategory deleted successfully.');
    }
}
