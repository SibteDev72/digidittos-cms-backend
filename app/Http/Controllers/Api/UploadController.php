<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    /**
     * Upload a file (image or video).
     * POST /api/upload
     * Accepts: multipart/form-data with 'file' field
     * Optional: 'folder' field (default: 'media')
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:102400', // 100MB max
            'folder' => 'nullable|string|max:50',
        ]);

        $file = $request->file('file');
        $folder = $request->input('folder', 'media');

        // Validate file type
        $allowedMimes = [
            // Images
            'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
            // Videos
            'video/mp4', 'video/webm', 'video/ogg', 'video/quicktime',
        ];

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return response()->json([
                'message' => 'File type not allowed. Accepted: JPEG, PNG, GIF, WebP, SVG, MP4, WebM, OGG, MOV',
            ], 422);
        }

        // Generate unique filename
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;

        // Store in public disk under folder
        $path = $file->storeAs("uploads/{$folder}", $filename, 'public');

        // Generate full URL
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

        // Create a media record so uploads are tracked in the media library
        Media::create([
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'path' => $path,
            'url' => $url,
            'disk' => 'public',
            'folder' => $folder,
            'width' => $width,
            'height' => $height,
            'uploaded_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'File uploaded successfully.',
            'url' => $url,
            'path' => $path,
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);
    }

    /**
     * Unlink endpoint — kept for backwards compatibility but intentionally
     * NO-OP on the filesystem + Media row. Removing a file from a form
     * field must never wipe it from the Media Library, because the same
     * asset may be referenced by other records. Deletion is an explicit
     * action in the Media Library itself (DELETE /api/media/{id}).
     *
     * DELETE /api/upload
     */
    public function destroy(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Field unlinked. The file remains in the Media Library and can be deleted from there.',
        ]);
    }
}
