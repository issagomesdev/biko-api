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
    Route::get('users/category/{id}', [UserController::class, 'usersCategory']);
    Route::apiResource('users', UserController::class)->except([
        'store', 'destroy'
    ]);

    // publications
    route::get('publications/category/{id}', [PublicationController::class, 'publicationsCategory']);
    Route::apiResource('publications', PublicationController::class);
});