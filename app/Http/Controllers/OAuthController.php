<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Str;

class OAuthController extends Controller
{
    // Redirige a Google
    public function redirectToGoogle() {
        return Socialite::driver('google')->redirect();
    }

    // Callback de Google
    public function handleGoogleCallback()
{
    $googleUser = Socialite::driver('google')->stateless()->user();

    $user = User::firstOrCreate(
        ['email' => $googleUser->getEmail()],
        [
            'name'     => $googleUser->getName(),
            'password' => bcrypt(Str::random(24)),
            'plan_id'  => 1
        ]
    );

    $user->update([
        'name' => $googleUser->getName(),
    ]);

    $token = $user->createToken('auth_token')->plainTextToken;

    // 🔥 Redirige al frontend con el token como query string
    return redirect("http://localhost:3000//login/google?token={$token}&id={$user->id}&plan_id={$user->plan_id}&name=" . urlencode($user->name));
}


public function redirectToFacebook()
{
    return Socialite::driver('facebook')->stateless()->redirect();
}

public function handleFacebookCallback()
{
    $facebookUser = Socialite::driver('facebook')->stateless()->user();

    $user = User::firstOrCreate(
        ['email' => $facebookUser->getEmail()],
        [
            'name' => $facebookUser->getName(),
            'password' => bcrypt(Str::random(16)),
            'provider_id' => $facebookUser->getId(),
            'provider' => 'facebook',
            'plan_id' => 1,
        ]
    );

    $token = $user->createToken('auth_token')->plainTextToken;

    // 🔥 Igual que Google: redirige al frontend con token
    return redirect("http://localhost:3000/login/facebook?token={$token}&id={$user->id}&plan_id={$user->plan_id}&name=" . urlencode($user->name));
}



public function redirectToMicrosoft()
{
    return Socialite::driver('microsoft')
        ->scopes(['User.Read'])
        ->stateless()
        ->redirect();
}

public function handleMicrosoftCallback()
{
    $microsoftUser = Socialite::driver('microsoft')->stateless()->user();

    $user = User::firstOrCreate(
        ['email' => $microsoftUser->getEmail()],
        [
            'name' => $microsoftUser->getName(),
            'password' => bcrypt(Str::random(24)),
            'provider_id' => $microsoftUser->getId(),
            'provider' => 'microsoft',
            'plan_id' => 1,
        ]
    );

    $token = $user->createToken('auth_token')->plainTextToken;

    // 🔥 Redirigir al frontend igual que Google y Facebook
    return redirect("http://localhost:3000/login/microsoft?token={$token}&id={$user->id}&plan_id={$user->plan_id}&name=" . urlencode($user->name));
}






}
