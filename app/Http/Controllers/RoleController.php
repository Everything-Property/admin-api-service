<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    //

    public function index()
    {
        // Fetch all roles with associated permissions
        $roles = Role::with('permissions')->get();

        return response()->json($roles, 200);
    }


    public function store(Request $request)
    {
        // Validate the request
        $validatedData = $request->validate([
            'name' => 'required|string|unique:roles',
            'description' => 'nullable|string',
            'permissions' => 'array|required',
        ]);

        // Create the role
        $role = Role::create([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'] ?? '',
        ]);

        // Attach the permissions to the role
        $role->permissions()->sync($validatedData['permissions']);

        return response()->json([
            'message' => 'Role created successfully.',
            'role' => $role->load('permissions'),
        ], 201);
    }

    public function show($id)
    {
        // Fetch the role with its associated permissions
        $role = Role::with('permissions')->findOrFail($id);

        return response()->json($role, 200);
    }

    public function update(Request $request, $id)
    {
        // Validate the request
        $validatedData = $request->validate([
            'name' => 'required|string|unique:roles,name,' . $id,
            'description' => 'nullable|string',
            'permissions' => 'array|required', // Permissions should be an array of IDs
        ]);

        // Find the role by ID
        $role = Role::findOrFail($id);

        // Update the role details
        $role->update([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'] ?? '',
        ]);

        // Sync the permissions
        $role->permissions()->sync($validatedData['permissions']);

        return response()->json([
            'message' => 'Role updated successfully.',
            'role' => $role->load('permissions'),
        ], 200);
    }

    public function destroy($id)
    {
        // Find the role by ID
        $role = Role::findOrFail($id);

        // Delete the role
        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully.',
        ], 200);
    }







}
