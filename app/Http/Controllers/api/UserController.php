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

    public function usersFilter(Request $request)
    {
        $query = User::with('categories');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%");
            });
        }
    
        if ($request->filled('categories') && is_array($request->input('categories')) && count($request->input('categories')) > 0) {
            $categories = $request->input('categories');
            $query->whereHas('categories', function ($q) use ($categories) {
                $q->whereIn('category_id', $categories);
            });
        } else {
            $query->whereHas('categories');
        }
    
        if ($request->filled('state')) {
            $query->where('state', $request->input('state'));
        }
        if ($request->filled('city')) {
            $query->where('city', $request->input('city'));
        }
        if ($request->filled('neighborhood')) {
            $query->where('neighborhood', $request->input('neighborhood'));
        }
        
        $users = $query->get();
    
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

    public function userAuth(Request $request)
    {
        $user = $request->user();
        $user->load('categories');

        return $this->sendResponse($user, 'Retrieved successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $user->update($request->all());
        if(isset($request->categories) && count(($request->categories)) > 0) {
            $user->categories()->sync($request->categories);
        }
        return $this->sendResponse($user, 'Retrieved successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }
}
