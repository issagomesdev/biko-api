<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Publication;
use App\Http\Requests\StorePublicationRequest;
use App\Http\Requests\UpdatePublicationRequest;
use App\Http\Controllers\api\BaseController as BaseController;

class PublicationController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $publications = Publication::with('author', 'categories', 'comments', 'likes')->get();
        return $this->sendResponse($publications, 'Retrieved successfully.');
    }

    public function publicationsCategory($id)
    {
        $publications = Publication::whereHas('categories', function ($query) use ($id) {
            $query->where('category_id', $id);
        })->with('author', 'comments', 'likes')->get();

        return $this->sendResponse($publications, 'Retrieved successfully.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePublicationRequest $request)
    {
        
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
    public function update(UpdatePublicationRequest $request, Publication $publication)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Publication $publication)
    {
        //
    }
}
