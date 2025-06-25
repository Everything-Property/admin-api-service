<?php

namespace App\Http\Controllers;

use App\Models\AccountType;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\AdminActivityLog;

class StaffController extends Controller
{
    //

    public function index()
{
    // Define an array of staff roles
    $staffRoles = ['ROLE_STAFF_MEDIA', 'ROLE_ADMIN', 'ROLE_AGENT', 'ROLE_STAFF_IT', 'ROLE_AGENT'];

    // Fetch all users who have any of the specified staff roles
    $staffs = User::where(function ($query) use ($staffRoles) {
        foreach ($staffRoles as $role) {
            $query->orWhereJsonContains('roles', $role);
        }
    })->get();

    return response()->json($staffs, 200);
}



public function update(Request $request, $id)
{
    try {
        // Attempt to find the user
        $staff = User::findOrFail($id);

        // Define validation rules, excluding unique checks for fields if they belong to the current user
        $validationRules = [
            'username' => 'required|string|unique:user,username,' . $staff->id,
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:user,email,' . $staff->id,
            'password' => 'nullable|string|min:6', 
            'phone' => 'required|string|unique:user,phone,' . $staff->id,
            'country_id' => 'required|integer',
            'account_type' => 'required|string|exists:roles,name',
        ];

        try {
            // Validate the request data
            $validatedData = $request->validate($validationRules);

            // Hash the password if it’s provided in the update request
            if (!empty($validatedData['password'])) {
                $validatedData['password'] = bcrypt($validatedData['password']);
            } else {
                // If no password is provided, keep the existing one
                unset($validatedData['password']);
            }

            // Assign account type to roles column
            $validatedData['roles'] = [$validatedData['account_type']];

            // Remove account_type from validated data
            unset($validatedData['account_type']);

            // Fetch permissions for the role if account_type has changed
            if ($staff->roles[0] !== $validatedData['roles'][0]) {
                $roleRecord = Role::where('name', $validatedData['roles'][0])->first();
                $permissions = $roleRecord ? $roleRecord->permissions->pluck('name')->toArray() : [];
                $validatedData['permissions'] = $permissions;
            }

            // Update staff data
            $staff->update($validatedData);

            return response()->json([
                'message' => 'Staff updated successfully.',
                'staff' => $staff,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'message' => 'Staff not found.',
        ], 404);
    }
}



    public function store(Request $request)
{
    // Define common validation rules for all staff roles
    $validationRules = [
        'username' => 'required|string|unique:user',
        'first_name' => 'required|string',
        'last_name' => 'required|string',
        'email' => 'required|email|unique:user',
        'password' => 'required|string|min:6',
        'phone' => 'required|string|unique:user',
        'country_id' => 'required|integer',
        'account_type' => 'required|string|exists:roles,name',
    ];

    // Validate the request data
    $validatedData = $request->validate($validationRules);

    // Hash the password
    $validatedData['password'] = bcrypt($validatedData['password']);

    // Assign the account type to the roles column
    $role = $validatedData['account_type'];
    $validatedData['roles'] = [$role];

    // Fetch permissions for the role
    $roleRecord = Role::where('name', $role)->first();
    $permissions = [];

    if ($roleRecord) {
        // Get permissions for the role
        $rolePermissions = $roleRecord->permissions; // Assuming you have the relationship defined
        $permissions = $rolePermissions->pluck('name')->toArray(); // Fetch the permission names
    }

    // Assign permissions to the staff data
    $validatedData['permissions'] = $permissions;

    // Remove account_type from validated data
    unset($validatedData['account_type']);

    // Create the new staff (user)
    $staff = User::create($validatedData);

    // Log the staff creation activity
    AdminActivityLog::create([
        'timestamp' => now(),
        'name' => 'Superadmin',
        'role' => 'ROLE_SUPERADMIN',
        'activity' => 'Staff Create',
        'details' => 'Created staff: ' . $staff->username,
        'device' => $request->header('User-Agent'),
    ]);

    return response()->json([
        'message' => 'Staff added successfully.',
        'staff' => $staff,
    ], 201);
}


    public function activate($id)
    {
        $staff = User::where('id', $id)->firstOrFail();

        $staff->user_verified = 1;
        $staff->save();

        return response()->json([
            'success' => true,
            'message' => 'Staff activated successfully.',
            'staff' => $staff,
        ], 200);
    }


    public function deactivate($id)
    {
        $staff = User::where('id', $id)->firstOrFail();

        $staff->user_verified = 0; 
        $staff->save();

        return response()->json([
            'message' => 'Staff deactivated successfully.',
            'staff' => $staff,
        ], 200);
    }


    public function getAllAccountTypes(): JsonResponse
    {
        $accountTypes = AccountType::all();

        return response()->json([
            'success' => true,
            'data' => $accountTypes,
        ]);
    }


}
