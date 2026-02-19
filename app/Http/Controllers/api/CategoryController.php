<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\CategoryResource;

class CategoryController extends BaseController
{

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
        $categories = Category::all();

        return $this->sendResponse(
            CategoryResource::collection($categories),
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
