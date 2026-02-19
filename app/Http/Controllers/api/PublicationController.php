<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Comment\DeleteCommentRequest;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Requests\Publication\FilterPublicationRequest;
use App\Http\Requests\Publication\StorePublicationRequest;
use App\Http\Requests\Publication\UpdatePublicationRequest;
use App\Http\Resources\PublicationResource;
use App\Models\Comment;
use App\Models\Publication;
use App\Services\PublicationService;

class PublicationController extends BaseController
{
    public function __construct(
        private readonly PublicationService $service
    ) {}

    /**
     * @OA\Get(
     *     path="/publications",
     *     summary="Listar e filtrar publicações",
     *     tags={"Publicações"},
     *
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string"), example="eletricista São Paulo", description="Pesquisa por texto, tags, categoria, cidade ou estado"),
     *     @OA\Parameter(name="type", in="query", required=false, @OA\Schema(type="integer", enum={0, 1}), example=0),
     *     @OA\Parameter(name="categories[]", in="query", required=false, @OA\Schema(type="array", @OA\Items(type="integer")), example={1, 2}),
     *     @OA\Parameter(name="city_id", in="query", required=false, @OA\Schema(type="integer"), example=1),
     *     @OA\Parameter(name="tags[]", in="query", required=false, @OA\Schema(type="array", @OA\Items(type="string"))),
     *     @OA\Parameter(name="date", in="query", required=false, @OA\Schema(type="string", enum={"today", "last_24h", "last_7d", "last_30d"}), description="Preset ou data específica (Y-m-d)"),
     *     @OA\Parameter(name="date_from", in="query", required=false, @OA\Schema(type="string", format="date"), description="Início do intervalo (Y-m-d)"),
     *     @OA\Parameter(name="date_to", in="query", required=false, @OA\Schema(type="string", format="date"), description="Fim do intervalo (Y-m-d)"),
     *     @OA\Parameter(name="orderBy", in="query", required=false, @OA\Schema(type="string", enum={"asc", "desc", "popular"}), example="desc"),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer"), example=20),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada de publicações",
     *
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Publication")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Dados inválidos")
     * )
     */
    public function index(FilterPublicationRequest $request)
    {
        $publications = $this->service->list($request->validated(), $request->user()?->id);

        return $this->sendResponse(
            PublicationResource::collection($publications)->response()->getData(true),
            'Publicações listadas com sucesso.'
        );
    }

    /**
     * @OA\Post(
     *     path="/publications",
     *     summary="Criar publicação",
     *     tags={"Publicações"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *                 required={"text", "type", "city_id"},
     *
     *                 @OA\Property(property="text", type="string", example="Preciso de um eletricista urgente"),
     *                 @OA\Property(property="type", type="integer", example=0),
     *                 @OA\Property(property="city_id", type="integer", example=1),
     *                 @OA\Property(property="categories[]", type="array", @OA\Items(type="integer"), example={1, 3}),
     *                 @OA\Property(property="tags[]", type="array", @OA\Items(type="string"), example={"trabalho braçal", "resultado"}),
     *                 @OA\Property(property="mentions[]", type="array", @OA\Items(type="integer"), description="IDs dos usuários mencionados (além dos @username detectados no texto)", example={1, 5}),
     *                 @OA\Property(property="media[]", type="array", @OA\Items(type="string", format="binary"), description="Fotos e vídeos (máx. 10 arquivos, 50MB cada). Formatos: jpeg, png, gif, webp, mp4, mov, avi, webm"),
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Publicação criada com sucesso"),
     *     @OA\Response(response=422, description="Dados inválidos")
     * )
     */
    public function store(StorePublicationRequest $request)
    {
        $data = $request->safe()->except('categories', 'tags', 'media', 'mentions');
        $data['user_id'] = $request->user()->id;

        $publication = $this->service->create(
            $data,
            $request->validated('categories'),
            $request->validated('tags'),
            $request->file('media', []),
            $request->validated('mentions'),
        );

        return $this->sendResponse(
            new PublicationResource($publication),
            'Publicação criada com sucesso.'
        );
    }

    /**
     * @OA\Get(
     *     path="/publications/{publication}",
     *     summary="Exibir publicação",
     *     tags={"Publicações"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="publication", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Publicação encontrada"),
     *     @OA\Response(response=404, description="Publicação não encontrada")
     * )
     */
    public function show(Publication $publication)
    {
        $authUser = request()->user();
        $author = $publication->load('author')->author;

        // Block check
        if ($authUser && $authUser->id !== $author->id) {
            if ($author->hasBlocked($authUser->id) || $author->isBlockedBy($authUser->id)) {
                return $this->sendError('Publicação não encontrada.', [], 404);
            }
        }

        if ($author->is_private) {
            $isOwner = $authUser && $authUser->id === $author->id;
            $isFollower = $authUser && $author->isFollowedBy($authUser->id);

            if (! $isOwner && ! $isFollower) {
                return $this->sendError('Publicação não encontrada.', [], 404);
            }
        }

        $publication = $this->service->findWithRelations($publication);

        return $this->sendResponse(
            new PublicationResource($publication),
            'Publicação encontrada.'
        );
    }

