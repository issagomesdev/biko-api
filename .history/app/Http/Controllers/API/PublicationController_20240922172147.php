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
        $publications = Publication::with('author', 'categories', 'comments', 'likes')->fisth();
    dd($publications);
        return $this->sendResponse($publications, 'Retrieved successfully.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePublicationRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Publication $publication)
    {
        //
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
