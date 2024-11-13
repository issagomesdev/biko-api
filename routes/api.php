<?php
  
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
  
use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\api\CategoryController;
use App\Http\Controllers\api\PublicationController;
use App\Http\Controllers\api\UserController;

// categories
Route::apiResource('categories', CategoryController::class)->except([
    'store', 'update', 'destroy'
]);

// auth
Route::controller(AuthController::class)->group(function(){
    Route::post('register', 'register');
    Route::post('login', 'login');
    Route::post('logout', 'logout')->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group( function () {
    // users
    Route::post('users/filter', [UserController::class, 'usersFilter']);
    Route::get('users/auth', [UserController::class, 'userAuth']);
    Route::apiResource('users', UserController::class)->except([
        'store', 'destroy'
    ]);

    // publications
    route::post('publications/filter', [PublicationController::class, 'publicationsFilter']);
    route::post('publications/like/{publication}', [PublicationController::class, 'publicationLike']);
    route::post('publications/comment/{publication}', [PublicationController::class, 'publicationComment']);
    Route::apiResource('publications', PublicationController::class)->except([
        'index'
    ]);
});