    /**
     * @OA\Post(
     *     path="/publications/{publication}",
     *     summary="Atualizar publicação (usar _method=PUT)",
     *     tags={"Publicações"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="publication", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *                 required={"_method"},
     *
     *                 @OA\Property(property="_method", type="string", example="PUT", description="Spoofing de método HTTP"),
     *                 @OA\Property(property="text", type="string", example="Texto atualizado da publicação"),
     *                 @OA\Property(property="type", type="integer", example=0),
     *                 @OA\Property(property="city_id", type="integer", example=1),
     *                 @OA\Property(property="categories[]", type="array", @OA\Items(type="integer"), example={1, 3}),
     *                 @OA\Property(property="tags[]", type="array", @OA\Items(type="string"), example={"urgente"}),
     *                 @OA\Property(property="mentions[]", type="array", @OA\Items(type="integer"), description="IDs dos usuários mencionados (além dos @username detectados no texto)", example={1, 5}),
     *                 @OA\Property(property="media[]", type="array", @OA\Items(type="string", format="binary"), description="Novas fotos/vídeos para adicionar (máx. 50MB cada)"),
     *                 @OA\Property(property="remove_media[]", type="array", @OA\Items(type="integer"), description="IDs das mídias a remover", example={1, 3}),
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Publicação atualizada com sucesso"),
     *     @OA\Response(response=403, description="Não autorizado"),
     *     @OA\Response(response=422, description="Dados inválidos")
     * )
     */
    public function update(UpdatePublicationRequest $request, Publication $publication)
    {
        $data = $request->safe()->except('categories', 'tags', 'media', 'remove_media', 'mentions');

        $publication = $this->service->update(
            $publication,
            $data,
            $request->validated('categories'),
            $request->validated('tags'),
            $request->file('media', []),
            $request->validated('remove_media', []),
            $request->validated('mentions'),
        );

        return $this->sendResponse(
            new PublicationResource($publication),
            'Publicação atualizada com sucesso.'
        );
    }

    /**
     * @OA\Delete(
     *     path="/publications/{publication}",
     *     summary="Deletar publicação",
     *     tags={"Publicações"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="publication", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Publicação deletada com sucesso"),
     *     @OA\Response(response=404, description="Publicação não encontrada")
     * )
     */
    public function destroy(Publication $publication)
    {
        $this->service->delete($publication);

        return $this->sendResponse([], 'Publicação deletada com sucesso.');
    }

    /**
     * @OA\Post(
     *     path="/publications/like/{publication}",
     *     summary="Curtir/descurtir publicação",
     *     tags={"Publicações"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="publication", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Like alterado com sucesso")
     * )
     */
    public function like(Publication $publication)
    {
        $liked = $this->service->toggleLike($publication, request()->user()->id);

        return $this->sendResponse(
            ['liked' => $liked],
            $liked ? 'Like adicionado.' : 'Like removido.'
        );
    }

    /**
     * @OA\Post(
     *     path="/publications/comment/{publication}",
     *     summary="Comentar publicação",
     *     tags={"Publicações"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="publication", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *                 required={"comment"},
     *
     *                 @OA\Property(property="comment", type="string", example="Ótimo serviço!"),
     *                 @OA\Property(property="parent_id", type="integer", nullable=true, description="ID do comentário pai (para replies)"),
     *                 @OA\Property(property="media[]", type="array", @OA\Items(type="string", format="binary"), description="Fotos e vídeos (máx. 5 arquivos, 50MB cada)"),
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Comentário adicionado"),
     *     @OA\Response(response=422, description="Dados inválidos")
     * )
     */
    public function comment(StoreCommentRequest $request, Publication $publication)
    {
        $publication = $this->service->addComment(
            $publication,
            $request->user()->id,
            $request->validated('comment'),
            $request->file('media', []),
            $request->validated('parent_id'),
        );

        return $this->sendResponse(
            new PublicationResource($publication),
            'Comentário adicionado com sucesso.'
        );
    }

    /**
     * @OA\Delete(
     *     path="/publications/comment/{comment}",
     *     summary="Deletar comentário",
     *     tags={"Publicações"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="comment", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Comentário deletado com sucesso"),
     *     @OA\Response(response=403, description="Não autorizado"),
     *     @OA\Response(response=404, description="Comentário não encontrado")
     * )
     */
    public function deleteComment(DeleteCommentRequest $request, Comment $comment)
    {
        $this->service->deleteComment($comment);

        return $this->sendResponse([], 'Comentário deletado com sucesso.');
    }
}
