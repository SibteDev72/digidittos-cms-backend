<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Traits\LogsActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    use LogsActivity;

    // ─── PUBLIC ENDPOINTS ─────────────────────────────────────────────

    /**
     * GET /api/public/projects - paginated published projects.
     *
     * Response matches the old Node backend so the business site can consume
     * it unchanged: { data: Project[], pagination: {page, limit, total, pages} }.
     */
    public function publicIndex(Request $request): JsonResponse
    {
        $perPage = (int) ($request->input('limit') ?? $request->input('per_page', 12));
        $perPage = max(1, min($perPage, 50));

        $query = Project::published()
            ->with('author:id,name,avatar')
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at');

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }
        if ($request->filled('tag')) {
            $tag = $request->input('tag');
            $query->whereJsonContains('tags', $tag);
        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%")
                  ->orWhere('client', 'like', "%{$search}%");
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
     * GET /api/public/projects/{slug} - single published project.
     */
    public function publicShow(string $slug): JsonResponse
    {
        $project = Project::published()
            ->with('author:id,name,avatar')
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json(['data' => $project]);
    }

    /**
     * GET /api/public/projects/categories - distinct categories in use.
     */
    public function publicCategories(): JsonResponse
    {
        $categories = Project::published()
            ->whereNotNull('category')
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->orderBy('category')
            ->get();

        return response()->json(['data' => $categories]);
    }

    /**
     * GET /api/public/projects/tags - aggregated tag frequencies.
     */
    public function publicTags(): JsonResponse
    {
        $counts = [];
        Project::published()
            ->whereNotNull('tags')
            ->get(['tags'])
            ->each(function ($row) use (&$counts) {
                foreach ((array) $row->tags as $t) {
                    $key = is_string($t) ? $t : ($t['slug'] ?? null);
                    if (! $key) continue;
                    $counts[$key] = ($counts[$key] ?? 0) + 1;
                }
            });

        $data = collect($counts)
            ->map(fn ($count, $name) => ['name' => $name, 'count' => $count])
            ->sortByDesc('count')
            ->values();

        return response()->json(['data' => $data]);
    }

    // ─── ADMIN ENDPOINTS ──────────────────────────────────────────────

    /**
     * GET /api/projects - all projects (any status), paginated with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 15), 50);
        $sortBy  = $request->input('sort_by', 'created_at');
        $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowed = ['title', 'status', 'created_at', 'updated_at', 'published_at', 'year'];
        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'created_at';
        }

        $query = Project::with('author:id,name,avatar');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('client', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        $projects = $query->orderBy($sortBy, $sortDir)->paginate($perPage);

        $projects->getCollection()->transform(function ($p) {
            $p->append('status_badge');
            return $p;
        });

        return response()->json($projects);
    }

    /**
     * GET /api/projects/{id} - single project for editing.
     */
    public function show(int $id): JsonResponse
    {
        $project = Project::with('author:id,name,email')->findOrFail($id);
        return response()->json(['project' => $project]);
    }

    /**
     * POST /api/projects - create a project.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateProject($request);

        if (empty($validated['slug'])) {
            $validated['slug'] = $this->uniqueSlug($validated['title']);
        }

        $validated['author_id'] = $request->user()?->id;

        if (($validated['status'] ?? 'draft') === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $project = Project::create($validated);

        $this->logActivity('project_created', "Project \"{$project->title}\" was created.");

        return response()->json($project, 201);
    }

    /**
     * PUT /api/projects/{id} - update a project.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $project = Project::findOrFail($id);

        $validated = $this->validateProject($request, $id);

        if (array_key_exists('slug', $validated) && empty($validated['slug'])) {
            $validated['slug'] = $this->uniqueSlug($validated['title'] ?? $project->title, $id);
        }

        if (($validated['status'] ?? null) === 'published' && empty($validated['published_at']) && ! $project->published_at) {
            $validated['published_at'] = now();
        }

        $project->update($validated);

        $this->logActivity('project_updated', "Project \"{$project->title}\" was updated.");

        return response()->json($project->fresh('author'));
    }

    /**
     * DELETE /api/projects/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $project = Project::findOrFail($id);
        $this->logActivity('project_deleted', "Project \"{$project->title}\" was deleted.");
        $project->delete();

        return response()->json(['message' => 'Project deleted successfully.']);
    }

    /**
     * PUT /api/projects/{id}/publish
     */
    public function publish(int $id): JsonResponse
    {
        $project = Project::findOrFail($id);
        $project->update([
            'status'       => 'published',
            'published_at' => $project->published_at ?: now(),
        ]);

        $this->logActivity('project_published', "Project \"{$project->title}\" was published.");

        return response()->json($project->fresh('author'));
    }

    /**
     * PUT /api/projects/{id}/unpublish
     */
    public function unpublish(int $id): JsonResponse
    {
        $project = Project::findOrFail($id);
        $project->update(['status' => 'draft']);

        $this->logActivity('project_unpublished', "Project \"{$project->title}\" was unpublished.");

        return response()->json($project->fresh('author'));
    }

    // ─── HELPERS ──────────────────────────────────────────────────────

    private function validateProject(Request $request, ?int $id = null): array
    {
        $rules = [
            'title'            => 'required|string|max:255',
            'slug'             => ['nullable', 'string', 'max:255', Rule::unique('projects', 'slug')->ignore($id)],
            'excerpt'          => 'nullable|string|max:500',
            'description'      => 'required|string',
            'featured_image'   => 'nullable|string|max:500',
            'client'           => 'nullable|string|max:255',
            'category'         => 'nullable|string|max:255',
            'duration'         => 'nullable|string|max:100',
            'year'             => 'nullable|integer|min:1990|max:2100',
            'live_url'         => 'nullable|string|max:500',
            'tech_stack'       => 'nullable|array',
            'tech_stack.*'     => 'string|max:100',
            'tags'             => 'nullable|array',
            'tags.*'           => 'string|max:100',
            'gallery'          => 'nullable|array',
            'gallery.*'        => 'string|max:500',
            'highlights'       => 'nullable|array',
            'highlights.*'     => 'string|max:500',
            'key_features'     => 'nullable|array',
            'key_features.*'   => 'string|max:255',
            'status'           => 'nullable|in:draft,published,archived',
            'published_at'     => 'nullable|date',
            'is_featured'      => 'nullable|boolean',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords'    => 'nullable|array',
            'meta_keywords.*'  => 'string|max:100',
            'og_title'         => 'nullable|string|max:255',
            'og_description'   => 'nullable|string|max:500',
            'og_image'         => 'nullable|string|max:500',
            'json_ld_schema'   => 'nullable',
        ];

        if ($id !== null) {
            $rules['title']       = 'sometimes|required|string|max:255';
            $rules['description'] = 'sometimes|required|string';
        }

        return $request->validate($rules);
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $slug = Str::slug($title);
        $original = $slug ?: 'project';
        $counter = 2;

        while (true) {
            $query = \DB::table('projects')->where('slug', $slug);
            if ($ignoreId) $query->where('id', '!=', $ignoreId);
            if (! $query->exists()) break;
            $slug = $original . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
