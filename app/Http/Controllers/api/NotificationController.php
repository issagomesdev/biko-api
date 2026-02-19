<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Notification\FilterNotificationRequest;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Services\NotificationService;

class NotificationController extends BaseController
{
    public function __construct(
        private readonly NotificationService $service
    ) {}

    /**
     * @OA\Get(
     *     path="/notifications",
     *     summary="Listar notificações do usuário autenticado",
     *     tags={"Notificações"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="type", in="query", required=false, @OA\Schema(type="string", enum={"like", "comment", "follow", "follow_request", "mention", "review", "review_reply", "comment_reply", "message"}), description="Filtrar por tipo de notificação"),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer"), example=20),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada de notificações",
     *
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Notification")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(FilterNotificationRequest $request)
    {
        $notifications = $this->service->list(
            $request->user(),
            $request->validated('type'),
            $request->validated('per_page', 20),
        );

        return $this->sendResponse(
            NotificationResource::collection($notifications)->response()->getData(true),
            'Notificações listadas com sucesso.'
        );
    }

    /**
     * @OA\Get(
     *     path="/notifications/unread-count",
     *     summary="Contagem de notificações não lidas",
     *     tags={"Notificações"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Contagem de notificações não lidas por tipo",
     *
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total", type="integer", example=12),
     *                 @OA\Property(property="like", type="integer", example=5),
     *                 @OA\Property(property="comment", type="integer", example=3),
     *                 @OA\Property(property="follow", type="integer", example=2),
     *                 @OA\Property(property="mention", type="integer", example=2),
     *             ),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function unreadCount()
    {
        $counts = $this->service->unreadCount(request()->user());

        return $this->sendResponse($counts, 'Contagem de notificações não lidas.');
    }

    /**
     * @OA\Post(
     *     path="/notifications/{notification}/read",
     *     summary="Marcar notificação como lida",
     *     tags={"Notificações"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="notification", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Notificação marcada como lida"),
     *     @OA\Response(response=403, description="Não autorizado"),
     *     @OA\Response(response=404, description="Notificação não encontrada")
     * )
     */
    public function read(Notification $notification)
    {
        if ($notification->user_id !== request()->user()->id) {
            return $this->sendError('Não autorizado.', [], 403);
        }

        $this->service->markAsRead($notification);

        return $this->sendResponse([], 'Notificação marcada como lida.');
    }

    /**
     * @OA\Post(
     *     path="/notifications/read-all",
     *     summary="Marcar todas as notificações como lidas",
     *     tags={"Notificações"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="type", in="query", required=false, @OA\Schema(type="string", enum={"like", "comment", "follow", "follow_request", "mention", "review", "review_reply", "comment_reply", "message"}), description="Marcar apenas notificações deste tipo"),
     *
     *     @OA\Response(response=200, description="Notificações marcadas como lidas")
     * )
     */
    public function readAll(FilterNotificationRequest $request)
    {
        $this->service->markAllAsRead(
            $request->user(),
            $request->validated('type'),
        );

        return $this->sendResponse([], 'Notificações marcadas como lidas.');
    }
}
