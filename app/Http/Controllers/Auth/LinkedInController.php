<?php

namespace App\Http\Controllers\Auth;

use App\User;
use Auth;
use Laravel\Socialite\Two\InvalidStateException;
use Socialite;

use Illuminate\Http\Request;

class LinkedInController extends \App\Http\Controllers\Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('auth');
    }

    public function login()
    {
        return Socialite::with('linkedin')->redirect();
    }

    public function callback(Request $request)
    {
        try {
            $linkedinUser = Socialite::driver('linkedin')->user();
        } catch (InvalidStateException $e) {
            return "Invalid state exception";
        }

        $email = $linkedinUser->email;

        $user = Auth::user();
        if (! $user) {
            $user = User::findByEmail($email);
            if (! $user) {
                $user = User::findStubbedUserByName($linkedinUser->name);
                if ($user) {
                    $user->email = $linkedinUser->email;
                }
            }
        }

        if (! $user) {
            $user = new User;
            $user->email = $email;
            $user->name = $linkedinUser->name;
            $user->password = md5(env('APP_KEY') . time());
            $user->username = preg_replace("/[^a-z0-9.]+/i", "", strtolower($user->name));
            $user->headline = $user->name . " is an interesting character... (click here to edit if you own this page)";
            $user->about = "Click here to edit";
            $user->save();
        }

        $imageUrl = $linkedinUser->avatar;
        if (! $user->avatar_path && $imageUrl) {
            $fileName = $user->id . "_linkedin.jpg";
            $user->avatar_path = "/avatars/$fileName";

            $img = public_path("avatars/$fileName");
            file_put_contents($img, file_get_contents($imageUrl));
        }

        $user->linkedin_token = $linkedinUser->token;
        \Log::info("LinkedIn Auth: " . json_encode($linkedinUser, true));
        $user->save();

        Auth::login($user, true);

        return view('auth.linkedin-callback', [
            'user' => $user,
        ]);
    }

    public function me(Request $request) {
        $user = Auth::user();
        if (! $user) {
            return ['message' => 'Not logged in'];
        }

        return $user->toArrayForCookie();
    }

    public function logout(Request $request) {
        Auth::logout();

        return response(['success' => 1]);
    }
}