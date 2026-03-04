<?php

namespace App\Http\Controllers\Api;

/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         title="BIKO API",
 *         version="1.0.0",
 *         description="API para gerenciamento de usuários, categorias e publicações"
 *     ),
 *
 *     @OA\Server(
 *         url=APP_URL,
 *         description="Servidor da API"
 *     ),
 *
 *     @OA\Components(
 *         @OA\SecurityScheme(
 *             securityScheme="bearerAuth",
 *             type="http",
 *             scheme="bearer",
 *             bearerFormat="JWT"
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="State",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="São Paulo"),
 *     @OA\Property(property="uf", type="string", example="SP")
 * )
 *
 * @OA\Schema(
 *     schema="City",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Campinas"),
 *     @OA\Property(property="state_id", type="integer", example=1)
 * )
 */
class SwaggerController
{
    // Apenas documentação
}
