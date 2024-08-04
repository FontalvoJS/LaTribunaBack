<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PostController;


// AUTH
Route::group([

    'middleware' => 'api',
    'prefix' => 'auth'

], function ($router) {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    Route::post('verify-token/{token}', [AuthController::class, 'verifyToken']);
    Route::post('generate-token/{email}', [AuthController::class, 'generateToken']);
    Route::post('verify-email/', [AuthController::class, 'verifyEmail']);
});

// PROFILE
Route::group([

    'middleware' => 'auth:api',
    'prefix' => 'profile'

], function ($router) {
    Route::post('update-profile', [ProfileController::class, 'updateProfile']);
});

// POSTS AND CONTACT
Route::group([

    'middleware' => 'api',
    'prefix' => 'admin'

], function ($router) {
    Route::post('upload-content-images', [PostController::class, 'uploadContentImages']);
    Route::post('create-post', [PostController::class, 'createPost']);
    Route::post('update-post', [PostController::class, 'updatePost']);
    Route::get('get-preview-posts', [PostController::class, 'getPreviewPosts']);
    Route::get('get-one-post/{id}', [PostController::class, 'getOnePost']);
    Route::delete('delete-post/{id}', [PostController::class, 'deletePost']);
    Route::post('contact', [ContactController::class, 'create']);
});
