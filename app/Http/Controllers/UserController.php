<?php

namespace App\Http\Controllers;

use App\Models\AdminActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    //


    public function index()
    {
        $users = User::all();

        return response()->json([
            'message' => 'Users retrieved successfully.',
            'data' => $users,
        ], 200);
    }


    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'username' => 'required|string|unique:user',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:user',
            'password' => 'required|string|min:6',
            'phone' => 'required|string|unique:user',
            'country_id' => 'required|integer',
            'role' => 'required|string',
        ]);

        // Hash the password
        $validatedData['password'] = bcrypt($validatedData['password']);

        // Assign the role
        $validatedData['roles'] = [$validatedData['role']];

        // Create the new user
        $user = User::create($validatedData);

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

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validatedData = $request->validate([
            'username' => 'string|unique:user,username,' . $id,
            'first_name' => 'string',
            'last_name' => 'string',
            'email' => 'email|unique:user,email,' . $id,
            'phone' => 'string|unique:user,phone,' . $id,
            'country_id' => 'integer',
            'role' => 'string',
        ]);

        // Update the user's attributes
        $user->update($validatedData);

        // Optionally update the role
        if (isset($validatedData['role'])) {
            $user->roles = [$validatedData['role']];
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







}
