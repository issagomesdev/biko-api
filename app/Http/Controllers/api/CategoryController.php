<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;

class CategoryController extends BaseController
{
    public function __construct(
        private readonly CategoryService $service
    ) {}

    /**
     * @OA\Get(
     *     path="/categories",
     *     tags={"Categories"},
     *     summary="Listar categorias",
     *     description="Retorna a lista de categorias de serviços disponíveis",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Categorias listadas com sucesso"
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        return $this->sendResponse(
            CategoryResource::collection($this->service->listAll()),
            'Retrieved successfully.'
        );
    }

    /**
     * @OA\Get(
     *     path="/categories/{category}",
     *     tags={"Categories"},
     *     summary="Detalhar categoria pelo slug",
     *     description="Retorna os dados de uma categoria específica usando o slug.",
     *
     *     @OA\Parameter(
     *         name="category",
     *         in="path",
     *         required=true,
     *         description="Slug da categoria",
     *         @OA\Schema(
     *             type="string",
     *             example="eletricista"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Categoria encontrada com sucesso",
     *         @OA\JsonContent(ref="#/components/schemas/Category")
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Categoria não encontrada"
     *     )
     * )
     */
    public function show(Category $category): JsonResponse
    {
        return $this->sendResponse(
            new CategoryResource($category),
            'Retrieved successfully.'
        );
    }
}
