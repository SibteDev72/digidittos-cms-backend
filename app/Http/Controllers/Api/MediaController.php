<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Traits\LogsActivity;

class MediaController extends Controller
{
    use LogsActivity;
    /**
     * Allowed MIME types (same as UploadController).
     */
    private array $allowedMimes = [
        // Images
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        // Videos
        'video/mp4', 'video/webm', 'video/ogg', 'video/quicktime',
    ];

    /**
     * GET /api/media - paginated list with search, filter, sort.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 24), 100);
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $type = $request->input('type');
        $search = $request->input('search');
        $folder = $request->input('folder');

        // Validate sort options
        $allowedSorts = ['name', 'size', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

        // Map 'name' sort to 'original_name' column
        if ($sortBy === 'name') {
            $sortBy = 'original_name';
        }

        $query = Media::query();

        // Filter by type
        if ($type === 'image') {
            $query->images();
        } elseif ($type === 'video') {
            $query->videos();
        }

        // Filter by folder
        if ($folder) {
            $query->where('folder', $folder);
        }

        // Search across multiple fields
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('original_name', 'like', "%{$search}%")
                  ->orWhere('filename', 'like', "%{$search}%")
                  ->orWhere('alt_text', 'like', "%{$search}%")
                  ->orWhere('caption', 'like', "%{$search}%");
            });
        }

        $media = $query->orderBy($sortBy, $sortDir)->paginate($perPage);

        return response()->json($media);
    }

    /**
     * POST /api/media - upload one or more files.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'nullable|file|max:102400',
            'files' => 'nullable|array',
            'files.*' => 'file|max:102400',
            'folder' => 'nullable|string|max:50',
            'alt_text' => 'nullable|string|max:255',
            'caption' => 'nullable|string|max:2000',
        ]);

        $folder = $request->input('folder', 'media');
        $altText = $request->input('alt_text');
        $caption = $request->input('caption');

        // Collect files from either 'file' (single) or 'files[]' (multiple)
        $files = [];
        if ($request->hasFile('files')) {
            $files = $request->file('files');
        } elseif ($request->hasFile('file')) {
            $files = [$request->file('file')];
        }

        if (empty($files)) {
            return response()->json(['message' => 'No files provided.'], 422);
        }

        $created = [];
        $errors = [];

        foreach ($files as $file) {
            // Validate MIME type
            if (!in_array($file->getMimeType(), $this->allowedMimes)) {
                $errors[] = "{$file->getClientOriginalName()}: File type not allowed.";
                continue;
            }

            // Generate UUID filename preserving extension
            $extension = $file->getClientOriginalExtension();
            $filename = Str::uuid() . '.' . $extension;

            // Store in public disk
            $path = $file->storeAs("uploads/{$folder}", $filename, 'public');
            $url = Storage::disk('public')->url($path);

            // Get image dimensions if applicable
            $width = null;
            $height = null;
            if (str_starts_with($file->getMimeType(), 'image/') && $file->getMimeType() !== 'image/svg+xml') {
                try {
                    $imageInfo = getimagesize($file->getRealPath());
                    if ($imageInfo) {
                        $width = $imageInfo[0];
                        $height = $imageInfo[1];
                    }
                } catch (\Throwable $e) {
                    // Silently ignore dimension errors
                }
            }

            $media = Media::create([
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'path' => $path,
                'url' => $url,
                'disk' => 'public',
                'folder' => $folder,
                'alt_text' => $altText,
                'caption' => $caption,
                'width' => $width,
                'height' => $height,
                'uploaded_by' => auth()->id(),
            ]);

            $this->logActivity('media_uploaded', "Media \"{$media->original_name}\" was uploaded.");

            $created[] = $media;
        }

        $response = ['media' => $created];
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, 201);
    }

    /**
     * GET /api/media/{id} - single media item.
     */
    public function show(int $id): JsonResponse
    {
        $media = Media::with('uploader:id,name,email')->findOrFail($id);

        return response()->json(['media' => $media]);
    }

    /**
     * PUT /api/media/{id} - update alt_text, caption, folder.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $media = Media::findOrFail($id);

        $validated = $request->validate([
            'alt_text' => 'nullable|string|max:255',
            'caption' => 'nullable|string|max:2000',
            'folder' => 'nullable|string|max:50',
        ]);

        $media->update($validated);

        $this->logActivity('media_updated', "Media \"{$media->original_name}\" was updated.");

        return response()->json(['media' => $media->fresh()]);
    }

    /**
     * DELETE /api/media/{id} - delete file from disk + DB record.
     */
    public function destroy(int $id): JsonResponse
    {
        $media = Media::findOrFail($id);

        // Delete from storage
        if (Storage::disk($media->disk)->exists($media->path)) {
            Storage::disk($media->disk)->delete($media->path);
        }

        $this->logActivity('media_deleted', "Media \"{$media->original_name}\" was deleted.");

        $media->delete();

        return response()->json(['message' => 'Media deleted successfully.']);
    }

    /**
     * POST /api/media/bulk-delete - delete multiple media items.
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:media,id',
        ]);

        $mediaItems = Media::whereIn('id', $request->input('ids'))->get();

        $ids = $request->input('ids');

        foreach ($mediaItems as $media) {
            if (Storage::disk($media->disk)->exists($media->path)) {
                Storage::disk($media->disk)->delete($media->path);
            }
            $media->delete();
        }

        $this->logActivity('media_bulk_deleted', count($ids) . " media files were deleted.");

        return response()->json([
            'message' => count($mediaItems) . ' media item(s) deleted successfully.',
        ]);
    }

    /**
     * GET /api/media-stats - media library statistics.
     */
    public function stats(): JsonResponse
    {
        $totalCount = Media::count();
        $totalSize = Media::sum('size');
        $imagesCount = Media::images()->count();
        $videosCount = Media::videos()->count();
        $folders = Media::distinct()->pluck('folder')->sort()->values();

        return response()->json([
            'total_count' => $totalCount,
            'total_size' => (int) $totalSize,
            'images_count' => $imagesCount,
            'videos_count' => $videosCount,
            'folders' => $folders,
        ]);
    }
}
