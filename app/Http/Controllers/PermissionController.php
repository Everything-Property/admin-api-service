<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    //

    public function index()
    {
        // Fetch all permissions
        $permissions = Permission::all();

        return response()->json($permissions, 200);
    }

    public function store(Request $request)
    {
        // Validate the request
        $validatedData = $request->validate([
            'name' => 'required|string|unique:permissions',
            'description' => 'nullable|string',
        ]);

        // Create the permission
        $permission = Permission::create([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'] ?? '',
        ]);

        return response()->json([
            'message' => 'Permission created successfully.',
            'permission' => $permission,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        // Validate the request
        $validatedData = $request->validate([
            'name' => 'required|string|unique:permissions,name,' . $id,
            'description' => 'nullable|string',
        ]);

        // Find the permission by ID
        $permission = Permission::findOrFail($id);

        // Update the permission details
        $permission->update([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'] ?? '',
        ]);

        return response()->json([
            'message' => 'Permission updated successfully.',
            'permission' => $permission,
        ], 200);
    }
    public function destroy($id)
    {
        // Find the permission by ID
        $permission = Permission::findOrFail($id);

        // Delete the permission
        $permission->delete();

        return response()->json([
            'message' => 'Permission deleted successfully.',
        ], 200);
    }



}
