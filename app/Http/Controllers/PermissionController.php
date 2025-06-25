<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;

class PermissionController extends Controller
{
    //
    
     use ApiResponseTrait;

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
    
    
    
    //permission enums
    
public function getAllPermissions()
    {
        try {
            // Fetch all permissions
            $permissions = Permission::all();

            // Transform the permissions
            $transformedPermissions = [];
            foreach ($permissions as $permission) {
                $key = strtoupper(str_replace(' ', '_', $permission->name));
                $transformedPermissions[$key] = $permission->name;
            }

            // Return success response using trait
            return $this->successResponse($transformedPermissions, 'Permissions enums fetched successfully');

        } catch (\Exception $e) {
            // Return failure response using trait
            return $this->failureResponse('Failed to fetch permissions', 500, ['error' => $e->getMessage()]);
        }
    }




}
