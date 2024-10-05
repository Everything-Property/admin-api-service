<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Property;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index()
    {
        // Total number of staff (users with 'ROLE_SUPERADMIN')
        $totalStaffs = User::whereJsonContains('roles', 'ROLE_SUPERADMIN')->count();

        // Total number of users
        $totalUsers = User::count();

        // Total inactive properties (where deleted_at is not null)
        $totalInactiveProperties = Property::whereNotNull('deleted_at')->count();

        // Total inactive projects (where status is not 'active')
        $totalInactiveProjects = Project::where('status', '!=', 'active')->count();

        // Number of users by month for the current year
        $usersByMonth = User::select(DB::raw('COUNT(*) as count'), DB::raw('MONTH(created_at) as month'))
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->get();

        // Number of users subscribed based on their role
        $subscribedUsersByRole = User::whereHas('userSubscriptions')
            ->select(DB::raw('COUNT(*) as count'), 'roles')
            ->groupBy('roles')
            ->get();

        // Number of users under each subscription plan (Free plan, basic plan, enterprise)
        $usersUnderSubscriptions = DB::table('user_subscription_plan')
            ->select(DB::raw('COUNT(*) as count'), 'subscription_plan_id')
            ->groupBy('subscription_plan_id')
            ->get();

        // Number of expired subscribers (based on their role)
        $expiredSubscribers = User::whereHas('userSubscriptions', function ($query) {
            $query->where('end_at', '<', Carbon::now());
        })
            ->select(DB::raw('COUNT(*) as count'), 'roles')
            ->groupBy('roles')
            ->get();

        // User daily transactions (Weekly) - last 7 days
        $userTransactions = DB::table('user_wallet_transaction')
            ->select(DB::raw('COUNT(*) as count'), DB::raw('DATE(created_at) as date'))
            ->whereBetween('created_at', [Carbon::now()->subDays(7), Carbon::now()])
            ->groupBy('date')
            ->get();

        // Number of viewing scheduled (inspection bookings) for the current month and year
        $scheduledViewings = DB::table('inspection_booking')
            ->select(DB::raw('COUNT(*) as count'), DB::raw('MONTH(appointment_date) as month'))
            ->whereYear('appointment_date', Carbon::now()->year)
            ->groupBy('month')
            ->get();

        return response()->json([
            'totalStaffs' => $totalStaffs,
            'totalUsers' => $totalUsers,
            'totalInactiveProperties' => $totalInactiveProperties,
            'totalInactiveProjects' => $totalInactiveProjects,
            'usersByMonth' => $usersByMonth,
            'subscribedUsersByRole' => $subscribedUsersByRole,
            'usersUnderSubscriptions' => $usersUnderSubscriptions,
            'expiredSubscribers' => $expiredSubscribers,
            'userTransactions' => $userTransactions,
            'scheduledViewings' => $scheduledViewings,
        ]);
    }
}
