<?php

namespace App\Docs;

/**
 * @OA\Schema(
 *     schema="Category",
 *     type="object",
 *     title="Category",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Eletricista"),
 *     @OA\Property(property="slug", type="string", example="eletricista"),
 * )
 *
 * @OA\Schema(
 *     schema="UserImage",
 *     type="object",
 *     title="UserImage",
 *     nullable=true,
 *
 *     @OA\Property(property="url", type="string", example="http://localhost/storage/1/avatar.jpg"),
 *     @OA\Property(property="thumb_url", type="string", nullable=true, example="http://localhost/storage/1/conversions/avatar-thumb.jpg"),
 * )
 *
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Hayssa Gomes"),
 *     @OA\Property(property="username", type="string", example="hayssa.gomes"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Eletricista profissional com 10 anos de experiência", maxLength=200),
 *     @OA\Property(property="is_private", type="boolean", example=false),
 *     @OA\Property(property="avatar", ref="#/components/schemas/UserImage", nullable=true),
 *     @OA\Property(property="cover", ref="#/components/schemas/UserImage", nullable=true),
 *     @OA\Property(property="followers_count", type="integer", example=12),
 *     @OA\Property(property="following_count", type="integer", example=8),
 *     @OA\Property(property="average_rating", type="number", format="float", nullable=true, example=4.5, description="Média de estrelas (sempre visível, mesmo em perfis privados)"),
 *     @OA\Property(property="reviews_count", type="integer", example=10),
 *     @OA\Property(property="is_online", type="boolean", example=true, description="Usuário online nos últimos 5 minutos"),
 *     @OA\Property(property="is_following", type="boolean", example=false),
 *     @OA\Property(property="is_pending", type="boolean", example=false),
 *     @OA\Property(property="is_blocked", type="boolean", example=false, description="Se o usuário autenticado bloqueou este usuário"),
 *     @OA\Property(property="last_seen_at", type="string", format="date-time", nullable=true, description="Visível apenas para perfis públicos, seguidores ou o próprio usuário"),
 *     @OA\Property(property="email", type="string", example="hayssa@email.com", description="Visível apenas para perfis públicos, seguidores ou o próprio usuário"),
 *     @OA\Property(property="phone", type="string", nullable=true, example="(11) 99999-1234", description="Visível apenas para perfis públicos, seguidores ou o próprio usuário"),
 *     @OA\Property(property="city", type="object", description="Visível apenas para perfis públicos, seguidores ou o próprio usuário",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="São Paulo"),
 *     ),
 *     @OA\Property(property="categories", type="array", @OA\Items(ref="#/components/schemas/Category")),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Visível apenas para perfis públicos, seguidores ou o próprio usuário"),
 * )
 *
 * @OA\Schema(
 *     schema="Publication",
 *     type="object",
 *     title="Publication",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="text", type="string", example="Preciso de um eletricista para instalação residencial"),
 *     @OA\Property(property="type", type="integer", example=0, description="0=cliente, 1=prestador"),
 *     @OA\Property(property="author", ref="#/components/schemas/User"),
 *     @OA\Property(property="city", type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="São Paulo"),
 *     ),
 *     @OA\Property(property="categories", type="array", @OA\Items(ref="#/components/schemas/Category")),
 *     @OA\Property(property="tags", type="array", @OA\Items(type="string"), example={"urgente", "residencial"}),
 *     @OA\Property(property="media", type="array", @OA\Items(ref="#/components/schemas/PublicationMedia")),
 *     @OA\Property(property="mentions", type="array", @OA\Items(ref="#/components/schemas/User"), description="Usuários mencionados via @username no texto"),
 *     @OA\Property(property="comments", type="array", @OA\Items(ref="#/components/schemas/Comment")),
 *     @OA\Property(property="likes_count", type="integer", example=5),
 *     @OA\Property(property="comments_count", type="integer", example=3),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 * )
 *
 * @OA\Schema(
 *     schema="Comment",
 *     type="object",
 *     title="Comment",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="comment", type="string", example="Ótimo serviço!"),
 *     @OA\Property(property="parent_id", type="integer", nullable=true, example=null, description="ID do comentário pai (null = comentário raiz)"),
 *     @OA\Property(property="author", ref="#/components/schemas/User"),
 *     @OA\Property(property="media", type="array", @OA\Items(ref="#/components/schemas/PublicationMedia")),
 *     @OA\Property(property="replies", type="array", @OA\Items(ref="#/components/schemas/Comment"), description="Respostas ao comentário"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 * )
 *
 * @OA\Schema(
 *     schema="PublicationMedia",
 *     type="object",
 *     title="PublicationMedia",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="foto-servico.jpg"),
 *     @OA\Property(property="mime_type", type="string", example="image/jpeg"),
 *     @OA\Property(property="size", type="integer", example=204800, description="Tamanho em bytes"),
 *     @OA\Property(property="url", type="string", example="http://localhost/storage/1/foto-servico.jpg"),
 *     @OA\Property(property="thumb_url", type="string", nullable=true, example="http://localhost/storage/1/conversions/foto-servico-thumb.jpg", description="URL do thumbnail (null para vídeos)"),
 *     @OA\Property(property="type", type="string", enum={"image", "video"}, example="image"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 * )
 *
 * @OA\Schema(
 *     schema="Collection",
 *     type="object",
 *     title="Collection",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Salvos"),
 *     @OA\Property(property="is_default", type="boolean", example=true),
 *     @OA\Property(property="publications_count", type="integer", example=5),
 *     @OA\Property(property="publications", type="array", @OA\Items(ref="#/components/schemas/Publication")),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 * )
 *
 * @OA\Schema(
 *     schema="Notification",
 *     type="object",
 *     title="Notification",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="type", type="string", enum={"like", "comment", "follow", "follow_request", "mention", "review", "review_reply", "comment_reply", "message"}, example="like"),
 *     @OA\Property(property="sender", ref="#/components/schemas/User"),
 *     @OA\Property(property="publication", ref="#/components/schemas/Publication", nullable=true, description="Null para notificações do tipo follow"),
 *     @OA\Property(property="read_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 * )
 *
 * @OA\Schema(
 *     schema="Review",
 *     type="object",
 *     title="Review",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="stars", type="integer", nullable=true, example=5, description="1-5 estrelas (null em replies)"),
 *     @OA\Property(property="comment", type="string", example="Excelente profissional!"),
 *     @OA\Property(property="reviewer", ref="#/components/schemas/User"),
 *     @OA\Property(property="media", type="array", @OA\Items(ref="#/components/schemas/PublicationMedia")),
 *     @OA\Property(property="replies", type="array", @OA\Items(ref="#/components/schemas/Review"), description="Respostas à avaliação"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 * )
 *
 * @OA\Schema(
 *     schema="Conversation",
 *     type="object",
 *     title="Conversation",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="other_user", ref="#/components/schemas/User"),
 *     @OA\Property(property="latest_message", ref="#/components/schemas/Message", nullable=true),
 *     @OA\Property(property="unread_count", type="integer", example=3),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 * )
 *
 * @OA\Schema(
 *     schema="Message",
 *     type="object",
 *     title="Message",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="body", type="string", example="Olá, tudo bem?"),
 *     @OA\Property(property="sender", ref="#/components/schemas/User"),
 *     @OA\Property(property="reply_to", ref="#/components/schemas/Message", nullable=true, description="Mensagem sendo respondida"),
 *     @OA\Property(property="read_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 * )
 */
class SwaggerSchemas {}
