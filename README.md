# 🛠️ Biko — Social platform for service providers and seekers (Laravel API)

![Laravel](https://img.shields.io/badge/laravel-%23FF2D20.svg?style=for-the-badge&logo=laravel&logoColor=white) 

![Status](https://img.shields.io/badge/status-in_development-yellow) 

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

🔗 The frontend interface built with Next.js is available [here](https://github.com/issagomesdev/biko).


<h2 id="about"> 📌 About</h2>

This project was developed as the final assignment for the Laboratory of Innovative Enterprises course in the Analysis and Systems Development (ADS) program at UNINASSAU. It aims to provide a practical solution connecting informal service providers with potential clients through a social platform, demonstrating the application of software development skills and innovative business concepts learned throughout the course.

💻 You can try the live version at [biko.byissa.tech](https://biko.byissa.tech/)

<h2 id="roadmap"> 🚧 Roadmap</h2>

### ✅ Implemented

- Authentication and registration using Laravel Sanctum
- User management with filtering by category and location
- CRUD for publications
- Like and comment system
- Public access to service categories

### 🔄 Planned

- Public user profiles with posts and basic interaction history
- Report system for inappropriate content moderation
- Service review and rating logic
- Notification endpoints
- Chat/message system integration

<h2 id="technologies"> 🧪 Technologies</h2>

This project was built using the following technologies and tools:

- [Laravel Documentation](https://laravel.com/docs)
- [Laravel Sanctum Documentation](https://laravel.com/docs/sanctum)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Composer Documentation](https://getcomposer.org/doc/)
- [MySQL Documentation](https://dev.mysql.com/doc/)

<h2 id="structure"> 📁 Structure</h2>

Overview of the main project structure:

```txt
📂 app/
 ┣ 📂 Http/
 ┃ ┣ 📂 Controllers/         # API controllers for auth, users, and publications
 ┃ ┃ ┣ 📄 AuthController.php
 ┃ ┃ ┣ 📄 UserController.php
 ┃ ┃ ┗ 📄 PublicationController.php
 ┣ 📂 Models/                # Eloquent models and relationships
 ┃ ┣ 📄 User.php
 ┃ ┣ 📄 Publication.php
 ┃ ┣ 📄 Category.php
 ┃ ┣ 📄 Comment.php
 ┃ ┗ 📄 Like.php

📂 routes/
 ┗ 📄 api.php                # API routes and route groups

📂 database/
 ┣ 📂 migrations/            # Table definitions
 ┗ 📄 seeders/               # Optional: sample data generators

📂 config/
 ┗ 📄 sanctum.php            # Sanctum token configuration

📄 .env                      # Environment variables
📄 composer.json             # Laravel dependencies
```

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
git clone https://github.com/issagomesdev/biko-api.git

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

<h2 id="related-projects">🔗 Related Projects</h2>

🧱 Frontend repository [here](https://github.com/issagomesdev/biko)