<?php

namespace App\Http\Controllers;

use App\Http\Resources\SubscriptionPlanResource;
use App\Models\Property;
use App\Models\Project;
use App\Models\User;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Total number of properties
        $totalProperties = Property::count();

        // Total number of active properties (assuming properties don't have a status column)
        // You can update this if there's a different logic for active properties.
        $totalActiveProperties = Property::whereNull('deleted_at')->count();

        // Total number of projects
        $totalProjects = Project::count();

        // Total number of active projects (using the status column)
        $totalActiveProjects = Project::where('status', 'active')->count();

        // Top brokers (Example: Getting users with the most properties)
        $topBrokers = User::withCount('properties')->orderBy('properties_count', 'desc')->take(5)->get();

        // Number of users by months
        $usersByMonth = User::select(DB::raw('COUNT(*) as count'), DB::raw('MONTH(created_at) as month'))
            ->groupBy('month')
            ->get();

        // Number of user subscriptions
        $totalUserSubscriptions = User::has('userSubscriptions')->count();

        // Number of users under each subscription
        $subscriptions = SubscriptionPlan::withCount('userSubscriptions')->get();

        return response()->json([
            'totalProperties' => $totalProperties,
            'totalActiveProperties' => $totalActiveProperties,
            'totalProjects' => $totalProjects,
            'totalActiveProjects' => $totalActiveProjects,
            'topBrokers' => $topBrokers,
            'usersByMonth' => $usersByMonth,
            'totalUserSubscriptions' => $totalUserSubscriptions,
            'subscriptions' => SubscriptionPlanResource::collection($subscriptions),
        ]);
    }


}
