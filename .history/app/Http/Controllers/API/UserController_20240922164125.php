<?php

namespace App\Http\Controllers\api;

use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Controllers\api\BaseController as BaseController;

class UserController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::with('categories')->get();
        return $this->sendResponse($users, 'Retrieved successfully.');
    }

    public function usersCategory($id)
    {
        dd($id);
        $users = User::whereHas('categories', function ($query) {
            $query->where('category_id', $id);
        })->get();

        return $this->sendResponse($users, 'Retrieved successfully.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }
}
