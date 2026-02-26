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
 */
class SwaggerController
{
    // Apenas documentação
}
