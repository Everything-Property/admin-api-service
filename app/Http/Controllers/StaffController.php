<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    //

    public function index()
    {
        // Fetch all users with the role "ROLE_SUPERADMIN"
        $staffs = User::whereJsonContains('roles', 'ROLE_SUPERADMIN')->get();

        return response()->json($staffs, 200);
    }


    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'username' => 'required|string|unique:user',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'phone' => 'required|string|unique:user',
            'country_id' => 'required|integer',
        ]);

        // Hash the password
        $validatedData['password'] = bcrypt($validatedData['password']);

        // Add role "ROLE_SUPERADMIN" to the roles array
        $validatedData['roles'] = json_encode(['ROLE_SUPERADMIN']);

        // Create new staff (user)
        $staff = User::create($validatedData);

        return response()->json([
            'message' => 'Staff added successfully.',
            'staff' => $staff,
        ], 201);
    }

    public function activate($id)
    {
        $staff = User::where('id', $id)->whereJsonContains('roles', 'ROLE_SUPERADMIN')->firstOrFail();

        $staff->user_verified = 1;
        $staff->save();

        return response()->json([
            'message' => 'Staff activated successfully.',
            'staff' => $staff,
        ], 200);
    }


    public function deactivate($id)
    {
        $staff = User::where('id', $id)->whereJsonContains('roles', 'ROLE_SUPERADMIN')->firstOrFail();

        $staff->user_verified = 0; // Mark user as deactivated
        $staff->save();

        return response()->json([
            'message' => 'Staff deactivated successfully.',
            'staff' => $staff,
        ], 200);
    }


}
