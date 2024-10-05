<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\AdminActivityLogController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');



    Route::get('/users', [UserController::class, 'users']);

    Route::group(['middleware' => ['web']], function () {
        Route::get('login/facebook', [LoginController::class, 'redirectToFacebook']);

        Route::get('login/facebook/callback', [LoginController::class, 'handleFacebookCallback']);
    });


    Route::get('dashboard', [\App\Http\Controllers\DashboardController::class, 'index']);
    Route::get('report', [ReportController::class, 'index']);



// staff routes

    Route::get('staffs', [StaffController::class, 'index']);
    Route::post('staffs', [StaffController::class, 'store']);
    Route::put('staffs/{id}/activate', [StaffController::class, 'activate']);
    Route::put('staffs/{id}/deactivate', [StaffController::class, 'deactivate']);


    Route::put('staffs/{id}/activate', [StaffController::class, 'activate']);
    Route::put('staffs/{id}/deactivate', [StaffController::class, 'deactivate']);

    //Roles
    Route::get('roles', [RoleController::class, 'index']);
    Route::post('roles', [RoleController::class, 'store']);
    Route::get('roles/{id}', [RoleController::class, 'show']);
    Route::put('roles/{id}', [RoleController::class, 'update']);
    Route::delete('roles/{id}', [RoleController::class, 'destroy']);


    //Permissions
    Route::get('permissions', [PermissionController::class, 'index']);
    Route::post('permissions', [PermissionController::class, 'store']);
    Route::put('permissions/{id}', [PermissionController::class, 'update']);
    Route::delete('permissions/{id}', [PermissionController::class, 'destroy']);


    Route::group(['prefix' => 'users'], function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
       // Route::patch('/{id}/manage', [UserController::class, 'manage']);

        Route::patch('/{id}/activate', [UserController::class, 'activate']);
        Route::patch('/{id}/deactivate', [UserController::class, 'deactivate']);
    });

    Route::get('settings/profile', [SettingController::class, 'getProfileSettings']);
    Route::put('settings/profile', [SettingController::class, 'updateProfileSettings']);

    // Account Settings
    Route::put('settings/account', [SettingController::class, 'updateAccountSettings']);

    // Notifications Settings
    Route::get('settings/notifications', [SettingController::class, 'getNotificationSettings']);
    Route::put('settings/notifications', [SettingController::class, 'updateNotificationSettings']);

    // Privacy Settings
    Route::get('settings/privacy', [SettingController::class, 'getPrivacySettings']);
    Route::put('settings/privacy', [SettingController::class, 'updatePrivacySettings']);

    // App Preference Settings
    Route::get('settings/app-preference', [SettingController::class, 'getAppPreferenceSettings']);
    Route::put('settings/app-preference', [SettingController::class, 'updateAppPreferenceSettings']);

    // Security Settings
    Route::get('settings/security', [SettingController::class, 'getSecuritySettings']);
    Route::put('settings/security', [SettingController::class, 'updateSecuritySettings']);


    //Admin activity log
    Route::get('admin-activity-logs', [AdminActivityLogController::class, 'index']);









