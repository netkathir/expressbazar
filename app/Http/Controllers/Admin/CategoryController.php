<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->string('search'));
                $query->where('category_name', 'like', "%{$search}%")
                    ->orderByRaw('CASE WHEN category_name LIKE ? THEN 0 ELSE 1 END', [$search.'%'])
                    ->orderBy('category_name');
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.categories.index', [
            'title' => 'Category Master',
            'activeMenu' => 'categories',
            'categories' => $categories,
        ]);
    }

    public function create()
    {
        return view('admin.categories.form', [
            'title' => 'Add Category',
            'activeMenu' => 'categories',
            'category' => new Category(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateCategory($request);
        $data['created_by'] = $request->user()?->id;
        $data['updated_by'] = $request->user()?->id;

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->storeImage($request->file('image'));
        }

        Category::create($data);

        return redirect()->route('admin.categories.index')->with('success', 'Category created successfully.');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.form', [
            'title' => 'Edit Category',
            'activeMenu' => 'categories',
            'category' => $category,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $data = $this->validateCategory($request, $category);
        $data['updated_by'] = $request->user()?->id;

        if ($request->hasFile('image')) {
            $this->deleteImage($category->image_path);
            $data['image_path'] = $this->storeImage($request->file('image'));
        }

        $category->update($data);

        return redirect()->route('admin.categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        if ($category->subcategories()->withTrashed()->exists() || $category->products()->withTrashed()->exists()) {
            return back()->withErrors(['delete' => 'Category is mapped with subcategories/products and cannot be deleted.']);
        }

        $this->deleteImage($category->image_path);
        $this->deleteFromDatabase($category);

        return redirect()->route('admin.categories.index')->with('success', 'Category deleted successfully.');
    }

    private function validateCategory(Request $request, ?Category $category = null): array
    {
        $request->merge([
            'category_name' => trim((string) $request->input('category_name')),
        ]);

        return $request->validate([
            'category_name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^(?=.*[A-Za-z0-9])[A-Za-z0-9\s&.,\'()\-\/]+$/',
                Rule::unique('categories', 'category_name')->ignore($category?->id),
            ],
            'image' => ['nullable', 'image', 'max:2048'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ], [
            'category_name.regex' => 'Category name must include letters or numbers and cannot contain unsupported special characters.',
        ]);
    }

    private function storeImage($file): string
    {
        $directory = public_path('uploads/categories');

        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filename = uniqid('category_', true).'.'.$file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return 'uploads/categories/'.$filename;
    }

    private function deleteImage(?string $path): void
    {
        if ($path) {
            $fullPath = public_path($path);

            if (File::exists($fullPath)) {
                File::delete($fullPath);
            }
        }
    }
}
