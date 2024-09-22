<?php

namespace App\Http\Controllers\api;

use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Controllers\api\BaseController as BaseController;
use Illuminate\Http\Request;

class UserController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::with('categories', 'publications')->get();
        return $this->sendResponse($users, 'Retrieved successfully.');
    }

    public function usersCategory($id)
    {
        $users = User::whereHas('categories', function ($query) use ($id) {
            $query->where('category_id', $id);
        })->with('publications')->get();

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
        $user->load('categories', 'publications');

        return $this->sendResponse($user, 'Retrieved successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $publication->update($request->all());

        return $this->sendResponse($publication, 'Retrieved successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }
}
