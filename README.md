# Project Management API

REST API developed with **Symfony 7.4 LTS** and **PHP 8.3** for managing projects, employees and tasks.

Features:

- JWT authentication
- CRUD operations for Projects, Employees and Tasks
- Task filtering
- Swagger API documentation

---

## Requirements

- PHP >= 8.3
- Composer
- MySQL 8
- Symfony CLI (recommended)

---

# Installation

## 1. Clone repository

```bash
git clone https://github.com/RaZaC-WP/project-management-api.git

cd project-management-api
```

## 2. Install dependencies

```bash
composer install
```

---

## 3. Configure environment

Create `.env.local`:

```dotenv
DATABASE_URL="mysql://root:@127.0.0.1:3306/project_management?serverVersion=8.0.32&charset=utf8mb4"
```

---

# JWT Configuration

The API uses JWT authentication with LexikJWTAuthenticationBundle.

## 1. Generate a passphrase

Generate a secure passphrase:

```bash
openssl rand -hex 32
```

Copy the generated value and configure it in `.env.local`:

```dotenv
JWT_PASSPHRASE=<generated_passphrase>
```

---

## 2. Generate JWT keys

JWT private keys are generated locally and are not included in the repository.

Generate the JWT key pair:

```bash
php bin/console lexik:jwt:generate-keypair
```

This creates:

```
config/jwt/private.pem
config/jwt/public.pem
```

---

## 3. Configure JWT paths

Add the following configuration to `.env.local`:

```dotenv
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_generated_passphrase
```

The `JWT_PASSPHRASE` value must be the same passphrase used when generating the JWT keys.

# Database setup

Create database:

```bash
php bin/console doctrine:database:create
```

Run migrations:

```bash
php bin/console doctrine:migrations:migrate
```

Load test data:

```bash
php bin/console doctrine:fixtures:load
```

Fixtures include:

- Users
- Employees
- Projects
- Tasks

---

# Run application

```bash
symfony server:start
```

Application:

```
http://localhost:8000
```

---

# Authentication

Login endpoint:

```
POST /api/login_check
```

Example:

```json
{
    "username": "admin@test.com",
    "password": "Aabc123"
}
```

Response:

```json
{
    "token": "JWT_TOKEN"
}
```

Use:

```
Authorization: Bearer JWT_TOKEN
```

---

# Application status

Health endpoint:

```
GET /
```

Example response:

```json
{
    "name": "Project Management API",
    "status": "running",
    "documentation": "/api/doc"
}
```

---

# API Documentation

Swagger:

The Swagger documentation contains the complete API specification, including request schemas and responses.

```
http://localhost:8000/api/doc
```

---

# Main endpoints

### Projects

```
GET    /api/projects
POST   /api/projects
GET    /api/projects/{id}
PUT    /api/projects/{id}
DELETE /api/projects/{id}
```

### Employees

```
GET    /api/employees
POST   /api/employees
GET    /api/employees/{id}
PUT    /api/employees/{id}
DELETE /api/employees/{id}
```

### Tasks

```
GET    /api/tasks
POST   /api/tasks
GET    /api/tasks/{id}
PUT    /api/tasks/{id}
DELETE /api/tasks/{id}
```

Task filters:

```
GET /api/tasks?status=DONE
GET /api/tasks?project=1
GET /api/tasks?employee=1
```

# Error handling

The API returns JSON responses for errors.

Example:

```json
{
    "error": "Endpoint not found"
}
```

Common HTTP status codes:

| Code | Description |
|---|---|
| 400 | Validation error |
| 401 | Authentication required |
| 404 | Resource not found |
| 409 | Conflict |
| 500 | Internal server error |

---

# Test user

Created automatically with fixtures:

```
Email:
admin@test.com

Password:
Aabc123
```