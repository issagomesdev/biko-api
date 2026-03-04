<?php

namespace App\Http\Controllers\Api;

use App\Models\State;
use Illuminate\Http\JsonResponse;

class LocationController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/states",
     *     tags={"Localização"},
     *     summary="Listar estados",
     *     description="Retorna a lista de todos os estados ordenados por nome",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Estados listados com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Retrieved successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/State")
     *             )
     *         )
     *     )
     * )
     */
    public function states(): JsonResponse
    {
        $states = State::orderBy('name')->get(['id', 'name', 'uf']);

        return $this->sendResponse($states, 'Retrieved successfully.');
    }

    /**
     * @OA\Get(
     *     path="/states/{state}/cities",
     *     tags={"Localização"},
     *     summary="Listar cidades de um estado",
     *     description="Retorna a lista de cidades de um estado específico ordenadas por nome",
     *
     *     @OA\Parameter(
     *         name="state",
     *         in="path",
     *         required=true,
     *         description="ID do estado",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Cidades listadas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Retrieved successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/City")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Estado não encontrado"
     *     )
     * )
     */
    public function cities(State $state): JsonResponse
    {
        $cities = $state->cities()->orderBy('name')->get(['id', 'name', 'state_id']);

        return $this->sendResponse($cities, 'Retrieved successfully.');
    }
}
