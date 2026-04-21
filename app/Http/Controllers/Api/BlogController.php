<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Traits\LogsActivity;

class BlogController extends Controller
{
    use LogsActivity;
    // ─── PUBLIC ENDPOINTS ────────────────────────────────────────────

    /**
     * GET /api/public/blog - paginated published posts.
     *
     * Response matches the old Node backend shape so the business site can
     * migrate cleanly:  { data: BlogPost[], pagination: {page, limit, total, pages} }
     * Accepts `limit` (alias for per_page) and `page` for parity.
     */
    public function publicIndex(Request $request): JsonResponse
    {
        $perPage = (int) ($request->input('limit') ?? $request->input('per_page', 12));
        $perPage = max(1, min($perPage, 50));

        $query = BlogPost::published()
            ->with(['category', 'tags', 'author:id,name,avatar'])
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at');

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->input('category')));
        }
        if ($request->filled('tag')) {
            $query->whereHas('tags', fn ($q) => $q->where('slug', $request->input('tag')));
        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => $paginator->items(),
            'pagination' => [
                'page'  => $paginator->currentPage(),
                'limit' => $paginator->perPage(),
                'total' => $paginator->total(),
                'pages' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/public/blog/{slug} - single published post.
     * Returns { data: BlogPost } to match the old backend's envelope.
     */
    public function publicShow(string $slug): JsonResponse
    {
        $post = BlogPost::published()
            ->with(['category', 'tags', 'author:id,name,avatar'])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json(['data' => $post]);
    }

    /**
     * GET /api/public/blog/featured?limit=3 - featured published posts.
     */
    public function publicFeatured(Request $request): JsonResponse
    {
        $limit = max(1, min((int) $request->input('limit', 3), 20));

        $posts = BlogPost::published()
            ->featured()
            ->with(['category', 'tags', 'author:id,name,avatar'])
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();

        return response()->json(['data' => $posts]);
    }

    /**
     * GET /api/public/blog/popular?limit=5 - most-viewed published posts.
     */
    public function publicPopular(Request $request): JsonResponse
    {
        $limit = max(1, min((int) $request->input('limit', 5), 20));

        $posts = BlogPost::published()
            ->with(['category', 'tags', 'author:id,name,avatar'])
            ->orderByDesc('views')
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();

        return response()->json(['data' => $posts]);
    }

    /**
     * GET /api/public/blog/{slug}/related?limit=3 - other posts in the same category.
     */
    public function publicRelated(Request $request, string $slug): JsonResponse
    {
        $limit = max(1, min((int) $request->input('limit', 3), 10));

        $post = BlogPost::published()->where('slug', $slug)->firstOrFail();

        $query = BlogPost::published()
            ->with(['category', 'tags', 'author:id,name,avatar'])
            ->where('id', '!=', $post->id);

        if ($post->category_id) {
            $query->where('category_id', $post->category_id);
        }

        $related = $query->orderByDesc('published_at')->limit($limit)->get();

        // Backfill with any published posts if category yielded too few.
        if ($related->count() < $limit) {
            $fill = BlogPost::published()
                ->with(['category', 'tags', 'author:id,name,avatar'])
                ->where('id', '!=', $post->id)
                ->whereNotIn('id', $related->pluck('id'))
                ->orderByDesc('published_at')
                ->limit($limit - $related->count())
                ->get();
            $related = $related->concat($fill);
        }

        return response()->json(['data' => $related]);
    }

    /**
     * GET /api/public/blog/{slug}/adjacent - previous and next post by publishedAt.
     */
    public function publicAdjacent(string $slug): JsonResponse
    {
        $post = BlogPost::published()->where('slug', $slug)->firstOrFail();

        $previous = BlogPost::published()
            ->with(['category:id,name,slug'])
            ->where('published_at', '<', $post->published_at)
            ->orderByDesc('published_at')
            ->first();

        $next = BlogPost::published()
            ->with(['category:id,name,slug'])
            ->where('published_at', '>', $post->published_at)
            ->orderBy('published_at')
            ->first();

        return response()->json([
            'data' => [
                'previous' => $previous,
                'next'     => $next,
            ],
        ]);
    }

    /**
     * POST /api/public/blog/{slug}/view - fire-and-forget view counter.
     */
    public function publicIncrementView(string $slug): JsonResponse
    {
        BlogPost::published()->where('slug', $slug)->increment('views');

        return response()->json(['message' => 'ok']);
    }

    /**
     * GET /api/public/blog/categories - active categories with post counts.
     */
    public function publicCategories(): JsonResponse
    {
        $categories = Category::active()
            ->ordered()
            ->withCount(['posts' => fn ($q) => $q->published()])
            ->get();

        return response()->json($categories);
    }

    /**
     * GET /api/public/blog/tags - tags with post counts.
     */
    public function publicTags(): JsonResponse
    {
        $tags = Tag::withCount(['posts' => fn ($q) => $q->published()])
            ->orderBy('name')
            ->get();

        return response()->json($tags);
    }

    // ─── ADMIN: CATEGORIES ──────────────────────────────────────────

    /**
     * GET /api/blog/categories - all categories (including inactive).
     */
    public function categories(Request $request): JsonResponse
    {
        $query = Category::ordered()->withCount('posts');

        if ($search = $request->input('search')) {
            $query->where('name', 'ilike', "%{$search}%");
        }

        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'sort_order');
        $sortDir = in_array($request->input('sort_dir'), ['asc', 'desc']) ? $request->input('sort_dir') : 'asc';

        if (in_array($sortBy, ['name', 'sort_order', 'created_at', 'posts_count'])) {
            $query->reorder($sortBy, $sortDir);
        }

        $categories = $query->paginate($perPage);

        return response()->json(['categories' => $categories]);
    }

    /**
     * POST /api/blog/categories - create category.
     */
    public function storeCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'slug'        => 'nullable|string|max:255|unique:categories,slug',
            'description' => 'nullable|string',
            'is_active'   => 'nullable|boolean',
            'sort_order'  => 'nullable|integer',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = $this->uniqueSlug($validated['name'], 'categories');
        }

        $category = Category::create($validated);

        $this->logActivity('category_created', "Category \"{$category->name}\" was created.");

        return response()->json($category, 201);
    }

    /**
     * PUT /api/blog/categories/{id} - update category.
     */
    public function updateCategory(Request $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'sometimes|required|string|max:255',
            'slug'        => ['nullable', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($category->id)],
            'description' => 'nullable|string',
            'is_active'   => 'nullable|boolean',
            'sort_order'  => 'nullable|integer',
        ]);

        $category->update($validated);

        $this->logActivity('category_updated', "Category \"{$category->name}\" was updated.");

        return response()->json($category);
    }

    /**
     * DELETE /api/blog/categories/{id} - delete category (reassign posts to null).
     */
    public function destroyCategory(int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        // Reassign posts to null
        BlogPost::where('category_id', $category->id)->update(['category_id' => null]);

        $this->logActivity('category_deleted', "Category \"{$category->name}\" was deleted.");

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully.']);
    }

    // ─── ADMIN: TAGS ────────────────────────────────────────────────

    /**
     * GET /api/blog/tags - all tags.
     */
    public function tags(Request $request): JsonResponse
    {
        $query = Tag::withCount('posts');

        if ($search = $request->input('search')) {
            $query->where('name', 'ilike', "%{$search}%");
        }

        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'name');
        $sortDir = in_array($request->input('sort_dir'), ['asc', 'desc']) ? $request->input('sort_dir') : 'asc';

        if (in_array($sortBy, ['name', 'created_at', 'posts_count'])) {
            $query->reorder($sortBy, $sortDir);
        }

        $tags = $query->paginate($perPage);

        return response()->json(['tags' => $tags]);
    }

    /**
     * POST /api/blog/tags - create tag (or find existing by name).
     */
    public function storeTag(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:tags,slug',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = $this->uniqueSlug($validated['name'], 'tags');
        }

        $tag = Tag::firstOrCreate(
            ['name' => $validated['name']],
            $validated
        );

        $this->logActivity('tag_created', "Tag \"{$tag->name}\" was created.");

        return response()->json($tag, 201);
    }

    /**
     * PUT /api/blog/tags/{id} - update tag.
     */
    public function updateTag(Request $request, int $id): JsonResponse
    {
        $tag = Tag::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('tags', 'slug')->ignore($tag->id)],
        ]);

        $tag->update($validated);

        $this->logActivity('tag_updated', "Tag \"{$tag->name}\" was updated.");

        return response()->json($tag);
    }

    /**
     * DELETE /api/blog/tags/{id} - delete tag (detach from posts).
     */
    public function destroyTag(int $id): JsonResponse
    {
        $tag = Tag::findOrFail($id);
        $tag->posts()->detach();

        $this->logActivity('tag_deleted', "Tag \"{$tag->name}\" was deleted.");

        $tag->delete();

        return response()->json(['message' => 'Tag deleted successfully.']);
    }

    // ─── ADMIN: POSTS ───────────────────────────────────────────────

    /**
     * GET /api/blog/posts - all posts (any status), paginated.
     */
    public function posts(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 15), 50);
        $sortBy  = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');

        // Whitelist sortable columns
        $allowedSorts = ['title', 'created_at', 'updated_at', 'published_at', 'status'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

        $query = BlogPost::with(['author:id,name,avatar', 'category', 'tags'])
            ->withCount('tags');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by category_id
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        $posts = $query->orderBy($sortBy, $sortDir)->paginate($perPage);

        // Append status_badge accessor
        $posts->getCollection()->transform(function ($post) {
            $post->append('status_badge');
            return $post;
        });

        return response()->json($posts);
    }

    /**
     * GET /api/blog/posts/{id} - show single post for editing.
     */
    public function showPost($id): JsonResponse
    {
        $post = BlogPost::with(['category', 'tags', 'author:id,name,email'])->findOrFail($id);

        return response()->json(['post' => $post]);
    }

    /**
     * POST /api/blog/posts - create post.
     */
    public function storePost(Request $request): JsonResponse
    {
        $validated = $this->validatePost($request);

        // Auto-generate slug
        if (empty($validated['slug'])) {
            $validated['slug'] = $this->uniqueSlug($validated['title'], 'blog_posts');
        }

        // Auto-calculate reading_time
        if (empty($validated['reading_time']) && !empty($validated['content'])) {
            $validated['reading_time'] = $this->calculateReadingTime($validated['content']);
        }

        // Set author
        $validated['author_id'] = $request->user()->id;

        // RBAC: only users with blog.publish may create a post in a
        // published or scheduled state. `blog.create` alone cannot
        // side-step the dedicated publish right.
        $requestedStatus = $validated['status'] ?? 'draft';
        if (in_array($requestedStatus, ['published', 'scheduled'], true)
            && ! $request->user()?->hasPermission('blog.publish')) {
            return response()->json([
                'message' => 'You do not have permission to publish or schedule blog posts.',
            ], 403);
        }

        // If publishing now, set published_at
        if (($validated['status'] ?? 'draft') === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        // Extract tag_ids before creating post
        $tagIds = $validated['tag_ids'] ?? [];
        unset($validated['tag_ids']);

        $post = BlogPost::create($validated);

        if (!empty($tagIds)) {
            $post->tags()->sync($tagIds);
        }

        $post->load(['author:id,name,avatar', 'category', 'tags']);

        $this->logActivity('post_created', "Blog post \"{$post->title}\" was created.");

        return response()->json($post, 201);
    }

    /**
     * PUT /api/blog/posts/{id} - update post.
     */
    public function updatePost(Request $request, int $id): JsonResponse
    {
        $post = BlogPost::findOrFail($id);

        $validated = $this->validatePost($request, $id);

        // Auto-generate slug if explicitly set to empty
        if (array_key_exists('slug', $validated) && empty($validated['slug'])) {
            $validated['slug'] = $this->uniqueSlug($validated['title'] ?? $post->title, 'blog_posts', $id);
        }

        // Auto-calculate reading_time if content changed
        if (!empty($validated['content']) && empty($validated['reading_time'])) {
            $validated['reading_time'] = $this->calculateReadingTime($validated['content']);
        }

        // RBAC: only users with blog.publish may push a post into or out
        // of a published/scheduled state. Users with blog.edit alone can
        // still save content changes against an already-draft post.
        if (array_key_exists('status', $validated)
            && ! $request->user()?->hasPermission('blog.publish')) {
            $currentStatus = $post->status;
            $nextStatus = $validated['status'];
            $touchesPublish = in_array($currentStatus, ['published', 'scheduled'], true)
                || in_array($nextStatus, ['published', 'scheduled'], true);
            if ($touchesPublish && $currentStatus !== $nextStatus) {
                return response()->json([
                    'message' => 'You do not have permission to change the publish status of blog posts.',
                ], 403);
            }
        }

        // If publishing now, set published_at
        if (($validated['status'] ?? null) === 'published' && empty($validated['published_at']) && !$post->published_at) {
            $validated['published_at'] = now();
        }

        // Handle tag_ids
        $tagIds = $validated['tag_ids'] ?? null;
        unset($validated['tag_ids']);

        $post->update($validated);

        if ($tagIds !== null) {
            $post->tags()->sync($tagIds);
        }

        $post->load(['author:id,name,avatar', 'category', 'tags']);

        $this->logActivity('post_updated', "Blog post \"{$post->title}\" was updated.");

        return response()->json($post);
    }

    /**
     * DELETE /api/blog/posts/{id} - delete post.
     */
    public function destroyPost(int $id): JsonResponse
    {
        $post = BlogPost::findOrFail($id);
        $post->tags()->detach();

        $this->logActivity('post_deleted', "Blog post \"{$post->title}\" was deleted.");

        $post->delete();

        return response()->json(['message' => 'Post deleted successfully.']);
    }

    /**
     * PUT /api/blog/posts/{id}/publish - publish immediately.
     */
    public function publish(int $id): JsonResponse
    {
        $post = BlogPost::findOrFail($id);
        $post->update([
            'status'       => 'published',
            'published_at' => now(),
        ]);

        $post->load(['author:id,name,avatar', 'category', 'tags']);

        $this->logActivity('post_published', "Blog post \"{$post->title}\" was published.");

        return response()->json($post);
    }

    /**
     * PUT /api/blog/posts/{id}/unpublish - revert to draft.
     */
    public function unpublish(int $id): JsonResponse
    {
        $post = BlogPost::findOrFail($id);
        $post->update([
            'status' => 'draft',
        ]);

        $post->load(['author:id,name,avatar', 'category', 'tags']);

        $this->logActivity('post_unpublished', "Blog post \"{$post->title}\" was unpublished.");

        return response()->json($post);
    }

    /**
     * PUT /api/blog/posts/{id}/schedule - schedule for future publish.
     */
    public function schedule(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'scheduled_at' => 'required|date|after:now',
        ]);

        $post = BlogPost::findOrFail($id);
        $post->update([
            'status'       => 'scheduled',
            'scheduled_at' => $request->input('scheduled_at'),
        ]);

        $post->load(['author:id,name,avatar', 'category', 'tags']);

        return response()->json($post);
    }

    // ─── HELPERS ────────────────────────────────────────────────────

    /**
     * Validate post request.
     */
    private function validatePost(Request $request, ?int $id = null): array
    {
        $rules = [
            'title'            => 'required|string|max:255',
            'slug'             => ['nullable', 'string', 'max:255', Rule::unique('blog_posts', 'slug')->ignore($id)],
            'excerpt'          => 'nullable|string|max:500',
            'content'          => 'required|string',
            'featured_image'   => 'nullable|string|max:500',
            'category_id'      => 'nullable|exists:categories,id',
            'tag_ids'          => 'nullable|array',
            'tag_ids.*'        => 'exists:tags,id',
            'status'           => 'nullable|in:draft,published,scheduled,archived',
            'published_at'     => 'nullable|date',
            'scheduled_at'     => 'nullable|date|after:now',
            'is_featured'      => 'nullable|boolean',
            'reading_time'     => 'nullable|integer',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords'    => 'nullable|array',
            'meta_keywords.*'  => 'string|max:100',
            'og_title'         => 'nullable|string|max:255',
            'og_description'   => 'nullable|string|max:500',
            'og_image'         => 'nullable|string|max:500',
            'json_ld_schema'   => 'nullable',
            'key_insights'     => 'nullable|array|max:6',
            'key_insights.*'   => 'string|max:500',
        ];

        // scheduled_at required only when status = scheduled
        if ($request->input('status') === 'scheduled') {
            $rules['scheduled_at'] = 'required|date|after:now';
        }

        // On update, make title and content optional
        if ($id !== null) {
            $rules['title']   = 'sometimes|required|string|max:255';
            $rules['content'] = 'sometimes|required|string';
        }

        return $request->validate($rules);
    }

    /**
     * Generate a unique slug for a given table.
     */
    private function uniqueSlug(string $title, string $table, ?int $ignoreId = null): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $counter = 2;

        while (true) {
            $query = \DB::table($table)->where('slug', $slug);
            if ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }
            if (!$query->exists()) {
                break;
            }
            $slug = $original . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Calculate reading time in minutes from HTML content.
     */
    private function calculateReadingTime(string $content): int
    {
        $wordCount = str_word_count(strip_tags($content));
        return max(1, (int) ceil($wordCount / 200));
    }
}
