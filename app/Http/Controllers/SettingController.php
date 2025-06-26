<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    //

    public function getProfileSettings()
    {

        $user_id = 16;

        $user = auth()->user();
        $settings = Setting::where('user_id', $user_id)->first();

        return response()->json([
            'name' => $settings->name,
            'email' => $settings->email,
            'logo' => $settings->logo,
        ], 200);
    }

    public function updateProfileSettings(Request $request)
    {
        $user_id = 16;
        $settings = Setting::firstOrCreate(['user_id' => $user_id]);

        $request->validate([
            'name' => 'string|nullable',
            'email' => 'email|nullable',
            'logo' => 'file|image|mimes:jpeg,png,jpg,gif|max:2048|nullable',
        ]);

        // Handle image upload if provided
        if ($request->hasFile('logo')) {
            // Delete the old logo if it exists
            if ($settings->logo) {
                Storage::delete($settings->logo);
            }

            // Store the new image
            $path = $request->file('logo')->store('logos');
            $settings->logo = $path;
        }

        $settings->name = $request->input('name', $settings->name);
        $settings->email = $request->input('email', $settings->email);
        $settings->save();

        return response()->json([
            'message' => 'Profile settings updated successfully.',
            'data' => $settings,
        ], 200);
    }

    public function updateAccountSettings(Request $request)
    {
        $user = auth()->user();
        $settings = Setting::firstOrCreate(['user_id' => $user->id]);

        $request->validate([
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:6',
        ]);


        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json(['message' => 'Old password does not match.'], 400);
        }

        // Update password
        $settings->password = bcrypt($request->new_password);
        $user->password = bcrypt($request->new_password);
        $user->save();
        $settings->save();

        return response()->json([
            'message' => 'Account settings updated successfully.',
        ], 200);
    }

    public function getNotificationSettings()
    {
        $user_id = 16;
        $settings = Setting::where('user_id', $user_id)->first();

        return response()->json([
            'push_notification' => $settings->push_notification,
            'email_notification' => $settings->email_notification,
            'sms_notification' => $settings->sms_notification,
        ], 200);
    }

    public function updateNotificationSettings(Request $request)
    {
        $user_id = 16;
        $settings = Setting::firstOrCreate(['user_id' => $user_id]);

        $request->validate([
            'push_notification' => 'boolean|nullable',
            'email_notification' => 'boolean|nullable',
            'sms_notification' => 'boolean|nullable',
        ]);

        $settings->push_notification = $request->input('push_notification', $settings->push_notification);
        $settings->email_notification = $request->input('email_notification', $settings->email_notification);
        $settings->sms_notification = $request->input('sms_notification', $settings->sms_notification);
        $settings->save();

        return response()->json([
            'message' => 'Notification settings updated successfully.',
            'data' => $settings,
        ], 200);
    }

    public function getPrivacySettings()
    {
        $user_id = 16;
        $settings = Setting::where('user_id', $user_id)->first();

        return response()->json([
            'privacy' => $settings->privacy,
        ], 200);
    }

    public function updatePrivacySettings(Request $request)
    {
        $user_id = 16;
        $settings = Setting::firstOrCreate(['user_id' => $user_id]);

        $request->validate([
            'privacy' => 'in:private,public|nullable',
        ]);

        $settings->privacy = $request->input('privacy', $settings->privacy);
        $settings->save();

        return response()->json([
            'message' => 'Privacy settings updated successfully.',
            'data' => $settings,
        ], 200);
    }

    public function getAppPreferenceSettings()
    {
        $user_id = 16;
        $settings = Setting::where('user_id', $user_id)->first();

        return response()->json([
            'dark_mode' => $settings->dark_mode,
            'language' => $settings->language,
        ], 200);
    }

    public function updateAppPreferenceSettings(Request $request)
    {
        $user_id = 16;
        $settings = Setting::firstOrCreate(['user_id' => $user_id]);

        $request->validate([
            'dark_mode' => 'boolean|nullable',
            'language' => 'string|nullable',
        ]);

        $settings->dark_mode = $request->input('dark_mode', $settings->dark_mode);
        $settings->language = $request->input('language', $settings->language);
        $settings->save();

        return response()->json([
            'message' => 'App preference settings updated successfully.',
            'data' => $settings,
        ], 200);
    }

    public function getSecuritySettings()
    {
        $user_id = 16;
        $settings = Setting::where('user_id', $user_id)->first();

        return response()->json([
            'two_factor_auth' => $settings->two_factor_auth,
        ], 200);
    }

    public function updateSecuritySettings(Request $request)
    {
        $user_id = 16;
        $settings = Setting::firstOrCreate(['user_id' => $user_id]);

        $request->validate([
            'two_factor_auth' => 'boolean|nullable',
        ]);

        $settings->two_factor_auth = $request->input('two_factor_auth', $settings->two_factor_auth);
        $settings->save();

        return response()->json([
            'message' => 'Security settings updated successfully.',
            'data' => $settings,
        ], 200);
    }


    public function getSocialMediaLinks()
    {
        $user_id = 16;
        $settings = Setting::where('user_id', $user_id)->first();

        if (!$settings) {
            return response()->json([
                'message' => 'Settings not found for this user.',
            ], 404);
        }

        return response()->json([
            'facebook' => $settings->facebook,
            'instagram' => $settings->instagram,
            'linkedin' => $settings->linkedin,
            'twitter' => $settings->twitter,
        ], 200);
    }

    public function updateSocialMediaLinks(Request $request)
    {
        $user_id = 16;
        $settings = Setting::firstOrCreate(['user_id' => $user_id]);

        $request->validate([
            'facebook' => 'nullable|url',
            'instagram' => 'nullable|url',
            'linkedin' => 'nullable|url',
            'twitter' => 'nullable|url',
        ]);

        $settings->facebook = $request->input('facebook', $settings->facebook);
        $settings->instagram = $request->input('instagram', $settings->instagram);
        $settings->linkedin = $request->input('linkedin', $settings->linkedin);
        $settings->twitter = $request->input('twitter', $settings->twitter);
        $settings->save();

        return response()->json([
            'message' => 'Social media links updated successfully.',
            'data' => [
                'facebook' => $settings->facebook,
                'instagram' => $settings->instagram,
                'linkedin' => $settings->linkedin,
                'twitter' => $settings->twitter,
            ],
        ], 200);
    }

    public function getWebsiteInformation(): JsonResponse
    {
        $setting = Setting::first();

        if (!$setting) {
            return response()->json(['message' => 'Website information not found'], 404);
        }
        

        $websiteInformation = [
            'name' => $setting->name,
            'logo' => asset('storage/' . $setting->logo),
            'email' => $setting->email,
            'social_media' => [
                'facebook' => $setting->facebook,
                'instagram' => $setting->instagram,
                'linkedin' => $setting->linkedin,
                'twitter' => $setting->twitter,
            ],
        ];

        return response()->json($websiteInformation, 200);
    }

    public function getCountries(): JsonResponse
    {
        $countries = DB::table('country')->get();

        return response()->json([
            'success' => true,
            'data' => $countries,
        ]);
    }
    
    
    
public function getApiLogs(Request $request): JsonResponse
{
    $perPage = $request->input('per_page', 15); // Default to 15 items per page
    $page = $request->input('page', 1); // Default to page 1
    $api_log = DB::table('api_logs')
        ->orderBy('created_at', 'desc') // Sort by created_at in descending order
        ->paginate($perPage, ['*'], 'page', $page);

    return response()->json([
        'success' => true,
        'data' => $api_log,
    ]);
}
    


}
