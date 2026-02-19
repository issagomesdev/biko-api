<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\CollectionController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PublicationController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// categories
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);

// auth
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::post('logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum');

// publications (public)
Route::get('publications', [PublicationController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    // users
    Route::get('users/auth', [UserController::class, 'userAuth']);
    Route::post('users/follow/{user}', [UserController::class, 'follow']);
    Route::get('users/pending-followers', [UserController::class, 'pendingFollowers']);
    Route::post('users/accept-follower/{user}', [UserController::class, 'acceptFollower']);
    Route::post('users/reject-follower/{user}', [UserController::class, 'rejectFollower']);
    Route::get('users/blocked', [UserController::class, 'blockedUsers']);
    Route::delete('users/delete-account', [UserController::class, 'deleteAccount']);
    Route::post('users/block/{user}', [UserController::class, 'block']);
    Route::post('users/unblock/{user}', [UserController::class, 'unblock']);
    Route::apiResource('users', UserController::class)->only([
        'index',
        'show',
        'update',
    ]);

    // collections
    Route::post('collections/{collection}/publications/{publication}', [CollectionController::class, 'togglePublication']);
    Route::apiResource('collections', CollectionController::class)->except([
        'create',
        'edit',
    ]);

    // notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('notifications/read-all', [NotificationController::class, 'readAll']);
    Route::post('notifications/{notification}/read', [NotificationController::class, 'read']);

    // reviews
    Route::get('users/{user}/reviews', [ReviewController::class, 'index']);
    Route::post('users/{user}/reviews', [ReviewController::class, 'store']);
    Route::post('reviews/{review}/reply', [ReviewController::class, 'reply']);
    Route::put('reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('reviews/{review}', [ReviewController::class, 'destroy']);

    // chat
    Route::get('conversations', [ChatController::class, 'index']);
    Route::post('conversations/{user}', [ChatController::class, 'store']);
    Route::get('conversations/{conversation}', [ChatController::class, 'show']);
    Route::post('conversations/{conversation}/messages', [ChatController::class, 'sendMessage']);
    Route::post('conversations/{conversation}/read', [ChatController::class, 'markAsRead']);
    Route::delete('messages/{message}', [ChatController::class, 'deleteMessage']);

    // publications
    Route::post('publications/like/{publication}', [PublicationController::class, 'like']);
    Route::post('publications/comment/{publication}', [PublicationController::class, 'comment']);
    Route::delete('publications/comment/{comment}', [PublicationController::class, 'deleteComment']);
    Route::apiResource('publications', PublicationController::class)->except([
        'index',
    ]);
});
