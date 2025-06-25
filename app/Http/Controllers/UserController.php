<?php

namespace App\Http\Controllers;

use App\Models\AdminActivityLog;
use App\Models\CompanyInformation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\UserInformation;
use App\Models\UserWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UserTransactionsExport;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Support\Facades\DB;
use App\Models\Property;



class UserController extends Controller
{
    //

    public function test(){

        dd("hello");
    }

    public function index(Request $request)
    {
        $query = User::query()
            ->withCount('properties as listings_count'); // Add property count with alias

        // Apply search filters if provided
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('username', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%")
                ->orWhereJsonContains('roles', $search); // Search within the roles JSON column
        }

        // Apply additional filters
        if ($request->has('role')) {
            $query->whereJsonContains('roles', $request->input('role'));
        }
        if ($request->has('user_verified')) {
            $query->where('user_verified', $request->input('user_verified'));
        }
        if ($request->has('kyc_verified')) {
            $query->where('kyc_verified', $request->input('kyc_verified'));
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at'); // Default to 'created_at' if not provided
        $sortOrder = $request->input('sort_order', 'desc'); // Default to descending order
        $query->orderBy($sortBy, $sortOrder);

        // Pagination parameters
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        // Paginate user data
        $users = $query->paginate($perPage);

        // Get pagination information
        $pagination = [
            'total' => $users->total(),
            'per_page' => $users->perPage(),
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
            'from' => $users->firstItem(),
            'to' => $users->lastItem(),
            'path' => $users->url(1), // Base URL for pagination links
            'next_page_url' => $users->nextPageUrl(),
            'prev_page_url' => $users->previousPageUrl(),
        ];

        // Format user data
        $formattedUsers = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'first_name' => $user->first_name ?? $user->company_name,
                'last_name' => $user->last_name ?? '-',
                'phone' => $user->phone,
                'roles' => $user->roles,
                'permissions' => $user->permissions,
                'user_verified' => $user->user_verified,
                'kyc_verified' => $user->kyc_verified,
                'profile_picture' => $user->profile_picture,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'listings_count' => $user->listings_count ?? 0 // Add the property count here
            ];
        });

        return response()->json([
            'message' => 'Users retrieved successfully.',
            'data' => $formattedUsers,
            'pagination' => $pagination,
        ], 200);
    }


    public function store(Request $request)
    {
        $role = $request->input('role');

        $validationRules = [
            'username' => 'required|string|unique:user',
            'email' => 'required|email|unique:user',
            'password' => 'required|string|min:6',
            'phone' => 'required|string|unique:user',
            'country_id' => 'required|integer',
            'role' => 'required|string',
        ];

        switch ($role) {
            case 'ROLE_SEEKER':
                $validationRules = array_merge($validationRules, [
                    'first_name' => 'required|string',
                    'last_name' => 'required|string',
                ]);
                break;
            case 'ROLE_BROKER':
                $validationRules = array_merge($validationRules, [
                    'broker_account_type' => 'required|string',
                    'first_name' => 'required|string',
                    'last_name' => 'required|string',
                ]);
                break;
            case 'ROLE_COMPANY':
            case 'ROLE_DEVELOPER':
                $validationRules = array_merge($validationRules, [
                    'company_name' => 'required|string',
                    'rc_number' => 'required|string',
                    'first_name' => 'required|string',
                    'last_name' => 'required|string',
                ]);
                break;
            case 'ROLE_STAFF_MEDIA':
                $validationRules = array_merge($validationRules, [
                    'first_name' => 'required|string',
                    'last_name' => 'required|string',
                ]);
                break;
            case 'ROLE_STAFF_IT':
                $validationRules = array_merge($validationRules, [
                    'first_name' => 'required|string',
                    'last_name' => 'required|string',
                ]);
                break;
            default:
                return response()->json(['error' => 'Invalid role'], 400);
        }

        $validatedData = $request->validate($validationRules);

        // Hash the password
        $validatedData['password'] = bcrypt($validatedData['password']);

        // Assign the role
        $validatedData['roles'] = [$role];

        // Fetch permissions for the role
        $roleRecord = Role::where('name', $role)->first();
        $permissions = [];

        if ($roleRecord) {
            // Get permissions for the role
            $rolePermissions = $roleRecord->permissions; // Assuming you have the relationship defined
            $permissions = $rolePermissions->pluck('name')->toArray(); // Fetch the permission names
        }

        // Assign permissions to the user data
        $validatedData['permissions'] = $permissions;

        // Create the new user
        $user = User::create($validatedData);

        // Save additional information based on role
        switch ($role) {
            case 'ROLE_BROKER':
                UserInformation::create([
                    'user_id' => $user->id,
                    'broker_account_type' => $request->input('broker_account_type'),
                ]);
                break;
            case 'ROLE_COMPANY':
            case 'ROLE_DEVELOPER':
                UserInformation::create([
                    'user_id' => $user->id,
                    'company_name' => $request->input('company_name'),
                ]);
                CompanyInformation::create([
                    'user_id' => $user->id,
                    'company_name' => $request->input('company_name'),
                    'rc_number' => $request->input('rc_number'),
                ]);
                break;
        }

        // Log the admin activity directly to the admin_activity_logs table
        AdminActivityLog::create([
            'timestamp' => now(),
            'name' => 'Superadmin',
            'role' => 'ROLE_SUPERADMIN',
            'activity' => 'User Create',
            'details' => 'Created user: ' . $user->username,
            'device' => $request->header('User-Agent'),
        ]);

        return response()->json([
            'message' => 'User created successfully.',
            'data' => $user,
        ], 201);
    }


    public function show($id)
    {
        $user = User::findOrFail($id);

        // Log the admin activity directly to the admin_activity_logs table
        AdminActivityLog::create([
            'timestamp' => now(),
            'name' => 'Superadmin',
            'role' => 'ROLE_SUPERADMIN',
            'activity' => 'User Retrieve',
            'details' => 'Retrieved user: ' . $user->username,
            'device' => request()->header('User-Agent'),
        ]);

        return response()->json([
            'message' => 'User retrieved successfully.',
            'data' => $user,
        ], 200);
    }

    //     public function update(Request $request, $id)
    //     {
    //     $user = User::findOrFail($id);

    //     $validatedData = $request->validate([
    //         'username' => 'nullable|string|unique:user,username,' . $id,
    //         'first_name' => 'nullable|string',
    //         'last_name' => 'nullable|string',
    //         'email' => 'nullable|email|unique:user,email,' . $id,
    //         'phone' => 'nullable|string|unique:user,phone,' . $id,
    //         'country_id' => 'nullable|integer',
    //         'role' => 'nullable|string',
    //     ]);

    //     // Update the user's attributes
    //     $user->update($validatedData);

    //     // Optionally update the role
    //     if (isset($validatedData['role'])) {
    //         $user->roles = [$validatedData['role']];
    //         $user->save();
    //     }

    //     // Log the admin activity directly to the admin_activity_logs table
    //     AdminActivityLog::create([
    //         'timestamp' => now(),
    //         'name' => 'Superadmin',
    //         'role' => 'ROLE_SUPERADMIN',
    //         'activity' => 'User Update',
    //         'details' => 'Updated user: ' . $user->username,
    //         'device' => $request->header('User-Agent'),
    //     ]);

    //     return response()->json([
    //         'message' => 'User updated successfully.',
    //         'data' => $user,
    //     ], 200);
    // }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validatedData = $request->validate([
            'username' => 'nullable|string|unique:user,username,' . $id,
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'email' => 'nullable|email|unique:user,email,' . $id,
            'phone' => 'nullable|string|unique:user,phone,' . $id,
            'country_id' => 'nullable|integer',
            'role' => 'nullable|string',
        ]);

        // Update the user's attributes
        $user->update($validatedData);

        // Optionally update the role with additional roles
        if (isset($validatedData['role'])) {
            $newRoles = ['ROLE_USER']; // Default role for all users

            switch ($validatedData['role']) {
                case 'ROLE_BROKER':
                    $newRoles[] = 'ROLE_BROKER';
                    $newRoles[] = 'ROLE_BROKERAGE';
                    break;
                case 'ROLE_COMPANY':
                    $newRoles[] = 'ROLE_COMPANY';
                    break;
                default:
                    $newRoles[] = $validatedData['role'];
                    break;
            }

            $user->roles = $newRoles;
            $user->save();
        }

        // Log the admin activity directly to the admin_activity_logs table
        AdminActivityLog::create([
            'timestamp' => now(),
            'name' => 'Superadmin',
            'role' => 'ROLE_SUPERADMIN',
            'activity' => 'User Update',
            'details' => 'Updated user: ' . $user->username,
            'device' => $request->header('User-Agent'),
        ]);

        return response()->json([
            'message' => 'User updated successfully.',
            'data' => $user,
        ], 200);
    }



    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $userName = $user->username;

        $user->delete();

        // Log the admin activity directly to the admin_activity_logs table
        AdminActivityLog::create([
            'timestamp' => now(),
            'name' => 'Superadmin',
            'role' => 'ROLE_SUPERADMIN',
            'activity' => 'User Delete',
            'details' => 'Deleted user: ' . $userName,
            'device' => request()->header('User-Agent'),
        ]);

        return response()->json([
            'message' => 'User deleted successfully.',
        ], 200);
    }

    public function activate($id)
    {
        $user = User::findOrFail($id);

        // Set `user_verified` to true
        $user->user_verified = true;
        $user->save();

        return response()->json([
            'message' => 'User activated successfully.',
            'data' => $user,
        ], 200);
    }

    public function deactivate($id)
    {
        $user = User::findOrFail($id);

        // Set `user_verified` to false
        $user->user_verified = false;
        $user->save();

        return response()->json([
            'message' => 'User deactivated successfully.',
            'data' => $user,
        ], 200);
    }



    //change user password
    public function adminUpdateUserPassword(Request $request, $id)
    {
        // Validate the input
        $request->validate([
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        // Find the user by ID
        $user = User::find($id);

        if (!$user) {
            // Return a custom error response if the user is not found
            return response()->json(['message' => 'User not found'], 404);
        }

        // Hash the new password using bcrypt (compatible with Symfony)
        $user->password = Hash::make($request->new_password);

        // Save the new password
        $user->save();

        return response()->json(['message' => 'User password updated successfully.'], 200);
    }
    //get users transactions
    public function getUserTransactions($userId)
    {
        // Find the wallet associated with the user
        $wallet = UserWallet::where('user_id', $userId)->first();

        if (!$wallet) {
            return response()->json(['message' => 'User wallet not found'], 404);
        }

        // Get the user's transactions
        $transactions = $wallet->transactions()->get();

        return response()->json([
            'message' => 'User transactions retrieved successfully.',
            'data' => $transactions,
        ], 200);
    }
    public function exportUserTransactions(Request $request, $userId)
    {
        // Find the wallet associated with the user
        $wallet = UserWallet::where('user_id', $userId)->first();

        if (!$wallet) {
            return response()->json(['message' => 'User wallet not found'], 404);
        }

        // Get the user's transactions
        $transactions = $wallet->transactions()->get();

        // Check the requested format
        $format = $request->query('format', 'csv');

        if ($format === 'csv' || $format === 'xlsx') {
            // Use Laravel Excel to export as CSV or Excel
            return Excel::download(new UserTransactionsExport($transactions), 'transactions.' . $format);
        } elseif ($format === 'pdf') {
            // Use DomPDF to export as PDF
            $pdf = PDF\Pdf::loadView('exports.transactions', ['transactions' => $transactions]);
            return $pdf->download('transactions.pdf');
        } else {
            return response()->json(['message' => 'Invalid export format.'], 400);
        }
    }

    public function verifyKyc($userId)
    {
        try {
            // Find the user by ID
            $user = User::findOrFail($userId);

            // Check if KYC is already verified
            if ($user->kyc_verified) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User KYC is already verified.'
                ], 400);
            }

            // Update the KYC verified status
            $user->kyc_verified = true;
            $user->save();

            // Log the action
            Log::info("KYC verified for user ID {$user->id} by Admin");

            // Return success response
            return response()->json([
                'status' => 'success',
                'message' => 'User KYC verified successfully.',
                'data' => $user,
            ], 200);
        } catch (\Exception $e) {
            // Log any error encountered
            Log::error("Error verifying KYC for user ID {$userId}: " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to verify user KYC.',
            ], 500);
        }
    }



    //admin authenticate
    //     public function adminLogin(Request $request)
    // {
    //   // Validate the incoming request data
    //     $request->validate([
    //         'username' => 'required|string',
    //         'password' => 'required|string',
    //     ]);

    //     $username = $request->input('username');
    //     $password = $request->input('password');

    //     // Log the login attempt for debugging
    //     \Log::info('Attempting login with username: ' . $username);

    //     // First, attempt to authenticate the user using Laravel's built-in Auth system
    //     if (Auth::attempt(['username' => $username, 'password' => $password])) {
    //         // The user has been authenticated successfully
    //         $user = Auth::user();

    //         // Debug: Check if the user is authenticated
    //         \Log::info('User authenticated: ' . $user->id);

    //         // Now, check if the user has the 'ROLE_SUPERADMIN' in their 'roles' JSON column
    //         if (DB::table('user')
    //             ->where('id', $user->id)
    //             ->whereJsonContains('roles', 'ROLE_SUPERADMIN')
    //             ->exists()) {
    //             // User has ROLE_SUPERADMIN, grant access
    //             return response()->json([
    //                 'message' => 'Superadmin access granted.'
    //             ]);
    //         } else {
    //             // User is authenticated but does not have ROLE_SUPERADMIN
    //             return response()->json([
    //                 'error' => 'Access denied. Not a superadmin.'
    //             ], 403);
    //         }
    //     } else {
    //         // If authentication fails (invalid username/password)
    //         return response()->json([
    //             'error' => 'Invalid credentials.'
    //         ], 401);
    //     }
    // }

    // public function adminLogin(Request $request)
    // {
    //     $username = $request->input('username');
    //     $password = $request->input('password');

    //     // Attempt to authenticate the user
    //     if (Auth::attempt(['username' => $username, 'password' => $password])) {
    //         $user = Auth::user();

    //         // Log user data and roles for debugging
    //         \Log::info('User authenticated with ID: ' . $user->id);
    //         \Log::info('Roles: ' . json_encode($user->roles));

    //         // Check if user has 'ROLE_SUPERADMIN' in their roles column
    //         if (DB::table('user')
    //             ->where('id', $user->id)
    //             ->whereJsonContains('roles', 'ROLE_SUPERADMIN')
    //             ->exists()) {

    //             // The user has the ROLE_SUPERADMIN
    //             return response()->json([
    //                 'message' => 'Superadmin access granted.'
    //             ]);
    //         } else {
    //             // The user does not have ROLE_SUPERADMIN
    //             return response()->json([
    //                 'error' => 'Access denied. Not a superadmin.'
    //             ], 403);
    //         }
    //     } else {
    //         return response()->json([
    //             'error' => 'Invalid credentials.'
    //         ], 401);
    //     }
    // }



    public function adminLogin(Request $request)
    {
        $username = $request->input('username');
        $password = $request->input('password');

        // Attempt to authenticate the user
        if (Auth::attempt(['username' => $username, 'password' => $password])) {
            $user = Auth::user();

            // Log user data and roles for debugging
            \Log::info('User authenticated with ID: ' . $user->id);
            \Log::info('Roles: ' . json_encode($user->roles));

            // Check if user has 'ROLE_SUPERADMIN' in their roles column
            if (DB::table('user') // Ensure the table name is correct ('users' not 'user')
                ->where('id', $user->id)
                ->whereJsonContains('roles', 'ROLE_SUPERADMIN')
                ->exists()
            ) {

                // Generate a Sanctum token
                $token = $user->createToken('YourAppName')->plainTextToken;

                // Return the response with token and user data
                return response()->json([
                    'token' => $token,
                    'user' => [
                        'id' => $user->id,
                        'firstName' => $user->first_name,
                        'lastName' => $user->last_name,
                        'phone' => $user->phone,
                        'email' => $user->email,
                        'username' => $user->username,
                        'accountType' => $user->account_type,
                        'subAccountType' => $user->sub_account_type,
                        'profilePicture' => $user->profile_picture,
                        'kycVerified' => $user->kyc_verified,
                        'permissions' => $user->permissions,
                        'emailNotification' => $user->email_notification,
                        'smsNotification' => $user->sms_notification,
                    ],
                    'refresh_token' => $token,
                ]);
            } else {
                // The user does not have ROLE_SUPERADMIN
                return response()->json([
                    'error' => 'Access denied. Not a superadmin.'
                ], 403);
            }
        } else {
            return response()->json([
                'error' => 'Invalid credentials.'
            ], 401);
        }
    }

    public function properties()
    {
        return $this->hasMany(Property::class);
    }
}
