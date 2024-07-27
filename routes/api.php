<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PostController;


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
});

Route::group([

    'middleware' => 'auth:api',
    'prefix' => 'profile'

], function ($router) {
    Route::post('update-profile', [ProfileController::class, 'updateProfile']);
});

Route::group([

    'middleware' => 'api',
    'prefix' => 'admin'

], function ($router) {
    Route::post('upload-content-images', [PostController::class, 'uploadContentImages']);
    Route::post('create-post', [PostController::class, 'createPost']);
    Route::get('get-preview-posts', [PostController::class, 'getPreviewPosts']);
    Route::get('get-one-post/{id}', [PostController::class, 'getOnePost']);
});
