<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\User\FilterUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends BaseController
{
    public function __construct(
        private readonly UserService $service
    ) {}

    /**
     * @OA\Get(
     *     path="/users",
     *     summary="Listar e filtrar usuários",
     *     tags={"Usuários"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string"), example="eletricista São Paulo", description="Pesquisa por nome, categoria, cidade ou estado"),
     *     @OA\Parameter(name="categories[]", in="query", required=false, @OA\Schema(type="array", @OA\Items(type="integer")), example={1, 2}),
     *     @OA\Parameter(name="city_id", in="query", required=false, @OA\Schema(type="integer"), example=1),
     *     @OA\Parameter(name="orderBy", in="query", required=false, @OA\Schema(type="string", enum={"popular", "desc", "asc"}), example="popular", description="Ordenação: popular (mais seguidores, padrão), desc (mais recentes), asc (mais antigos)"),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer"), example=20),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada de usuários",
     *
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/User")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Dados inválidos")
     * )
     */
    public function index(FilterUserRequest $request)
    {
        $users = $this->service->list($request->validated(), $request->user()?->id);

        return $this->sendResponse(
            UserResource::collection($users)->response()->getData(true),
            'Usuários listados com sucesso.'
        );
    }

    /**
     * @OA\Get(
     *     path="/users/{user}",
     *     summary="Exibir usuário",
     *     tags={"Usuários"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Usuário encontrado"),
     *     @OA\Response(response=404, description="Usuário não encontrado")
     * )
     */
    public function show(User $user)
    {
        $user = $this->service->findWithRelations($user);

        return $this->sendResponse(
            new UserResource($user),
            'Usuário encontrado.'
        );
    }

    /**
     * @OA\Get(
     *     path="/users/auth",
     *     summary="Retornar usuário autenticado",
     *     tags={"Usuários"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(response=200, description="Usuário autenticado"),
     *     @OA\Response(response=401, description="Não autenticado")
     * )
     */
    public function userAuth(Request $request)
    {
        $user = $this->service->findWithRelations($request->user());

        return $this->sendResponse(
            new UserResource($user),
            'Usuário autenticado.'
        );
    }

    /**
     * @OA\Post(
     *     path="/users/{user}",
     *     summary="Atualizar usuário (usar _method=PUT)",
     *     tags={"Usuários"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer")),
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
     *                 @OA\Property(property="name", type="string", example="João da Silva"),
     *                 @OA\Property(property="username", type="string", example="joao.silva", description="Apenas letras, números, ponto e underscore"),
     *                 @OA\Property(property="email", type="string", format="email", example="joao@email.com"),
     *                 @OA\Property(property="phone", type="string", example="(11) 99999-1234"),
     *                 @OA\Property(property="description", type="string", example="Eletricista profissional com 10 anos de experiência", description="Bio do usuário (máx. 200 caracteres)"),
     *                 @OA\Property(property="is_private", type="boolean", example=false, description="Perfil privado (seguidores precisam de aprovação)"),
     *                 @OA\Property(property="city_id", type="integer", example=1),
     *                 @OA\Property(property="categories[]", type="array", @OA\Items(type="integer"), example={1, 3}),
     *                 @OA\Property(property="avatar", type="string", format="binary", description="Foto de perfil (máx. 5MB). Formatos: jpeg, png, gif, webp"),
     *                 @OA\Property(property="cover", type="string", format="binary", description="Foto de capa (máx. 10MB). Formatos: jpeg, png, gif, webp"),
     *                 @OA\Property(property="remove_avatar", type="boolean", example=false, description="Remover foto de perfil atual"),
     *                 @OA\Property(property="remove_cover", type="boolean", example=false, description="Remover foto de capa atual"),
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Usuário atualizado com sucesso"),
     *     @OA\Response(response=403, description="Não autorizado"),
     *     @OA\Response(response=422, description="Dados inválidos")
     * )
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->safe()->except('categories', 'avatar', 'cover', 'remove_avatar', 'remove_cover');

        $user = $this->service->update(
            $user,
            $data,
            $request->validated('categories'),
            $request->file('avatar'),
            $request->file('cover'),
            (bool) $request->validated('remove_avatar', false),
            (bool) $request->validated('remove_cover', false),
        );

        return $this->sendResponse(
            new UserResource($user),
            'Usuário atualizado com sucesso.'
        );
    }

    /**
     * @OA\Post(
     *     path="/users/follow/{user}",
     *     summary="Seguir/deixar de seguir usuário",
     *     tags={"Usuários"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Follow alterado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="status", type="string", enum={"followed", "unfollowed", "requested", "cancelled"}, example="followed"),
     *             ),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function follow(User $user)
    {
        $result = $this->service->toggleFollow($user, request()->user()->id);

        $messages = [
            'followed' => 'Seguindo usuário.',
            'unfollowed' => 'Deixou de seguir usuário.',
            'requested' => 'Solicitação de follow enviada.',
            'cancelled' => 'Solicitação de follow cancelada.',
        ];

        return $this->sendResponse($result, $messages[$result['status']]);
    }

    /**
     * @OA\Get(
     *     path="/users/pending-followers",
     *     summary="Listar solicitações de follow pendentes",
     *     tags={"Usuários"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada de solicitações pendentes",
     *
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/User")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function pendingFollowers(Request $request)
    {
        $users = $this->service->listPendingFollowers($request->user());

        return $this->sendResponse(
            UserResource::collection($users)->response()->getData(true),
            'Solicitações pendentes listadas com sucesso.'
        );
    }

    /**
     * @OA\Post(
     *     path="/users/accept-follower/{user}",
     *     summary="Aceitar solicitação de follow",
     *     tags={"Usuários"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer"), description="ID do usuário solicitante"),
     *
     *     @OA\Response(response=200, description="Solicitação aceita")
     * )
     */
    public function acceptFollower(User $user, Request $request)
    {
        $this->service->acceptFollowRequest($request->user(), $user->id);

        return $this->sendResponse([], 'Solicitação aceita.');
    }

    /**
     * @OA\Post(
     *     path="/users/reject-follower/{user}",
     *     summary="Rejeitar solicitação de follow",
     *     tags={"Usuários"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer"), description="ID do usuário solicitante"),
     *
     *     @OA\Response(response=200, description="Solicitação rejeitada")
     * )
     */
    public function rejectFollower(User $user, Request $request)
    {
        $this->service->rejectFollowRequest($request->user(), $user->id);

        return $this->sendResponse([], 'Solicitação rejeitada.');
    }

    /**
     * @OA\Post(
     *     path="/users/block/{user}",
     *     summary="Bloquear usuário",
     *     tags={"Usuários"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Usuário bloqueado"),
     *     @OA\Response(response=422, description="Não pode bloquear a si mesmo")
     * )
     */
    public function block(User $user, Request $request)
    {
        if ($request->user()->id === $user->id) {
            return $this->sendError('Você não pode bloquear a si mesmo.', [], 422);
        }

        $this->service->blockUser($request->user(), $user->id);

        return $this->sendResponse([], 'Usuário bloqueado.');
    }

    /**
     * @OA\Post(
     *     path="/users/unblock/{user}",
     *     summary="Desbloquear usuário",
     *     tags={"Usuários"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Usuário desbloqueado")
     * )
     */
    public function unblock(User $user, Request $request)
    {
        $this->service->unblockUser($request->user(), $user->id);

        return $this->sendResponse([], 'Usuário desbloqueado.');
    }

    /**
     * @OA\Get(
     *     path="/users/blocked",
     *     summary="Listar usuários bloqueados",
     *     tags={"Usuários"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada de usuários bloqueados",
     *
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/User")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function blockedUsers(Request $request)
    {
        $users = $this->service->listBlockedUsers($request->user());

        return $this->sendResponse(
            UserResource::collection($users)->response()->getData(true),
            'Usuários bloqueados listados com sucesso.'
        );
    }

    /**
     * @OA\Delete(
     *     path="/users/delete-account",
     *     summary="Excluir conta do usuário autenticado (soft delete, recuperável em até 60 dias)",
     *     tags={"Usuários"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(response=200, description="Conta excluída com sucesso"),
     *     @OA\Response(response=401, description="Não autenticado")
     * )
     */
    public function deleteAccount(Request $request)
    {
        $this->service->deleteAccount($request->user());

        return $this->sendResponse([], 'Conta excluída com sucesso. Você tem até 60 dias para recuperá-la fazendo login.');
    }
}
