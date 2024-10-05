<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class LoginController extends Controller
{
    public function redirectToFacebook()
    {
        return Socialite::driver('facebook')->redirect();
    }

    // public function handleFacebookCallback(Request $request)
    // {
    //     $facebookUser = Socialite::driver('facebook')->stateless()->user();


    //     $email = $facebookUser->email;
    //     $name = $facebookUser->name;

    //     // Split the name into first and last names
    //     $nameParts = explode(' ', $name);
    //     $firstName = $nameParts[0];
    //     $lastName = isset($nameParts[1]) ? $nameParts[1] : '';

    //     // Check if the user already exists in the database
    //     $user = User::where('email', $email)->first();

    //     if ($user) {
    //         // User exists, authenticate and generate token
    //         auth()->login($user);

    //         dd($user);

    //         $token = $user->createToken('auth_token')->plainTextToken;

    //         // Prepare user data
    //         $userDto = [
    //             'id' => $user->id,
    //             'firstName' => $user->firstName,
    //             'lastName' => $user->lastName,
    //             'phone' => $user->phone,
    //             'email' => $user->email,
    //             'username' => $user->username,
    //             'accountType' => $user->accountType,
    //             'subAccountType' => $user->subAccountType,
    //             'profilePicture' => $user->profilePicture,
    //             'kycVerified' => $user->kycVerified,
    //             'bannerImage' => $user->bannerImage,
    //             'token' => $token
    //         ];

    //         // Construct URL for the dashboard with user and token data as parameters
    //         $dashboardUrl = 'http://everythingproperty.ng/dashboard';
    //         $queryParams = http_build_query($userDto);
    //         $dashboardUrl .= '?' . $queryParams;

    //         // Redirect user to the dashboard URL
    //         return new \Illuminate\Http\RedirectResponse($dashboardUrl);
    //     } else {
    //         // User doesn't exist, redirect to registration page
    //         return new RedirectResponse('http://everythingproperty.ng/register?email=' . urlencode($email) . '&firstName=' . urlencode($firstName) . '&lastName=' . urlencode($lastName));
    //     }
    // }

    public function handleFacebookCallback(Request $request)
    {
        // Retrieve user information from Facebook
        $facebookUser = Socialite::driver('facebook')->stateless()->user();

        // Get the user's email and name from Facebook data
        $email = $facebookUser->email;
        $name = $facebookUser->name;

        // Split the name into first and last names
        $nameParts = explode(' ', $name);
        $firstName = $nameParts[0];
        $lastName = isset($nameParts[1]) ? $nameParts[1] : '';

        // Check if the user exists in the Laravel database
        $user = User::where('email', $email)->first();

        if ($user) {
            // Make a request to the Symfony endpoint to get the JWT token
            $response = Http::post('https://api.everythingproperty.ng/v1/user/get-token', [
                'email' => $email,
            ]);

            if ($response->successful()) {
                // Extract the token from the response
                $jwtToken = $response->json()['token'];

                // Store the JWT token in session or cookie for further use
                session(['jwt_token' => $jwtToken]);

                // Log the user in within Laravel
                auth()->login($user);

                // Redirect the user to the dashboard or intended page
                return redirect()->intended('/dashboard')->with('token', $jwtToken);
            } else {
                // Handle the error if the token is not retrieved
                return redirect('/login')->withErrors(['error' => 'Unable to retrieve JWT token']);
            }
        } else {
            // User doesn't exist, redirect to the registration page with user data
            return new RedirectResponse('http://everythingproperty.ng/register?email=' . urlencode($email) . '&firstName=' . urlencode($firstName) . '&lastName=' . urlencode($lastName));
        }
    }
}
