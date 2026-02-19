<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\UserResource;

class AuthController extends BaseController
{
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
     *             required={"name","username","email","password","city_id"},
     *             @OA\Property(property="name", type="string", example="Fulano de tal"),
     *             @OA\Property(property="username", type="string", example="fulano.tal", description="Apenas letras, números, ponto e underscore"),
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
        $data = $request->validated();

        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        if (!empty($data['categories'])) {
            $user->categories()->sync($data['categories']);
        }

        $user->load('categories');

        return $this->sendResponse([
            'token' => $user->createToken('api')->plainTextToken,
            'data' => new UserResource($user),
        ], 'Cadastro realizado com sucesso!');
    }
    /**
     * @OA\Post(
     *     path="/login",
     *     tags={"Auth"},
     *     summary="Autenticar usuário",
     *     description="Realiza login e retorna token de autenticação",
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
     *         description="Credenciais inválidas"
     *     )
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // Check for soft-deleted account (recovery within 60 days)
        $deletedUser = User::onlyTrashed()
            ->where('email', $request->email)
            ->first();

        if ($deletedUser) {
            if ($deletedUser->deleted_at->diffInDays(now()) > 60) {
                return $this->sendError(
                    'Esta conta foi excluída permanentemente.',
                    [],
                    401
                );
            }

            if (!Hash::check($request->password, $deletedUser->password)) {
                return $this->sendError(
                    'Credenciais inválidas',
                    ['email' => ['E-mail ou senha incorretos']],
                    401
                );
            }

            $deletedUser->restore();

            return $this->sendResponse([
                'token' => $deletedUser->createToken('api')->plainTextToken,
                'data'  => new UserResource($deletedUser->load('categories')),
                'restored' => true,
            ], 'Conta restaurada com sucesso! Atualize seus dados de perfil.');
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return $this->sendError(
                'Credenciais inválidas',
                ['email' => ['E-mail ou senha incorretos']],
                401
            );
        }

        $user = Auth::user()->load('categories');

        return $this->sendResponse([
            'token' => $user->createToken('api')->plainTextToken,
            'data'  => new UserResource($user),
        ], 'Login realizado com sucesso!');
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
        auth()->user()->currentAccessToken()->delete();

        return $this->sendResponse(null, 'Desconectado');
    }
}
