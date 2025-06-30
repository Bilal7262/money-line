<?php
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Mail\OtpVerification;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:8',
        ]);

        // $otp = rand(100000, 999999);
        $otp = 123456; // For testing purposes, use a fixed OTP

        $user = User::create([
            'email' => $validated['email'],
            'password' => $validated['password'],
            'otp' => $otp,
            'is_verified' => false,
        ]);

        Mail::raw("Your OTP is $otp", function ($msg) use ($user) {
            $msg->to($user->email)->subject('Verify your email');
        });

        return response()->json([
            'message' => 'User registered. OTP sent.',
        ], 201);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();
        // return $user;

        if ($user->otp !== $request->otp) {
            return response()->json(['message' => 'Invalid OTP'], 422);
        }

        $user->update([
            'otp' => null,
            'is_verified' => true,
            'email_verified_at' => now(),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'OTP verified',
            'token' => $token,
            'user' => $user,
        ]);
    }



    public function handleSocial(Request $request, $provider)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        try {
            $socialUser = Socialite::driver($provider)->stateless()->userFromToken($request->token);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid social token'], 422);
        }

        $user = User::updateOrCreate(
            ['email' => $socialUser->getEmail()],
            [
                'name' => $socialUser->getName() ?? '',
                'email_verified_at' => now(),
                'is_verified' => true,
            ]
        );

        if (! $user->tokens()->exists()) {
            $user->otp = null;
            $user->is_profile_complete = false; // guide them to next steps
            $user->save();
        }

        $token = $user->createToken('social_token')->plainTextToken;

        return response()->json([
            'message' => 'Social login successful',
            'token' => $token,
            'user' => $user
        ]);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($validated)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = $request->user();
        if (!$user->is_verified) {
            return response()->json(['message' => 'Email not verified'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function forgetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $validated['email'])->first();
        $otp = random_int(100000, 999999);
        $user->otp = $otp;
        $user->otp_created_at = now();
        $user->save();

        try {
            Mail::to($user->email)->send(new OtpVerification($otp));
        } catch (\Exception $e) {
            Log::error('Failed to send OTP email: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to send OTP'], 500);
        }

        return response()->json([
            'message' => 'OTP sent for password reset',
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|numeric|digits:6',
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
            ],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user->otp || $user->otp != $validated['otp'] || Carbon::parse($user->otp_created_at)->addMinutes(10)->isPast()) {
            return response()->json(['message' => 'Invalid or expired OTP'], 422);
        }

        $user->password = Hash::make($validated['password']);
        $user->otp = null;
        $user->otp_created_at = null;
        $user->save();

        return response()->json(['message' => 'Password reset successful']);
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'old_password' => 'required|string',
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
            ],
        ]);

        $user = $request->user();

        if (!Hash::check($validated['old_password'], $user->password)) {
            return response()->json(['message' => 'Invalid old password'], 422);
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        return response()->json(['message' => 'Password changed successfully']);
    }


}
