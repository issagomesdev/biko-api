<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Publication;
use App\Http\Requests\StorePublicationRequest;
use App\Http\Requests\UpdatePublicationRequest;
use App\Http\Controllers\api\BaseController as BaseController;
use Illuminate\Http\Request;

class PublicationController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $publications = Publication::with('author', 'categories', 'comments', 'likes')
        ->orderBy('created_at', 'desc')
        ->get();
        return $this->sendResponse($publications, 'Retrieved successfully.');
    }

    public function publicationsFilter(Request $request)
    {
        $query = Publication::with('author', 'categories', 'comments', 'likes');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('text', 'LIKE', "%{$search}%");
            });
        }
    
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }
    
        if ($request->filled('categories') && is_array($request->input('categories')) && count($request->input('categories')) > 0) {
            $categories = $request->input('categories');
            $query->whereHas('categories', function ($q) use ($categories) {
                $q->whereIn('category_id', $categories);
            });
        }
    
        if ($request->filled('state')) {
            $query->whereHas('author', function ($q) use ($request) {
                $q->where('state', $request->input('state'));
            });
        }
        if ($request->filled('city')) {
            $query->whereHas('author', function ($q) use ($request) {
                $q->where('city', $request->input('city'));
            });
        }
        if ($request->filled('neighborhood')) {
            $query->whereHas('author', function ($q) use ($request) {
                $q->where('neighborhood', 'LIKE', "%{$request->input('neighborhood')}%");
            });
        }
    
        $orderBy = $request->input('orderBy', 'desc');
        if ($orderBy === 'asc' || $orderBy === 'desc') {
            $query->orderBy('created_at', $orderBy);
        } elseif ($orderBy === 'popular') {
            $query->withCount(['likes', 'comments'])
                  ->orderByRaw('((likes_count + comments_count) / 2) DESC')
                  ->orderBy('comments_count', 'DESC')
                  ->orderBy('created_at', 'ASC');
        }
    
        $publications = $query->get();
    
        return $this->sendResponse($publications, 'Retrieved successfully.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $data['user_id'] = $request->user()->id;
        $publication = Publication::create($data);
        if(isset($request->categories) && count(($request->categories)) > 0) {
            $publication->categories()->attach($request->categories);
        }
        $publication->load('author', 'categories', 'comments', 'likes');
        return $this->sendResponse($publication, 'Retrieved successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Publication $publication)
    {
        $publication->load('author', 'categories', 'comments', 'likes');
        
        return $this->sendResponse($publication, 'Retrieved successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Publication $publication)
    {
        $publication->update($request->all());
        if(isset($request->categories) && count(($request->categories)) > 0) {
            $publication->categories()->sync($request->categories);
        }
        $publication->load('author', 'categories', 'comments', 'likes');
        
        return $this->sendResponse($publication, 'Retrieved successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Publication $publication)
    {
        //
    }
}
