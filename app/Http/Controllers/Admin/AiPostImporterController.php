<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Blog\AiPostImportService;
use App\Services\Security\SecurityAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AiPostImporterController extends Controller
{
    public function __construct(
        private readonly SecurityAuditLogger $securityAuditLogger,
    ) {
    }

    private function requireAiImportAccess(Request $request): void
    {
        if (! $request->user()?->canImportAiPosts()) {
            abort(403);
        }
    }

    public function index(Request $request): Response
    {
        $this->requireAiImportAccess($request);

        return Inertia::render('Admin/PostImporter/Index');
    }

    public function store(Request $request, AiPostImportService $importer): RedirectResponse
    {
        $this->requireAiImportAccess($request);

        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $validated = $request->validate([
            'package' => [
                'required',
                'string',
                'max:100000',
            ],
        ]);

        $package = trim($validated['package']);

        if ($package === '') {
            throw ValidationException::withMessages([
                'package' => 'Package cannot be empty.',
            ]);
        }

        if (! Str::contains($package, ['TITLE:', 'ARTICLE:', 'LIST:'])) {
            throw ValidationException::withMessages([
                'package' => 'Invalid AI package structure.',
            ]);
        }

        $this->enforceRateLimit($request);

        try {
            $post = $importer->import($package, $user);

            $this->securityAuditLogger->log(
                event: 'ai_post_imported',
                request: $request,
                userId: (int) $user->id,
                entityType: 'post',
                entityId: (int) $post->id,
                context: [
                    'post_slug' => $post->slug,
                    'payload_length' => mb_strlen($package),
                ]
            );
        } catch (\Throwable $e) {
            $this->securityAuditLogger->log(
                event: 'ai_post_import_failed',
                request: $request,
                userId: (int) $user->id,
                entityType: 'ai_post_import',
                entityId: null,
                context: [
                    'payload_length' => mb_strlen($package),
                    'error' => $e->getMessage(),
                ]
            );

            throw ValidationException::withMessages([
                'package' => 'Failed to process AI package.',
            ]);
        }

        return redirect()
            ->route('admin.posts.edit', $post)
            ->with('success', 'Post imported as draft.');
    }

    private function enforceRateLimit(Request $request): void
    {
        $key = 'ai_import:' . ($request->user()?->id ?? 'guest') . ':' . $request->ip();

        $attempts = cache()->get($key, 0);

        if ($attempts >= 10) {
            abort(429, 'Too many import attempts. Slow down.');
        }

        cache()->put($key, $attempts + 1, now()->addMinutes(1));
    }
}
