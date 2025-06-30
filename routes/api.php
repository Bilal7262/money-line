<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

use App\Http\Controllers\User\{AuthController,ProfileController,SportController,CommunityController};

Route::prefix('auth')->group(function () {
   Route::post('register', [AuthController::class, 'register']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);

    Route::get('/auth/redirect/{provider}', function ($provider) {
        return Socialite::driver($provider)->redirect();
    });
    Route::post('social/callback/{provider}', [AuthController::class, 'handleSocial']); // google/facebook/apple
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('forget-password', [AuthController::class, 'forgetPassword'])->middleware('throttle:10,1');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:10,1');
    Route::middleware('auth:sanctum')->group(function(){
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('submit-referral', [ProfileController::class, 'submitReferral']);
        Route::post('complete-profile', [ProfileController::class, 'completeProfile']);
        Route::post('select-sports', [ProfileController::class, 'selectSports']);
        Route::post('select-teams', [ProfileController::class, 'selectTeams']);
        Route::get('profile', [ProfileController::class, 'showProfile']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
    });


    
});
    Route::middleware('auth:sanctum')->group(function(){
        Route::prefix('community')->group(function () {
            Route::post('posts', [CommunityController::class, 'createPost']);
            Route::get('posts', [CommunityController::class, 'showPosts']);
            Route::get('posts/{post}', [CommunityController::class, 'showPost']);
            Route::post('posts/{post}/like', [CommunityController::class, 'likePost']);
            Route::post('posts/{post}/bookmark', [CommunityController::class, 'bookmarkPost']);
            Route::post('posts/{post}/report', [CommunityController::class, 'reportPost']);
            Route::post('posts/{post}/comments', [CommunityController::class, 'createComment']);
            Route::post('comments/{comment}/like', [CommunityController::class, 'likeComment']);
        });
    });
    Route::get('sports', [SportController::class, 'showSports']);
    Route::get('teams', [SportController::class, 'showTeams']);
    Route::get('teams-by-sports', [SportController::class, 'getTeamsBySport']);
