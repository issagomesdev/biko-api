<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Review\DeleteReviewRequest;
use App\Http\Requests\Review\StoreReviewReplyRequest;
use App\Http\Requests\Review\StoreReviewRequest;
use App\Http\Requests\Review\UpdateReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Models\User;
use App\Services\ReviewService;
use Illuminate\Http\Request;

class ReviewController extends BaseController
{
    public function __construct(
        private readonly ReviewService $service
    ) {}

    /**
     * @OA\Get(
     *     path="/users/{user}/reviews",
     *     summary="Listar avaliações de um usuário",
     *     tags={"Avaliações"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer"), example=20),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada de avaliações",
     *
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Review")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(response=403, description="Perfil privado — apenas seguidores podem ver avaliações"),
     *     @OA\Response(response=404, description="Usuário não encontrado")
     * )
     */
    public function index(User $user, Request $request)
    {
        $authUser = $request->user();

        if ($authUser && $authUser->id !== $user->id) {
            if ($user->hasBlocked($authUser->id) || $user->isBlockedBy($authUser->id)) {
                return $this->sendError('Acesso negado.', [], 403);
            }
        }

        if ($user->is_private) {
            if (! $authUser || ($authUser->id !== $user->id && ! $user->isFollowedBy($authUser->id))) {
                return $this->sendError('Acesso negado.', [], 403);
            }
        }

        $reviews = $this->service->listForUser($user, (int) $request->query('per_page', 20), $authUser?->id);

        return $this->sendResponse(
            ReviewResource::collection($reviews)->response()->getData(true),
            'Avaliações listadas com sucesso.'
        );
    }

    /**
     * @OA\Post(
     *     path="/users/{user}/reviews",
     *     summary="Criar avaliação para um usuário",
     *     tags={"Avaliações"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *                 required={"stars", "comment"},
     *
     *                 @OA\Property(property="stars", type="integer", example=5, description="1 a 5 estrelas"),
     *                 @OA\Property(property="comment", type="string", example="Excelente profissional!"),
     *                 @OA\Property(property="media[]", type="array", @OA\Items(type="string", format="binary"), description="Fotos e vídeos (máx. 5 arquivos, 50MB cada)"),
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Avaliação criada com sucesso"),
     *     @OA\Response(response=403, description="Perfil privado — apenas seguidores podem avaliar"),
     *     @OA\Response(response=409, description="Você já avaliou este usuário"),
     *     @OA\Response(response=422, description="Dados inválidos")
     * )
     */
    public function store(StoreReviewRequest $request, User $user)
    {
        $authUser = $request->user();

        if ($authUser->id === $user->id) {
            return $this->sendError('Você não pode avaliar a si mesmo.', [], 403);
        }

        if ($user->hasBlocked($authUser->id) || $user->isBlockedBy($authUser->id)) {
            return $this->sendError('Não é possível avaliar este usuário.', [], 403);
        }

        if ($user->is_private && ! $user->isFollowedBy($authUser->id)) {
            return $this->sendError('Apenas seguidores podem avaliar este perfil.', [], 403);
        }

        $existing = Review::where('user_id', $user->id)
            ->where('reviewer_id', $authUser->id)
            ->whereNull('parent_id')
            ->exists();

        if ($existing) {
            return $this->sendError('Você já avaliou este usuário.', [], 409);
        }

        $review = $this->service->create(
            $user,
            $authUser->id,
            $request->validated('stars'),
            $request->validated('comment'),
            $request->file('media', []),
        );

        return $this->sendResponse(
            new ReviewResource($review),
            'Avaliação criada com sucesso.'
        );
    }

    /**
     * @OA\Post(
     *     path="/reviews/{review}/reply",
     *     summary="Responder a uma avaliação",
     *     tags={"Avaliações"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="review", in="path", required=true, @OA\Schema(type="integer")),
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
     *                 @OA\Property(property="comment", type="string", example="Obrigado pela avaliação!"),
     *                 @OA\Property(property="media[]", type="array", @OA\Items(type="string", format="binary"), description="Fotos e vídeos (máx. 5 arquivos, 50MB cada)"),
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Resposta criada com sucesso"),
     *     @OA\Response(response=404, description="Avaliação não encontrada"),
     *     @OA\Response(response=422, description="Dados inválidos")
     * )
     */
    public function reply(StoreReviewReplyRequest $request, Review $review)
    {
        $reply = $this->service->reply(
            $review,
            $request->user()->id,
            $request->validated('comment'),
            $request->file('media', []),
        );

        return $this->sendResponse(
            new ReviewResource($reply),
            'Resposta criada com sucesso.'
        );
    }

    /**
     * @OA\Post(
     *     path="/reviews/{review}",
     *     summary="Editar avaliação ou resposta (usar _method=PUT)",
     *     tags={"Avaliações"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="review", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *                 required={"_method"},
     *
     *                 @OA\Property(property="_method", type="string", example="PUT", description="Spoofing de método HTTP"),
     *                 @OA\Property(property="stars", type="integer", example=4, description="1 a 5 estrelas (apenas avaliações raiz)"),
     *                 @OA\Property(property="comment", type="string", example="Atualizando minha avaliação"),
     *                 @OA\Property(property="media[]", type="array", @OA\Items(type="string", format="binary"), description="Novas mídias (máx. 5 no total, 50MB cada)"),
     *                 @OA\Property(property="remove_media[]", type="array", @OA\Items(type="integer"), description="IDs das mídias a remover", example={1, 3}),
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Avaliação atualizada com sucesso"),
     *     @OA\Response(response=403, description="Não autorizado"),
     *     @OA\Response(response=422, description="Dados inválidos")
     * )
     */
    public function update(UpdateReviewRequest $request, Review $review)
    {
        $data = $request->safe()->except('media', 'remove_media');

        $review = $this->service->update(
            $review,
            $data,
            $request->file('media', []),
            $request->validated('remove_media', []),
        );

        return $this->sendResponse(
            new ReviewResource($review),
            'Avaliação atualizada com sucesso.'
        );
    }

    /**
     * @OA\Delete(
     *     path="/reviews/{review}",
     *     summary="Deletar avaliação ou resposta",
     *     tags={"Avaliações"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="review", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Avaliação deletada com sucesso"),
     *     @OA\Response(response=403, description="Não autorizado"),
     *     @OA\Response(response=404, description="Avaliação não encontrada")
     * )
     */
    public function destroy(DeleteReviewRequest $_request, Review $review)
    {
        $this->service->delete($review);

        return $this->sendResponse([], 'Avaliação deletada com sucesso.');
    }
}
