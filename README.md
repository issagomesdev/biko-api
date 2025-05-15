# 🛠️ Biko — Laravel API

<p align="center">
  <a href="#about">About</a> •
  <a href="#route-structure">Route Structure</a> •
  <a href="#models--relationships">Models & Relationships</a> •
  <a href="#getting-started">Getting Started</a> •
  <a href="#authentication-flow">Authentication Flow</a> •
  <a href="#tests">Tests</a> •
  <a href="#references">References</a>
</p>

**Biko** is a platform that connects clients to informal service providers, working like a social network where users can register, share their work, and interact through posts, likes and comments.

This repository contains the **RESTful API** developed in **Laravel**, responsible for all backend operations.

![Status](https://img.shields.io/badge/status-in_development-yellow) 

<h2 id="about"> 📌 About</h2>

This API manages:

- Authentication and registration using Laravel Sanctum
- User management with filtering by category and location
- CRUD for publications
- Like and comment system
- Public access to service categories

<h2 id="route-structure">📁 Route Structure</h2>

### 🔐 Authentication

| Route         | Method | Middleware     | Description         |
|---------------|--------|----------------|---------------------|
| `/register`   | POST   | -              | Register new user   |
| `/login`      | POST   | -              | Login               |
| `/logout`     | POST   | `auth:sanctum` | Logout              |

### 👤 Users

| Route                   | Method | Description                                |
|-------------------------|--------|--------------------------------------------|
| `/users`               | GET    | List all users                             |
| `/users/{id}`          | GET    | Get user details                           |
| `/users/{id}`          | PUT    | Update user                                |
| `/users/filter`        | POST   | Filter users by category/location          |
| `/users/auth`          | GET    | Get authenticated user info                |

### 📢 Publications

| Route                             | Method | Description                        |
|----------------------------------|--------|------------------------------------|
| `/publications`                  | POST   | Create a new publication           |
| `/publications/{id}`             | GET    | Show a publication                 |
| `/publications/{id}`             | PUT    | Update a publication               |
| `/publications/{id}`             | DELETE | Delete a publication               |
| `/publications/filter`           | POST   | Filter publications                |
| `/publications/like/{id}`        | POST   | Like/unlike a publication          |
| `/publications/comment/{id}`     | POST   | Add comment to a publication       |

### 🏷️ Categories

| Route                 | Method | Description                    |
|----------------------|--------|--------------------------------|
| `/categories`        | GET    | List all available categories  |
| `/categories/{id}`   | GET    | Get category details           |

> **Note:** All user and publication routes (except `/categories`) require `auth:sanctum` authentication.

<h2 id="models--relationships">🧱 Models & Relationships</h2>

- `User`
  - `hasMany` Publications
  - `belongsToMany` Categories
- `Publication`
  - `belongsTo` User (author)
  - `belongsToMany` Categories
  - `hasMany` Comments, Likes
- `Category`
  - `belongsToMany` Users, Publications
- `Like`
  - `belongsTo` Publication, `hasOne` User
- `Comment`
  - `belongsTo` User, Publication

<h2 id="getting-started">▶️ Getting Started</h2>

### Requirements

- PHP >= 8.1
- Laravel >= 10
- Composer
- MySQL or compatible DB
- Laravel Sanctum

### Installation

```bash
# Clone the repository
git clone https://github.com/seu-usuario/biko-api.git

cd biko-api

# Install dependencies
composer install

# Copy and edit environment variables
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Link storage
php artisan storage:link

# Serve the application
php artisan serve
```

<h2 id="authentication-flow">🔐 Authentication Flow</h2>

- **Register**: `POST /register` with name, email, password, CPF, location, and optional categories
- **Login**: `POST /login` returns Bearer token
- **Authorization**: Use the token in the request header: `Authorization: Bearer {token}`
- **Logout**: `POST /logout` to invalidate the token

### Example login with curl:

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@email.com","password":"password123"}'
```
<h2 id="tests">🧪 Tests</h2>

To run tests (PHPUnit):

```bash
php artisan test
```

<h2 id="references">🔗 References</h2>

- [Laravel Documentation](https://laravel.com/docs)
- [Laravel Sanctum Documentation](https://laravel.com/docs/sanctum)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Composer Documentation](https://getcomposer.org/doc/)
- [MySQL Documentation](https://dev.mysql.com/doc/)
