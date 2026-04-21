<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AboutSection;
use App\Models\ActivityLog;
use App\Models\BlogPost;
use App\Models\HomepageSection;
use App\Models\Media;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Return dashboard statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        $totalUsers = User::count();
        $totalPosts = BlogPost::count();
        $totalServices = Service::count();
        $totalMedia = Media::count();

        // Count active sessions from the sessions table
        $activeSessions = 0;
        try {
            $activeSessions = DB::table('sessions')
                ->where('last_activity', '>=', now()->subMinutes(config('session.lifetime', 120))->getTimestamp())
                ->count();
        } catch (\Exception $e) {
            $activeSessions = 0;
        }

        // Page sections count (homepage + about)
        $totalPages = HomepageSection::count() + AboutSection::count();

        return response()->json([
            'total_users' => $totalUsers,
            'active_sessions' => $activeSessions,
            'total_pages' => $totalPages,
            'total_posts' => $totalPosts,
            'total_services' => $totalServices,
            'total_media' => $totalMedia,
        ]);
    }

    /**
     * Return recent activity log entries.
     */
    public function recentActivity(Request $request): JsonResponse
    {
        $activities = ActivityLog::with('user:id,name,email')
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'activities' => $activities,
        ]);
    }
}
