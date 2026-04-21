<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()->withCount('subcategories')->orderBy('name')->paginate(12);

        return view('admin.catalog.categories.index', [
            'pageTitle' => 'Categories | Admin | ExpressBazar',
            'categories' => $categories,
        ]);
    }

    public function create(): View
    {
        return view('admin.catalog.categories.create', [
            'pageTitle' => 'Create Category | Admin | ExpressBazar',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:categories,slug'],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        Category::create($data);

        return redirect()->route('admin.categories')->with('status', 'Category created successfully.');
    }

    public function edit(Category $category): View
    {
        return view('admin.catalog.categories.edit', [
            'pageTitle' => 'Edit Category | Admin | ExpressBazar',
            'category' => $category,
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:categories,slug,' . $category->id],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $category->update($data);

        return redirect()->route('admin.categories')->with('status', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return redirect()->route('admin.categories')->with('status', 'Category deleted successfully.');
    }
}
