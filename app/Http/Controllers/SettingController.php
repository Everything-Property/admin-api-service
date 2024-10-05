<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
            'logo' => 'string|nullable',
        ]);

        $settings->name = $request->input('name', $settings->name);
        $settings->email = $request->input('email', $settings->email);
        $settings->logo = $request->input('logo', $settings->logo);
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
        $user = auth()->user();
        $settings = Setting::where('user_id', $user->id)->first();

        return response()->json([
            'push_notification' => $settings->push_notification,
            'email_notification' => $settings->email_notification,
            'sms_notification' => $settings->sms_notification,
        ], 200);
    }

    public function updateNotificationSettings(Request $request)
    {
        $user = auth()->user();
        $settings = Setting::firstOrCreate(['user_id' => $user->id]);

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
        $user = auth()->user();
        $settings = Setting::where('user_id', $user->id)->first();

        return response()->json([
            'privacy' => $settings->privacy,
        ], 200);
    }


    public function updatePrivacySettings(Request $request)
    {
        $user = auth()->user();
        $settings = Setting::firstOrCreate(['user_id' => $user->id]);

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
        $user = auth()->user();
        $settings = Setting::where('user_id', $user->id)->first();

        return response()->json([
            'dark_mode' => $settings->dark_mode,
            'language' => $settings->language,
        ], 200);
    }

    public function updateAppPreferenceSettings(Request $request)
    {
        $user = auth()->user();
        $settings = Setting::firstOrCreate(['user_id' => $user->id]);

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
        $user = auth()->user();
        $settings = Setting::where('user_id', $user->id)->first();

        return response()->json([
            'two_factor_auth' => $settings->two_factor_auth,
        ], 200);
    }

    public function updateSecuritySettings(Request $request)
    {
        $user = auth()->user();
        $settings = Setting::firstOrCreate(['user_id' => $user->id]);

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





}
