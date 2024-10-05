<?php

namespace App\Http\Controllers;

use App\Models\AdminActivityLog;
use Illuminate\Http\Request;

class AdminActivityLogController extends Controller
{
    //

    public function index(Request $request)
    {
        // Get the sort type from the request, default to all if not provided
        $sort = $request->query('sort', 'all');

        // Create a query builder instance
        $query = AdminActivityLog::query();

        // Apply sorting based on activity type if specified
        if (in_array($sort, ['login', 'logout', 'create', 'update', 'delete'])) {
            $query->where('activity', 'LIKE', "%$sort%");
        }

        // Pagination with optional limit per page
        $perPage = $request->query('per_page', 10); // Default to 10 logs per page
        $activityLogs = $query->orderBy('timestamp', 'desc')->paginate($perPage);

        return response()->json([
            'message' => 'Activity logs retrieved successfully.',
            'data' => $activityLogs,
        ], 200);
    }
}
