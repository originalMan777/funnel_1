<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BlogDummyContentSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::query()->first();

        if (!$author) {
            $author = User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }

        $categories = collect([
            ['name' => 'Marketing', 'slug' => 'marketing'],
            ['name' => 'Branding', 'slug' => 'branding'],
            ['name' => 'Real Estate', 'slug' => 'real-estate'],
            ['name' => 'Social Media', 'slug' => 'social-media'],
            ['name' => 'Strategy', 'slug' => 'strategy'],
            ['name' => 'Design', 'slug' => 'design'],
        ])->mapWithKeys(function (array $category): array {
            $model = Category::query()->firstOrCreate(
                ['slug' => $category['slug']],
                ['name' => $category['name']]
            );

            return [$category['slug'] => $model];
        });

        $tags = collect([
            ['name' => 'Audience Growth', 'slug' => 'audience-growth'],
            ['name' => 'Lead Generation', 'slug' => 'lead-generation'],
            ['name' => 'Brand Strategy', 'slug' => 'brand-strategy'],
            ['name' => 'Content Planning', 'slug' => 'content-planning'],
            ['name' => 'Home Sellers', 'slug' => 'home-sellers'],
            ['name' => 'Buyer Journey', 'slug' => 'buyer-journey'],
            ['name' => 'Website UX', 'slug' => 'website-ux'],
            ['name' => 'Local Marketing', 'slug' => 'local-marketing'],
            ['name' => 'Social Proof', 'slug' => 'social-proof'],
            ['name' => 'Visual Identity', 'slug' => 'visual-identity'],
        ])->mapWithKeys(function (array $tag): array {
            $model = Tag::query()->firstOrCreate(
                ['slug' => $tag['slug']],
                ['name' => $tag['name']]
            );

            return [$tag['slug'] => $model];
        });

        $imagePaths = collect(File::files(public_path('images/blog')))
            ->filter(fn ($file) => in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp'], true))
            ->map(fn ($file) => '/images/blog/' . $file->getFilename())
            ->values();

        $posts = [
            [
                'title' => 'Why Most Service Websites Feel Invisible',
                'category' => 'marketing',
                'tags' => ['lead-generation', 'website-ux'],
                'angle' => 'Most websites fail quietly. The design may look acceptable, but the messaging, hierarchy, and calls to action never actually pull a visitor toward the next step.',
            ],
            [
                'title' => 'The First Impression Problem in Local Business Branding',
                'category' => 'branding',
                'tags' => ['visual-identity', 'brand-strategy'],
                'angle' => 'People judge a business long before they read the details. The first impression is built by structure, confidence, and how clearly the brand presents itself.',
            ],
            [
                'title' => 'What Home Sellers Need to See Before They Trust You',
                'category' => 'real-estate',
                'tags' => ['home-sellers', 'social-proof'],
                'angle' => 'Sellers want clarity, reassurance, and proof that you understand the process. They do not want a vague promise and a smiling headshot doing all the work.',
            ],
            [
                'title' => 'Why Posting More Is Not the Same as Growing',
                'category' => 'social-media',
                'tags' => ['audience-growth', 'content-planning'],
                'angle' => 'A busy posting schedule can create activity without creating momentum. Growth usually comes from sharper positioning, stronger hooks, and better sequencing.',
            ],
            [
                'title' => 'The Quiet Cost of Weak Calls to Action',
                'category' => 'strategy',
                'tags' => ['lead-generation', 'brand-strategy'],
                'angle' => 'When the call to action is soft, unclear, or hidden, the whole page loses force. Visitors may stay interested yet still leave without acting.',
            ],
            [
                'title' => 'How Better Layout Choices Build More Trust',
                'category' => 'design',
                'tags' => ['website-ux', 'visual-identity'],
                'angle' => 'Trust is often a layout decision before it becomes a copy decision. Strong spacing, rhythm, and visual order make a business feel more established.',
            ],
            [
                'title' => 'What Makes a Brand Feel Premium Instead of Busy',
                'category' => 'branding',
                'tags' => ['visual-identity', 'brand-strategy'],
                'angle' => 'Premium brands usually feel edited, intentional, and controlled. Busy brands feel like every idea was allowed into the room at the same time.',
            ],
            [
                'title' => 'The Blog Advantage Most Small Businesses Ignore',
                'category' => 'marketing',
                'tags' => ['content-planning', 'audience-growth'],
                'angle' => 'A blog can quietly build authority, answer objections, and create search visibility, but only if the topics are chosen strategically and written with purpose.',
            ],
            [
                'title' => 'How to Make a Buyer Journey Feel Simpler Online',
                'category' => 'real-estate',
                'tags' => ['buyer-journey', 'website-ux'],
                'angle' => 'Confusion kills momentum. When the online journey is broken into clear steps, buyers feel less overwhelmed and more willing to move forward.',
            ],
            [
                'title' => 'Why Consistency Beats Random Creative Bursts',
                'category' => 'strategy',
                'tags' => ['content-planning', 'brand-strategy'],
                'angle' => 'Creative spikes can win attention, but consistency builds memory. The businesses that look strongest usually repeat the right signals over time.',
            ],
            [
                'title' => 'The Difference Between Looking Expensive and Looking Solid',
                'category' => 'design',
                'tags' => ['visual-identity', 'website-ux'],
                'angle' => 'Not every strong brand needs luxury language. Sometimes the real goal is to look stable, capable, and worth taking seriously right away.',
            ],
            [
                'title' => 'What Social Proof Should Actually Do on a Website',
                'category' => 'marketing',
                'tags' => ['social-proof', 'lead-generation'],
                'angle' => 'Testimonials are not decoration. They should remove friction, answer doubt, and support the exact promises the rest of the page is making.',
            ],
            [
                'title' => 'How Real Estate Content Can Attract Better Leads',
                'category' => 'real-estate',
                'tags' => ['home-sellers', 'content-planning'],
                'angle' => 'The best real estate content does more than fill a feed. It meets people at moments of uncertainty and gives them a reason to trust your process.',
            ],
            [
                'title' => 'Why a Brand Message Needs a Stronger Spine',
                'category' => 'branding',
                'tags' => ['brand-strategy', 'lead-generation'],
                'angle' => 'If the message bends in too many directions, the brand becomes forgettable. A stronger spine makes every page, post, and offer hit harder.',
            ],
            [
                'title' => 'The Local Marketing Mistake That Keeps Repeating',
                'category' => 'marketing',
                'tags' => ['local-marketing', 'audience-growth'],
                'angle' => 'Many local businesses try to out-volume the problem when they really need to out-clarify it. Better targeting often beats more noise.',
            ],
            [
                'title' => 'How to Give a Service Page More Authority Fast',
                'category' => 'strategy',
                'tags' => ['website-ux', 'social-proof'],
                'angle' => 'Authority grows when the page anticipates questions and answers them clearly. The layout, proof, and sequencing all need to work together.',
            ],
            [
                'title' => 'What Better Visual Identity Does for Conversion',
                'category' => 'design',
                'tags' => ['visual-identity', 'lead-generation'],
                'angle' => 'Visual identity is not just style. It changes how fast people trust the brand and whether they believe the offer is worth the next click.',
            ],
            [
                'title' => 'Why Content Planning Should Start With Pain Points',
                'category' => 'social-media',
                'tags' => ['content-planning', 'audience-growth'],
                'angle' => 'Content gets sharper when it starts with real friction. Pain points create angles, and angles give the content a reason to exist.',
            ],
            [
                'title' => 'How to Make a Brand Feel Clearer in One Pass',
                'category' => 'branding',
                'tags' => ['visual-identity', 'brand-strategy'],
                'angle' => 'A clearer brand usually comes from subtraction. When the strongest signals are kept and the weak ones are removed, the whole system improves.',
            ],
            [
                'title' => 'The Homepage Question Every Visitor Is Asking',
                'category' => 'marketing',
                'tags' => ['website-ux', 'lead-generation'],
                'angle' => 'Visitors may not say it out loud, but they are always asking the same thing: is this for me, and should I trust it enough to keep going?',
            ],
        ];

            $expandedPosts = [];

            for ($i = 0; $i < 2; $i++) {
                foreach ($posts as $post) {
                    $expandedPosts[] = $post;
                }
            }

            foreach ($expandedPosts as $index => $definition) {
            $title = $definition['title'];
            $slug = Str::slug($title);
            $category = $categories[$definition['category']];
            $publishedAt = Carbon::now()->subDays(20 - $index)->setTime(9 + ($index % 6), 15);
            $imagePath = $imagePaths->isNotEmpty()
                ? $imagePaths[$index % $imagePaths->count()]
                : null;

            $content = $this->buildContent($title, $definition['angle'], $category->name);
            $excerpt = Str::limit(strip_tags($content), 180, '...');

            $post = Post::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $title,
                    'excerpt' => $excerpt,
                    'content' => $content,
                    'status' => Post::STATUS_PUBLISHED,
                    'published_at' => $publishedAt,
                    'meta_title' => $title,
                    'meta_description' => Str::limit($excerpt, 155, '...'),
                    'canonical_url' => null,
                    'og_title' => $title,
                    'og_description' => Str::limit($excerpt, 155, '...'),
                    'og_image_path' => $imagePath,
                    'featured_image_path' => $imagePath,
                    'category_id' => $category->id,
                    'created_by' => $author->id,
                    'updated_by' => $author->id,
                    'noindex' => false,
                    'sources' => null,
                ]
            );

            $tagIds = collect($definition['tags'])
                ->map(fn (string $slug) => $tags[$slug]->id)
                ->values()
                ->all();

            $post->tags()->sync($tagIds);
        }
    }

    private function buildContent(string $title, string $angle, string $categoryName): string
{
    $paragraph = fn($text) => "<p>{$text}</p>";

    $content = "";

    // 🔥 INTRO
    $content .= $paragraph($angle);

    // 🔥 LONG BODY (THIS FIXES EVERYTHING)
    for ($i = 0; $i < 14; $i++) {
        $content .= $paragraph(
            "In the {$categoryName} space, clarity is one of the biggest advantages a business can have.
            {$title} highlights how structure, positioning, and communication all come together to shape how a visitor experiences a page.

            When content is intentional, it becomes easier for users to follow, easier to trust, and easier to act on.
            This is especially important when testing systems that rely on content depth, such as inline lead slots and conversion triggers.

            Most businesses underestimate sequencing. The order of ideas, the pacing of content, and how information unfolds
            directly impact engagement. Strong content is not just informative—it is directional.

            This article is intentionally extended to simulate real-world blog behavior. It ensures that mid-content systems like
            lead slots, inline offers, and conversion triggers activate properly.

            {$categoryName} content benefits from repetition with variation. Reinforcing ideas from different angles creates
            understanding, and understanding drives action.

            This paragraph helps simulate scroll depth, ensuring that your layout, spacing, and slot injection logic can be tested accurately."
        );
    }

    // 🔥 OUTRO
    $content .= $paragraph(
        "Ultimately, {$title} is part of a larger system. When content is structured properly, it does more than inform—it guides, positions, and converts."
    );

    return $content;
}
}
