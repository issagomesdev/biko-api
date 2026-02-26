<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Chat\DeleteMessageRequest;
use App\Http\Requests\Chat\SendMessageRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Http\Request;

class ChatController extends BaseController
{
    public function __construct(
        private readonly ChatService $service
    ) {}

    /**
     * @OA\Get(
     *     path="/conversations",
     *     summary="Listar conversas do usuário autenticado",
     *     tags={"Chat"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer"), example=20),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada de conversas",
     *
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Conversation")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $conversations = $this->service->listConversations(
            $request->user(),
            (int) $request->query('per_page', 20),
        );

        return $this->sendResponse(
            ConversationResource::collection($conversations)->response()->getData(true),
            'Conversas listadas com sucesso.'
        );
    }

    /**
     * @OA\Post(
     *     path="/conversations/{user}",
     *     summary="Iniciar ou obter conversa com um usuário",
     *     tags={"Chat"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Conversa obtida/criada com sucesso"),
     *     @OA\Response(response=403, description="Não autorizado a iniciar conversa com este usuário"),
     *     @OA\Response(response=404, description="Usuário não encontrado")
     * )
     */
    public function store(User $user, Request $request)
    {
        $this->service->assertCanStartConversation($request->user(), $user);

        $conversation = $this->service->findOrCreateConversation($request->user()->id, $user->id);
        $conversation->load(['userOne.media', 'userTwo.media', 'latestMessage.sender']);

        return $this->sendResponse(
            new ConversationResource($conversation),
            'Conversa obtida com sucesso.'
        );
    }

    /**
     * @OA\Get(
     *     path="/conversations/{conversation}",
     *     summary="Ver mensagens de uma conversa",
     *     tags={"Chat"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="conversation", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer"), example=50),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Mensagens da conversa",
     *
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Message")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(response=403, description="Não autorizado"),
     *     @OA\Response(response=404, description="Conversa não encontrada")
     * )
     */
    public function show(Conversation $conversation, Request $request)
    {
        $this->service->assertParticipant($conversation, $request->user()->id);

        $messages = $this->service->getMessages(
            $conversation,
            (int) $request->query('per_page', 50),
        );

        return $this->sendResponse(
            MessageResource::collection($messages)->response()->getData(true),
            'Mensagens listadas com sucesso.'
        );
    }

    /**
     * @OA\Post(
     *     path="/conversations/{conversation}/messages",
     *     summary="Enviar mensagem em uma conversa",
     *     tags={"Chat"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="conversation", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"body"},
     *
     *             @OA\Property(property="body", type="string", example="Olá, tudo bem?"),
     *             @OA\Property(property="reply_to_id", type="integer", nullable=true, description="ID da mensagem sendo respondida")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Mensagem enviada com sucesso"),
     *     @OA\Response(response=403, description="Não autorizado"),
     *     @OA\Response(response=422, description="Dados inválidos")
     * )
     */
    public function sendMessage(SendMessageRequest $request, Conversation $conversation)
    {
        $message = $this->service->sendMessage(
            $conversation,
            $request->user()->id,
            $request->validated('body'),
            $request->validated('reply_to_id'),
        );

        return $this->sendResponse(
            new MessageResource($message),
            'Mensagem enviada com sucesso.'
        );
    }

    /**
     * @OA\Post(
     *     path="/conversations/{conversation}/read",
     *     summary="Marcar mensagens da conversa como lidas",
     *     tags={"Chat"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="conversation", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Mensagens marcadas como lidas"),
     *     @OA\Response(response=403, description="Não autorizado")
     * )
     */
    public function markAsRead(Conversation $conversation, Request $request)
    {
        $this->service->assertParticipant($conversation, $request->user()->id);

        $this->service->markAsRead($conversation, $request->user()->id);

        return $this->sendResponse([], 'Mensagens marcadas como lidas.');
    }

    /**
     * @OA\Delete(
     *     path="/messages/{message}",
     *     summary="Deletar mensagem",
     *     tags={"Chat"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="message", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Mensagem deletada com sucesso"),
     *     @OA\Response(response=403, description="Não autorizado"),
     *     @OA\Response(response=404, description="Mensagem não encontrada")
     * )
     */
    public function deleteMessage(DeleteMessageRequest $_request, Message $message)
    {
        $this->service->deleteMessage($message);

        return $this->sendResponse([], 'Mensagem deletada com sucesso.');
    }
}
