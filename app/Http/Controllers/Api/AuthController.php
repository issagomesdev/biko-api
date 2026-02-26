<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends BaseController
{
    public function __construct(
        private readonly AuthService $service
    ) {}

    /**
     * @OA\Post(
     *     path="/register",
     *     tags={"Auth"},
     *     summary="Cadastrar novo usuário",
     *     description="Cria um novo usuário e retorna token de autenticação",
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","city_id"},
     *             @OA\Property(property="name", type="string", example="Fulano de tal"),
     *             @OA\Property(property="email", type="string", format="email", example="fulano@email.com"),
     *             @OA\Property(property="phone", type="string", example="(11) 99999-1234"),
     *             @OA\Property(property="password", type="string", format="password", example="12345678"),
     *             @OA\Property(property="city_id", type="integer", example=10, description="ID da cidade (cities.id)"),
     *             @OA\Property(
     *                 property="categories",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 example={1,2,3}
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Cadastro realizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="token", type="string"),
     *                 @OA\Property(property="data", type="object")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     )
     * )
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->service->register($request->validated());

        return $this->sendResponse([
            'token' => $result['token'],
            'data'  => new UserResource($result['data']),
        ], 'Cadastro realizado com sucesso!');
    }

    /**
     * @OA\Post(
     *     path="/login",
     *     tags={"Auth"},
     *     summary="Autenticar usuário",
     *     description="Realiza login e retorna token de autenticação. Se a conta estiver soft-deleted há menos de 60 dias, ela é restaurada automaticamente.",
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="fulano@email.com"),
     *             @OA\Property(property="password", type="string", format="password", example="12345678")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Login realizado com sucesso"
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Credenciais inválidas ou conta permanentemente excluída"
     *     )
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->service->login($request->email, $request->password);

        $data = [
            'token' => $result['token'],
            'data'  => new UserResource($result['data']),
        ];

        if ($result['restored']) {
            $data['restored'] = true;
            return $this->sendResponse($data, 'Conta restaurada com sucesso! Atualize seus dados de perfil.');
        }

        return $this->sendResponse($data, 'Login realizado com sucesso!');
    }

    /**
     * @OA\Post(
     *     path="/logout",
     *     tags={"Auth"},
     *     summary="Logout do usuário autenticado",
     *     description="Invalida o token atual do usuário",
     *
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Logout realizado com sucesso"
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Não autenticado"
     *     )
     * )
     */
    public function logout(): JsonResponse
    {
        $this->service->logout(auth()->user());

        return $this->sendResponse(null, 'Desconectado');
    }
}
