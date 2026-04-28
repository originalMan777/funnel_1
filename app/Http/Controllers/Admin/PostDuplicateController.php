<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\Blog\DuplicatePostService;
use App\Services\Logging\AdminActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PostDuplicateController extends Controller
{
    public function __construct(
        private readonly DuplicatePostService $duplicatePostService,
        private readonly AdminActivityLogger $adminLogger,
    ) {
    }

    public function __invoke(Request $request, Post $post): RedirectResponse
    {
        if (! $request->user()?->canManagePosts()) {
            abort(403);
        }

        $duplicate = $this->duplicatePostService->duplicate($post, (int) $request->user()->id);

        $this->adminLogger->info(
            event: 'post_duplicated',
            request: $request,
            entity: $duplicate,
            entityType: 'post',
            entityId: $duplicate->id,
            outcome: 'draft_created',
            context: [
                'source_post_id' => $post->id,
                'source_post_slug' => $post->slug,
                'duplicate_post_id' => $duplicate->id,
                'duplicate_post_slug' => $duplicate->slug,
            ],
        );

        return redirect()
            ->route('admin.posts.edit', $duplicate)
            ->with('success', 'Post duplicated as draft.');
    }
}
