<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TagController extends Controller
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

        $tags = Tag::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'created_at', 'updated_at']);

        return Inertia::render('Admin/Tags/Index', [
            'tags' => $tags,
        ]);
    }

    public function store(Request $request)
    {
        $this->requireTaxonomyAccess($request);

        $validated = $this->validateTag($request);

        $baseSlug = ($validated['slug'] ?? '') !== ''
            ? (string) $validated['slug']
            : (string) $validated['name'];

        $validated['slug'] = $this->generateUniqueSlug($baseSlug);

        $tag = Tag::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
        ]);

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'tag' => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                ],
            ], 201);
        }

        return redirect()->route('admin.tags.index');
    }

    public function update(Request $request, Tag $tag)
    {
        $this->requireTaxonomyAccess($request);

        $validated = $this->validateTag($request);

        $baseSlug = ($validated['slug'] ?? '') !== ''
            ? (string) $validated['slug']
            : (string) $validated['name'];

        $validated['slug'] = $this->generateUniqueSlug($baseSlug, $tag->id);

        $tag->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
        ]);

        return redirect()->route('admin.tags.index');
    }

    public function destroy(Request $request, Tag $tag)
    {
        $this->requireTaxonomyAccess($request);

        $tag->delete();

        return redirect()->route('admin.tags.index');
    }

    private function validateTag(Request $request): array
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
            $base = 'tag';
        }

        $existing = Tag::query()
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
