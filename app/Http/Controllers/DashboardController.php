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

    $totalActiveProperties = Property::whereNull('deleted_at')->count();

    // Total number of projects
    $totalProjects = Project::count();

    // Total number of active projects (using the status column)
    $totalActiveProjects = Project::where('status', 'active')->count();

    // Top brokers (Example: Getting users with the most properties)
    $topBrokers = User::withCount(['properties' => function ($query) {
        $query->whereNull('deleted_at');
    }])->orderBy('properties_count', 'desc')->take(5)->get();

    // Number of users by months with role breakdown
    $months = collect(range(1, 12))->map(function ($month) {
        return [
            'month' => $month,
            'month_name' => date('F', mktime(0, 0, 0, $month, 1)),
            'count' => 0,
            'roles' => [
                'broker' => 0,
                'seeker' => 0,
                'developer' => 0,
                'company' => 0,
                'superadmin' => 0,
                'agent' => 0,
                'media' => 0, // Added media role
                'unknown' => 0
            ]
        ];
    });

    $usersByMonth = User::select(
        DB::raw('COUNT(*) as count'),
        DB::raw('MONTH(created_at) as month'),
        DB::raw('SUM(CASE 
            WHEN roles = \'["ROLE_USER","ROLE_BROKERAGE","ROLE_BROKER"]\' THEN 1 
            ELSE 0 
        END) as broker_count'),
        DB::raw('SUM(CASE 
            WHEN roles = \'["ROLE_USER"]\' 
            AND NOT JSON_CONTAINS(roles, \'"ROLE_COMPANY"\') 
            AND NOT JSON_CONTAINS(roles, \'"ROLE_DEVELOPER"\') 
            AND NOT JSON_CONTAINS(roles, \'"ROLE_BROKERAGE"\') 
            THEN 1 
            ELSE 0 
        END) as seeker_count'),
        DB::raw('SUM(CASE 
            WHEN roles = \'["ROLE_USER","ROLE_DEVELOPER"]\' THEN 1 
            ELSE 0 
        END) as developer_count'),
        DB::raw('SUM(CASE 
            WHEN roles = \'["ROLE_USER","ROLE_COMPANY"]\' THEN 1 
            ELSE 0 
        END) as company_count'),
        DB::raw('SUM(CASE 
            WHEN JSON_CONTAINS(roles, \'"ROLE_SUPERADMIN"\') THEN 1 
            ELSE 0 
        END) as superadmin_count'),
        DB::raw('SUM(CASE 
            WHEN JSON_CONTAINS(roles, \'"ROLE_AGENT"\') THEN 1 
            ELSE 0 
        END) as agent_count'),
        DB::raw('SUM(CASE 
            WHEN roles = \'["ROLE_STAFF_MEDIA"]\' THEN 1 
            ELSE 0 
        END) as media_count'),
        DB::raw('SUM(CASE 
            WHEN roles NOT IN (
                \'["ROLE_USER"]\',
                \'["ROLE_USER","ROLE_BROKERAGE","ROLE_BROKER"]\',
                \'["ROLE_USER","ROLE_DEVELOPER"]\',
                \'["ROLE_USER","ROLE_COMPANY"]\',
                \'["ROLE_STAFF_MEDIA"]\'
            ) 
            AND NOT JSON_CONTAINS(roles, \'"ROLE_SUPERADMIN"\') 
            AND NOT JSON_CONTAINS(roles, \'"ROLE_AGENT"\') 
            THEN 1 
            ELSE 0 
        END) as unknown_count')
    )
        ->whereYear('created_at', now()->year)
        ->groupBy('month')
        ->get()
        ->map(function ($item) {
            return [
                'month' => (int) $item->month,
                'month_name' => date('F', mktime(0, 0, 0, $item->month, 1)),
                'count' => (int) $item->count,
                'roles' => [
                    'broker' => (int) $item->broker_count,
                    'seeker' => (int) $item->seeker_count,
                    'developer' => (int) $item->developer_count,
                    'company' => (int) $item->company_count,
                    'superadmin' => (int) $item->superadmin_count,
                    'agent' => (int) $item->agent_count,
                    'media' => (int) $item->media_count, // Added media role
                    'unknown' => (int) $item->unknown_count
                ]
            ];
        });

    // Merge the actual data with the months template
    $usersByMonth = $months->map(function ($monthTemplate) use ($usersByMonth) {
        $actualData = $usersByMonth->firstWhere('month', $monthTemplate['month']);
        return $actualData ?? $monthTemplate;
    });

    // Count users by their roles
    $usersByRole = [
        'superadmin' => User::whereJsonContains('roles', 'ROLE_SUPERADMIN')->count(),
        'seeker' => User::where('roles', '["ROLE_USER"]')
            ->whereJsonDoesntContain('roles', 'ROLE_COMPANY')
            ->whereJsonDoesntContain('roles', 'ROLE_DEVELOPER')
            ->whereJsonDoesntContain('roles', 'ROLE_BROKERAGE')
            ->count(),
        'developer' => User::where('roles', '["ROLE_USER","ROLE_DEVELOPER"]')->count(),
        'company' => User::where('roles', '["ROLE_USER","ROLE_COMPANY"]')->count(),
        'broker' => User::where('roles', '["ROLE_USER","ROLE_BROKERAGE","ROLE_BROKER"]')->count(),
        'agent' => User::whereJsonContains('roles', 'ROLE_AGENT')->count(),
        'media' => User::where('roles', '["ROLE_STAFF_MEDIA"]')->count(), // Added media role
        'unknown' => User::whereNotIn('roles', [
            '["ROLE_USER"]',
            '["ROLE_USER","ROLE_BROKERAGE","ROLE_BROKER"]',
            '["ROLE_USER","ROLE_DEVELOPER"]',
            '["ROLE_USER","ROLE_COMPANY"]',
            '["ROLE_STAFF_MEDIA"]'
        ])
            ->whereJsonDoesntContain('roles', 'ROLE_SUPERADMIN')
            ->whereJsonDoesntContain('roles', 'ROLE_AGENT')
            ->count()
    ];

    // Number of user subscriptions
    $totalUserSubscriptions = User::has('userSubscriptions')->count();

    // Number of users under each subscription
    $subscriptions = SubscriptionPlan::with('userSubscriptions')
        ->get()
        ->map(function ($subscription) {
            $subscription->user_count = $subscription->userSubscriptions->count();
            return $subscription;
        });

    // Number of users subscribed based on their role
    $subscribedUsersByRole = User::whereHas('userSubscriptions')
        ->select(DB::raw('COUNT(*) as count'), 'roles')
        ->groupBy('roles')
        ->get();

    // Count expired subscribers by role
    $roles = [
        'Seeker' => 0,
        'Company' => 0,
        'Broker' => 0,
        'Developer' => 0,
        'Media' => 0, // Added media role
        'Unknown' => 0
    ];

    $expiredSubscribersByRole = User::select(
        DB::raw('CASE
            WHEN roles = \'["ROLE_USER"]\' THEN "Seeker"
            WHEN roles = \'["ROLE_USER","ROLE_COMPANY"]\' THEN "Company"
            WHEN roles = \'["ROLE_USER","ROLE_BROKERAGE","ROLE_BROKER"]\' THEN "Broker"
            WHEN roles = \'["ROLE_USER","ROLE_DEVELOPER"]\' THEN "Developer"
            WHEN roles = \'["ROLE_STAFF_MEDIA"]\' THEN "Media"
            ELSE "Unknown"
        END AS role'),
        DB::raw('COUNT(*) as count')
    )
        ->whereHas('userSubscriptions', function ($query) {
            $query->where('end_at', '<', now());
        })
        ->groupBy('role')
        ->get();

    foreach ($expiredSubscribersByRole as $row) {
        $roles[$row->role] = $row->count;
    }

    $expiredSubscribersByRole = array_map(function ($role, $count) {
        return ['role' => $role, 'count' => $count];
    }, array_keys($roles), array_values($roles));

    return response()->json([
        'totalProperties' => $totalProperties,
        'totalActiveProperties' => $totalActiveProperties,
        'totalProjects' => $totalProjects,
        'totalActiveProjects' => $totalActiveProjects,
        'topBrokers' => $topBrokers,
        'usersByMonth' => $usersByMonth,
        'usersByRole' => $usersByRole,
        'totalUserSubscriptions' => $totalUserSubscriptions,
        'subscriptions' => SubscriptionPlanResource::collection($subscriptions),
        'expiredSubscribersByRole' => $expiredSubscribersByRole,
        'subscribedUsersByRole' => $subscribedUsersByRole,
    ]);
}
}
