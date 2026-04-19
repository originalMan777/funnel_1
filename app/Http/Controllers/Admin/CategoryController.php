<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CategoryController extends Controller
{
    private function requireTaxonomyAccess(Request $request): void
    {
        if (! $request->user()?->canManageTaxonomy()) {
            abort(403);
        }
    }

    public function index(Request $request)
    {
        $this->requireTaxonomyAccess($request);

        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'created_at', 'updated_at']);

        return Inertia::render('Admin/Categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $this->requireTaxonomyAccess($request);

        $validated = $this->validateCategory($request);

        $baseSlug = ($validated['slug'] ?? '') !== ''
            ? (string) $validated['slug']
            : (string) $validated['name'];

        $validated['slug'] = $this->generateUniqueSlug($baseSlug);

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
        ]);

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ],
            ], 201);
        }

        return redirect()->route('admin.categories.index');
    }

    public function update(Request $request, Category $category)
    {
        $this->requireTaxonomyAccess($request);

        $validated = $this->validateCategory($request);

        $baseSlug = ($validated['slug'] ?? '') !== ''
            ? (string) $validated['slug']
            : (string) $validated['name'];

        $validated['slug'] = $this->generateUniqueSlug($baseSlug, $category->id);

        $category->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
        ]);

        return redirect()->route('admin.categories.index');
    }

    public function destroy(Request $request, Category $category)
    {
        $this->requireTaxonomyAccess($request);

        $category->delete();

        return redirect()->route('admin.categories.index');
    }

    private function validateCategory(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function generateUniqueSlug(string $input, ?int $ignoreId = null): string
    {
        $base = Str::slug($input);
        if ($base === '') {
            $base = 'category';
        }

        $existing = Category::query()
            ->when($ignoreId !== null, fn ($q) => $q->whereKeyNot($ignoreId))
            ->where(function ($q) use ($base) {
                $q->where('slug', $base)->orWhere('slug', 'like', $base . '-%');
            })
            ->pluck('slug')
            ->all();

        if (! in_array($base, $existing, true)) {
            return $base;
        }

        $i = 2;
        while (in_array($base . '-' . $i, $existing, true)) {
            $i++;
        }

        return $base . '-' . $i;
    }
}
