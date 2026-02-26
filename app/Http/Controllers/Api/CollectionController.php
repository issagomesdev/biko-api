<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Collection\DeleteCollectionRequest;
use App\Http\Requests\Collection\StoreCollectionRequest;
use App\Http\Requests\Collection\UpdateCollectionRequest;
use App\Http\Resources\CollectionResource;
use App\Models\Collection;
use App\Models\Publication;
use App\Services\CollectionService;

class CollectionController extends BaseController
{
    public function __construct(
        private readonly CollectionService $service
    ) {}

    /**
     * @OA\Get(
     *     path="/collections",
     *     summary="Listar coleções do usuário autenticado",
     *     tags={"Coleções"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista de coleções",
     *
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Collection")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index()
    {
        $collections = $this->service->list(request()->user());

        return $this->sendResponse(
            CollectionResource::collection($collections),
            'Coleções listadas com sucesso.'
        );
    }

    /**
     * @OA\Post(
     *     path="/collections",
     *     summary="Criar coleção",
     *     tags={"Coleções"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name"},
     *
     *             @OA\Property(property="name", type="string", example="Eletricistas")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Coleção criada com sucesso"),
     *     @OA\Response(response=422, description="Dados inválidos")
     * )
     */
    public function store(StoreCollectionRequest $request)
    {
        $collection = $this->service->create(
            $request->user(),
            $request->validated('name'),
        );

        return $this->sendResponse(
            new CollectionResource($collection),
            'Coleção criada com sucesso.'
        );
    }

    /**
     * @OA\Get(
     *     path="/collections/{collection}",
     *     summary="Exibir coleção com publicações",
     *     tags={"Coleções"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="collection", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Coleção encontrada"),
     *     @OA\Response(response=404, description="Coleção não encontrada")
     * )
     */
    public function show(Collection $collection)
    {
        $collection = $this->service->show($collection);

        return $this->sendResponse(
            new CollectionResource($collection),
            'Coleção encontrada.'
        );
    }

    /**
     * @OA\Put(
     *     path="/collections/{collection}",
     *     summary="Renomear coleção",
     *     tags={"Coleções"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="collection", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name"},
     *
     *             @OA\Property(property="name", type="string", example="Favoritos")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Coleção atualizada com sucesso"),
     *     @OA\Response(response=403, description="Não autorizado (coleção padrão não pode ser renomeada)"),
     *     @OA\Response(response=422, description="Dados inválidos")
     * )
     */
    public function update(UpdateCollectionRequest $request, Collection $collection)
    {
        $collection = $this->service->update(
            $collection,
            $request->validated('name'),
        );

        return $this->sendResponse(
            new CollectionResource($collection),
            'Coleção atualizada com sucesso.'
        );
    }

    /**
     * @OA\Delete(
     *     path="/collections/{collection}",
     *     summary="Deletar coleção",
     *     tags={"Coleções"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="collection", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Coleção deletada com sucesso"),
     *     @OA\Response(response=403, description="Não autorizado (coleção padrão não pode ser deletada)")
     * )
     */
    public function destroy(DeleteCollectionRequest $request, Collection $collection)
    {
        $this->service->delete($collection);

        return $this->sendResponse([], 'Coleção deletada com sucesso.');
    }

    /**
     * @OA\Post(
     *     path="/collections/{collection}/publications/{publication}",
     *     summary="Salvar/remover publicação da coleção (toggle)",
     *     tags={"Coleções"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="collection", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="publication", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Publicação salva/removida da coleção")
     * )
     */
    public function togglePublication(Collection $collection, Publication $publication)
    {
        $saved = $this->service->togglePublication($collection, $publication);

        return $this->sendResponse(
            ['saved' => $saved],
            $saved ? 'Publicação salva na coleção.' : 'Publicação removida da coleção.'
        );
    }
}
