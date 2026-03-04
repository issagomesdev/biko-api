# 🛠️ Biko — Social platform for service providers and seekers (Laravel API)

![Laravel](https://img.shields.io/badge/laravel-%23FF2D20.svg?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/php-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/mysql-4479A1.svg?style=for-the-badge&logo=mysql&logoColor=white)
![Docker](https://img.shields.io/badge/docker-%230db7ed.svg?style=for-the-badge&logo=docker&logoColor=white)
![Status](https://img.shields.io/badge/status-beta-blue?style=for-the-badge)

<p align="center">
  <a href="#about">About</a> •
  <a href="#roadmap">Roadmap</a> •
  <a href="#technologies">Technologies</a> •
  <a href="#structure">Structure</a> •
  <a href="#route-structure">Route Structure</a> •
  <a href="#models--relationships">Models & Relationships</a> •
  <a href="#getting-started">Getting Started</a> •
  <a href="#authentication-flow">Authentication Flow</a> •
  <a href="#tests">Tests</a> •
  <a href="#related-projects">Related Projects</a>
</p>

**Biko** is a platform that connects clients to informal service providers, working like a social network where users can register, share their work, and interact through posts, likes and comments.

This repository contains the **RESTful API** developed in **Laravel**, responsible for all backend operations.

> 🔗 The frontend interface built with Next.js is available [here](https://github.com/issagomesdev/biko).

<h2 id="about"> 📌 About</h2>

This project was developed as the final assignment for the Laboratory of Innovative Enterprises course in the Analysis and Systems Development (ADS) program at UNINASSAU. It aims to provide a practical solution connecting informal service providers with potential clients through a social platform, demonstrating the application of software development skills and innovative business concepts learned throughout the course.

💻 You can try the live version at [biko.byissa.dev](https://biko.byissa.dev/)

<h2 id="roadmap"> 🚧 Roadmap</h2>

### ✅ Implemented

- Authentication and registration using Laravel Sanctum
- User management with filtering by category and location
- Follow system with privacy control (public/private profiles, pending requests)
- CRUD for publications with media attachments (images/videos)
- Like, comment and reply system
- Service review and rating system with media support
- Real-time chat with WebSocket (Laravel Reverb)
- Notification system (likes, comments, follows, mentions, reviews, messages)
- Collections system (save/organize publications)
- Block system (restricts messaging, reviews, and profile visibility)
- Account deletion with soft delete (recoverable within 60 days via login)
- Public access to service categories
- Public access to states and cities (location endpoints)
- Swagger/OpenAPI documentation

### 🔄 Planned

- Report system for inappropriate content moderation
- Push notifications

<h2 id="technologies"> 🧪 Technologies</h2>

This project was built using the following technologies and tools:

- [Docker](https://docs.docker.com)
- [Laravel 11](https://laravel.com/docs)
- [Laravel Sanctum](https://laravel.com/docs/sanctum) — Token-based authentication
- [Laravel Reverb](https://laravel.com/docs/reverb) — WebSocket server for real-time chat
- [Spatie Media Library](https://spatie.be/docs/laravel-medialibrary) — File uploads and media conversions
- [L5-Swagger (OpenAPI)](https://github.com/DarkaOnLine/L5-Swagger) — API documentation
- [PHPUnit](https://phpunit.de/documentation.html) — Testing
- [Composer](https://getcomposer.org/doc/)
- [MySQL](https://dev.mysql.com/doc/)

<h2 id="api-documentation">📘 API Documentation</h2>

This API is fully documented using **Swagger (OpenAPI 3)**.

After running the project locally, you can access the interactive documentation at ``url/api/documentation``

Features available in Swagger UI:
- Complete endpoint listing
- Request and response schemas
- Authentication via Bearer Token (Laravel Sanctum)
- Try-it-out requests directly from the browser

<h2 id="structure"> 📁 Structure</h2>

Overview of the main project structure:

```txt
📂 app/
 ┣ 📂 Http/
 ┃ ┣ 📂 Controllers/Api/     # API controllers
 ┃ ┣ 📂 Requests/            # Form request validation
 ┃ ┗ 📂 Resources/           # API resource transformers
 ┣ 📂 Models/                # Eloquent models and relationships
 ┣ 📂 Services/              # Business logic layer
 ┣ 📂 Events/                # WebSocket broadcast events
 ┗ 📂 Console/Commands/      # Scheduled commands

📂 routes/
 ┣ 📄 api.php                # API routes
 ┣ 📄 channels.php           # Broadcast channel authorization
 ┗ 📄 console.php            # Scheduled tasks

📂 database/
 ┣ 📂 migrations/            # Table definitions
 ┣ 📂 factories/             # Model factories for testing
 ┗ 📂 seeders/               # Sample data generators

📂 tests/Feature/            # Feature tests (137 tests)
```

<h2 id="route-structure">📁 Route Structure</h2>

### 🔐 Authentication

| Route         | Method | Middleware     | Description         |
|---------------|--------|----------------|---------------------|
| `/register`   | POST   | -              | Register new user   |
| `/login`      | POST   | -              | Login               |
| `/logout`     | POST   | `auth:sanctum` | Logout              |

### 🏷️ Categories

| Route                    | Method | Description                    |
|--------------------------|--------|--------------------------------|
| `/categories`            | GET    | List all available categories  |
| `/categories/{category}` | GET    | Get category details           |

### 🗺️ Location

| Route                       | Method | Description                    |
|-----------------------------|--------|--------------------------------|
| `/states`                   | GET    | List all states                |
| `/states/{state}/cities`    | GET    | List cities of a state         |

### 👤 Users

| Route                                | Method | Description                          |
|--------------------------------------|--------|--------------------------------------|
| `/users`                             | GET    | List and filter users                |
| `/users/auth`                        | GET    | Get authenticated user info          |
| `/users/{id}`                        | GET    | Get user profile                     |
| `/users/{id}`                        | PUT    | Update user profile                  |
| `/users/delete-account`              | DELETE | Soft delete account (60-day grace)   |
| `/users/follow/{user}`               | POST   | Follow / unfollow user               |
| `/users/pending-followers`           | GET    | List pending follow requests         |
| `/users/accept-follower/{user}`      | POST   | Accept follow request                |
| `/users/reject-follower/{user}`      | POST   | Reject follow request                |
| `/users/blocked`                     | GET    | List blocked users                   |
| `/users/block/{user}`                | POST   | Block user                           |
| `/users/unblock/{user}`              | POST   | Unblock user                         |

### 📢 Publications

| Route                                | Method | Description                          |
|--------------------------------------|--------|--------------------------------------|
| `/publications`                      | GET    | List and filter publications         |
| `/publications`                      | POST   | Create publication                   |
| `/publications/{id}`                 | GET    | Show publication                     |
| `/publications/{id}`                 | PUT    | Update publication                   |
| `/publications/{id}`                 | DELETE | Delete publication                   |
| `/publications/{id}/like`            | POST   | Like / unlike                        |
| `/publications/{id}/comment`         | POST   | Add comment                          |
| `/publications/{id}/comments/{c}`    | DELETE | Delete comment                       |

### ⭐ Reviews

| Route                            | Method | Description                          |
|----------------------------------|--------|--------------------------------------|
| `/users/{user}/reviews`          | GET    | List reviews for a user              |
| `/users/{user}/reviews`          | POST   | Create review (+ up to 5 media)      |
| `/reviews/{review}`              | PUT    | Update review / add or remove media  |
| `/reviews/{review}`              | DELETE | Delete review                        |
| `/reviews/{review}/reply`        | POST   | Reply to a review                    |

### 💬 Chat

| Route                                    | Method | Description                    |
|------------------------------------------|--------|--------------------------------|
| `/conversations`                         | GET    | List conversations             |
| `/conversations/{user}`                  | POST   | Start or get conversation      |
| `/conversations/{conversation}`          | GET    | List messages                  |
| `/conversations/{conversation}/messages` | POST   | Send message                   |
| `/conversations/{conversation}/read`     | POST   | Mark conversation as read      |
| `/messages/{message}`                    | DELETE | Delete message                 |

### 🔔 Notifications

| Route                    | Method | Description                        |
|--------------------------|--------|------------------------------------|
| `/notifications`         | GET    | List notifications (filterable)    |
| `/notifications/unread`  | GET    | Unread count by type               |
| `/notifications/{id}`    | POST   | Mark single notification as read   |
| `/notifications/read-all`| POST   | Mark all as read (filterable)      |

### 📁 Collections

| Route                                        | Method | Description                      |
|----------------------------------------------|--------|----------------------------------|
| `/collections`                               | GET    | List user's collections          |
| `/collections`                               | POST   | Create collection                |
| `/collections/{collection}`                  | GET    | Show collection with publications|
| `/collections/{collection}`                  | PUT    | Rename collection                |
| `/collections/{collection}`                  | DELETE | Delete collection                |
| `/collections/{collection}/{publication}`    | POST   | Toggle publication in collection |

> 📌 For full request/response schemas and try-it-out, see the Swagger docs at `/api/documentation`.

<h2 id="models--relationships">🧱 Models & Relationships</h2>

- `User` — `hasMany` Publications, Reviews, Collections · `belongsToMany` Categories, Followers, Blocks
- `Publication` — `belongsTo` User · `belongsToMany` Categories · `hasMany` Comments, Likes · `hasMedia`
- `Review` — `belongsTo` User, Reviewer · `hasMany` Replies (self-ref) · `hasMedia`
- `Comment` — `belongsTo` User, Publication · `hasMany` Replies (self-ref) · `hasMedia`
- `Collection` — `belongsTo` User · `belongsToMany` Publications
- `Conversation` — `belongsTo` UserOne, UserTwo · `hasMany` Messages
- `Message` — `belongsTo` Conversation, Sender · `belongsTo` ReplyTo (self-ref)
- `Notification` — `belongsTo` User, Sender, Publication
- `State` — `hasMany` Cities
- `City` — `belongsTo` State · used by Users and Publications
- `Category` — `belongsToMany` Users, Publications

<h2 id="getting-started">▶️ Getting Started</h2>

### Requirements

- [Docker](https://www.docker.com)

### Running with Docker (recommended)
```bash
# Clone the repository
git clone https://github.com/issagomesdev/biko-api.git
cd biko-api

# Copy and configure environment variables
cp .env.example .env

# Update these variables in .env:
# DB_HOST=db
# DB_USERNAME=biko_user
# DB_PASSWORD=biko_password
# DB_ROOT_PASSWORD=root_password

# Build and start all containers
docker compose up -d --build

# Install dependencies
docker compose exec app composer install

# Fix storage permissions
docker compose exec app chmod -R 775 storage bootstrap/cache
docker compose exec app chown -R www-data:www-data storage bootstrap/cache

# Generate application key
docker compose exec app php artisan key:generate

# Run migrations and seeders
docker compose exec app php artisan migrate:fresh --seed

# Create storage symlink
docker compose exec app php artisan storage:link

# Generate API documentation
docker compose exec php php artisan l5-swagger:generate
```

The API will be available at `http://localhost:8000`
WebSocket server (Reverb) will be available at `http://localhost:8080`

### Running locally (without Docker)

### Requirements

- PHP >= 8.3
- Laravel 11
- Composer
- MySQL
- [Laravel Reverb](https://laravel.com/docs/reverb)
```bash
# Clone the repository
git clone https://github.com/issagomesdev/biko-api.git
cd biko-api

# Install dependencies
composer install

# Copy and configure environment variables
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations and seeders
php artisan migrate:fresh --seed

# Link storage (for media files)
php artisan storage:link

# Start the WebSocket server (required for chat)
php artisan reverb:start

# Serve the application
php artisan serve
```

<h2 id="authentication-flow">🔐 Authentication Flow</h2>

- **Register**: `POST /api/register` — name, email, password, city_id, optional categories
- **Login**: `POST /api/login` — returns a Bearer token; also restores soft-deleted accounts within 60 days
- **Authorization**: include in every protected request: `Authorization: Bearer {token}`
- **Logout**: `POST /api/logout` — revokes the current token
- **Delete account**: `DELETE /api/users/delete-account` — soft delete, recoverable for 60 days by logging in again

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@email.com","password":"password123"}'
```
<h2 id="tests">🧪 Tests</h2>

The project has **137 feature tests** covering all major endpoints and business rules.

| Suite                   | Tests | Coverage                                              |
|-------------------------|-------|-------------------------------------------------------|
| `AuthTest`              | 11    | Register, login, logout                               |
| `UserTest`              | 16    | CRUD, search, filters                                 |
| `FollowTest`            | 8     | Follow, unfollow, pending, accept, reject             |
| `BlockTest`             | 8     | Block, unblock, list, profile restriction             |
| `PublicationTest`       | 26    | CRUD, likes, comments, date filters                   |
| `ReviewTest`            | 16    | CRUD, replies, duplicate prevention, block checks     |
| `ChatTest`              | 15    | Conversations, messages, mark as read, block checks   |
| `NotificationTest`      | 7     | List, unread count, mark read, filter by type         |
| `CollectionTest`        | 11    | CRUD, toggle publication, default protection          |
| `AccountDeletionTest`   | 7     | Soft delete, restore via login, 60-day purge          |

```bash
php artisan test
```

<h2 id="related-projects">🔗 Related Projects</h2>

🧱 Frontend repository [here](https://github.com/issagomesdev/biko)