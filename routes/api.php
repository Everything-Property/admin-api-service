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
use App\Http\Controllers\WebsiteContentController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\Api\BoostSubscriptionController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


    Route::get('/users', [UserController::class, 'users']);
    Route::get('/test', [UserController::class, 'test']);

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
    Route::put('/staffs/{id}', [StaffController::class, 'update']);


    Route::put('staffs/{id}/activate', [StaffController::class, 'activate']);
    Route::put('staffs/{id}/deactivate', [StaffController::class, 'deactivate']);


    Route::get('/account-types', [StaffController::class, 'getAllAccountTypes']);

    //Roles
    Route::get('roles', [RoleController::class, 'index']);
    Route::post('roles', [RoleController::class, 'store']);
    Route::get('roles/{id}', [RoleController::class, 'show']);
    Route::put('roles/{id}', [RoleController::class, 'update']);
    Route::delete('roles/{id}', [RoleController::class, 'destroy']);


    //Permissions
    Route::get('permissions', [PermissionController::class, 'index']);
    Route::get('permissions-enums', [PermissionController::class, 'getAllPermissions']);
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


        //change user password
        Route::put('/{id}/change-password', [UserController::class, 'adminUpdateUserPassword']);

        //users transactions
        Route::get('/{userId}/transactions', [UserController::class, 'getUserTransactions']);

        //export transactions
        Route::get('/{userId}/transactions/export', [UserController::class, 'exportUserTransactions']);

        Route::put('{userId}/verify-kyc', [UserController::class, 'verifyKyc']);
        
    });

    Route::get('settings/profile', [SettingController::class, 'getProfileSettings']);
    Route::post('settings/profile', [SettingController::class, 'updateProfileSettings']);

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
    
    
    Route::get('api-logs', [SettingController::class, 'getApiLogs']);
    


    //Admin activity log
    Route::get('admin-activity-logs', [AdminActivityLogController::class, 'index']);

    //social media links
    Route::get('settings/social-media-links', [SettingController::class, 'getSocialMediaLinks']);

    Route::put('settings/social-media-links', [SettingController::class, 'updateSocialMediaLinks']);

    Route::get('/website-information', [SettingController::class, 'getWebsiteInformation']);

    Route::get('/all-countries', [SettingController::class, 'getCountries']);


    Route::prefix('website-content')->group(function () {
            Route::get('/', [WebsiteContentController::class, 'index']);
            Route::get('/{id}', [WebsiteContentController::class, 'show']);
            Route::post('/', [WebsiteContentController::class, 'store']);
            Route::post('/{id}', [WebsiteContentController::class, 'update']);
            Route::delete('/{id}', [WebsiteContentController::class, 'destroy']);
    });
    
    
    // Boost Subscription Routes
    
    // Public endpoint for getting available plans
    
    
    // Protected boost subscription routes
    Route::group(['prefix' => 'boost-subscriptions'], function () {
        Route::get('/plans', [BoostSubscriptionController::class, 'getPlans']);
        Route::get('/current', [BoostSubscriptionController::class, 'getCurrentSubscription']);
        Route::get('/history', [BoostSubscriptionController::class, 'getSubscriptionHistory']);
        Route::post('/initialize-payment', [BoostSubscriptionController::class, 'initializePayment']);
        Route::post('/verify-payment', [BoostSubscriptionController::class, 'verifyPayment']);
        Route::patch('/cancel-auto-renewal', [BoostSubscriptionController::class, 'cancelAutoRenewal']);
        Route::patch('/enable-auto-renewal', [BoostSubscriptionController::class, 'enableAutoRenewal']);
        Route::get('/viewing-quota', [BoostSubscriptionController::class, 'checkViewingQuota']);
        Route::post('/viewing-request', [BoostSubscriptionController::class, 'createViewingRequest']);
    });

    Route::group(['prefix' => 'admin'], function () {
        
        Route::post('/login', [UserController::class, 'adminLogin']);

        //all properties
        Route::get('/properties', [PropertyController::class, 'getAllProperties']);

        //delist property
        Route::post('/delist-property', [PropertyController::class, 'delistProperty']);

        //relist property
        Route::post('/relist-property', [PropertyController::class, 'relistProperty']);
    });



