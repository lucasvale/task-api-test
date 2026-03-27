# Problem

## Senior PHP Backend Developer – Coding Assessment

Thank you for taking the time to complete this assessment.  
The goal is to demonstrate how you approach architecture, testing, and backend engineering best practices.

---

### 📖 Project: Collaborative Task Management API

#### Objective
Build a REST API for managing projects, tasks, comments, and notifications.  
We value **clean architecture, thoughtful design, and code quality** over speed or feature quantity.

---

### ✅ Requirements

#### Core Features
- **Authentication**: User registration & login (JWT or Laravel Sanctum).
- **Projects**: CRUD operations. Each project belongs to a user.
- **Tasks**:
    - CRUD operations.
    - Fields: `title, description, status (todo/in-progress/done), due_date`.
    - Filtering: by status, due date, full-text search.
    - Pagination for listing.
- **Comments**: CRUD operations. Each comment belongs to a task.
- **Notifications**:
    - Triggered when a task is assigned or updated.
    - Delivered asynchronously (e.g., queue).
    - Endpoint for fetching unseen notifications.

#### Non-Functional
- Use a layered architecture (controllers, services, repositories, domain models).
- Apply at least two meaningful design patterns (e.g., Repository, Strategy, Observer).
- Database migrations must be included.
- Cache task listings (e.g., Redis).
- Add rate limiting for sensitive endpoints.
- Standardized error handling and responses.

#### Testing
- Unit tests for core services and repositories.
- Integration tests for API endpoints.
- Minimum **70% test coverage**.

#### DevOps
- `Dockerfile` + `docker-compose.yml` for local setup.
- CI pipeline runs automatically (tests, static analysis, linting, security).
- Compatible with **PHP 8.2+**.

#### Documentation
- Update this `README.md` to include:
    - Setup instructions.
    - Example API requests (curl/Postman).
    - Explanation of your architectural decisions and trade-offs.
    - Which design patterns you applied, and why.

---

### 🎯 Acceptance Criteria

Your submission will be evaluated on:

- **Architecture & Patterns**: Separation of concerns, justified design patterns.
- **Code Quality & Standards**: PSR-12 compliance, maintainability.
- **Feature Completeness**: Requirements implemented.
- **Testing**: Coverage, meaningful cases, edge-case handling.
- **Documentation**: Clear and professional.
- **DevOps**: CI/CD awareness, Docker setup.

---

### 📝 Commit Guidelines

We value not only the final code but also how you structure your work.  
Please use **meaningful, structured commit messages** throughout your development.

- Follow [Conventional Commits](https://www.conventionalcommits.org/) style when possible:
    - `feat:` – for new features
    - `fix:` – for bug fixes
    - `chore:` – for setup, configuration, or maintenance
    - `test:` – for adding or improving tests
    - `docs:` – for documentation changes

- Examples:
    - `chore: initial commit (Laravel project setup)`
    - `feat: add task CRUD endpoints`
    - `fix: correct due date validation logic`


# Solution 
## Collaborative Task Management API

REST API for managing projects, tasks, comments, and notifications built with **Laravel 12**, **PHP 8.4**, and **Laravel Sanctum**.

---

### Table of Contents

- [Tech Stack](#tech-stack)
- [Architecture](#architecture)
- [Design Patterns](#design-patterns)
- [Setup Instructions](#setup-instructions)
- [Running Tests](#running-tests)
- [API Endpoints](#api-endpoints)
- [API Examples (curl)](#api-examples-curl)
- [Features](#features)
- [Trade-offs & Future Improvements](#trade-offs--future-improvements)

---

### Tech Stack

| Component       | Technology              |
|-----------------|-------------------------|
| Framework       | Laravel 12              |
| Language        | PHP 8.4                 |
| Authentication  | Laravel Sanctum 4       |
| Database        | MySQL 8.0               |
| Cache / Queue   | Redis 7                 |
| Mail (dev)      | MailHog                 |
| DTOs            | Spatie Laravel Data     |
| Testing         | PHPUnit 11 + Pest 3    |
| Containerization| Docker + Docker Compose |

---

### Architecture

The project follows a **DDD-inspired layered architecture** with clear separation of concerns:

```
app/
├── Application/          # DTOs (Data Transfer Objects)
│   ├── Auth/DTOs/
│   ├── Comment/DTOs/
│   ├── Notification/DTOs/
│   ├── Project/DTOs/
│   ├── Task/DTOs/
│   └── User/DTOs/
├── Domain/               # Business logic (Services, Entities, Repository Interfaces)
│   ├── Auth/
│   │   ├── Repositories/AuthRepositoryInterface.php
│   │   └── Services/AuthService.php
│   ├── Comment/
│   │   ├── Entities/CommentEntity.php
│   │   ├── Repositories/CommentRepositoryInterface.php
│   │   └── Services/CommentService.php
│   ├── Notification/
│   │   ├── Entities/NotificationEntity.php
│   │   ├── Repositories/NotificationRepositoryInterface.php
│   │   └── Services/NotificationService.php
│   ├── Project/
│   │   ├── Entities/ProjectEntity.php
│   │   ├── Repositories/ProjectRepositoryInterface.php
│   │   └── Services/ProjectService.php
│   ├── Task/
│   │   ├── Entities/TaskEntity.php
│   │   ├── Repositories/TaskRepositoryInterface.php
│   │   └── Services/TaskService.php
│   └── User/
│       ├── Entities/UserEntity.php
│       ├── Repositories/UserRepositoryInterface.php
│       └── Services/UserService.php
├── Infrastructure/       # Repository implementations (Eloquent)
│   └── Database/Repositories/
├── Http/Controllers/     # API Controllers
├── Models/               # Eloquent Models
├── Notifications/        # Queued Notifications (TaskCreated, TaskUpdated)
├── Enum/                 # HttpStatus enum
└── Traits/               # ApiResponser trait (JSend format)
```

**Layer responsibilities:**

| Layer            | Responsibility                                      |
|------------------|-----------------------------------------------------|
| **HTTP**         | Request handling, validation delegation, responses   |
| **Application**  | DTOs for request/response data shaping               |
| **Domain**       | Business rules, entities, service orchestration      |
| **Infrastructure** | Database access via Eloquent, external integrations |

---

### Design Patterns

#### 1. Repository Pattern
All database access is abstracted behind interfaces (`UserRepositoryInterface`, `TaskRepositoryInterface`, etc.) with Eloquent implementations in the Infrastructure layer. This decouples business logic from the ORM, making services testable with mocks.

#### 2. Observer Pattern (Notifications)
When a task is created or updated with an `assigned_to` user, the `TaskService` dispatches queued notifications (`TaskCreated`, `TaskUpdated`) via Laravel's notification system. These are processed asynchronously through Redis queues and deliver both email and database notifications.

#### 3. DTO Pattern (Data Transfer Objects)
All request data flows through Spatie Laravel Data DTOs (`CreateTaskRequestDto`, `TaskFiltersDto`, etc.), providing automatic validation, type safety, and a clean contract between layers.

#### 4. Entity Pattern
Domain entities (`TaskEntity`, `ProjectEntity`, etc.) encapsulate business state with factory methods (`fromCreateDto()`, `fromModel()`) and mutation methods (`applyUpdate()`), keeping business logic out of Eloquent models.

---

### Setup Instructions

#### Prerequisites

- Docker & Docker Compose

#### 1. Clone the repository

```bash
git clone <repository-url>
cd task-api-test
```

#### 2. Start the containers

```bash
docker-compose up -d --build
```

This starts four services:

| Service    | Port  | Description            |
|------------|-------|------------------------|
| **api**    | 8000  | Laravel application    |
| **mysql**  | 3380  | MySQL 8.0 database     |
| **redis**  | 6379  | Redis 7 (cache/queue)  |
| **mailhog**| 8025  | MailHog web UI (email) |

#### 3. Verify the setup

```bash
# Check all containers are running
docker-compose ps

# Check the API is responding
curl http://localhost:8000/api/auth/login -X POST -H "Content-Type: application/json" -d '{"email":"test@test.com","password":"123"}'
```

The startup script automatically:
- Generates the application key
- Runs database migrations
- Starts the web server, queue worker, and scheduler via Supervisor

#### 4. Access MailHog

Open [http://localhost:8025](http://localhost:8025) to view emails sent by the application (task assignment notifications).

---

### Running Tests

Tests run inside the Docker container against a real MySQL database:

```bash
# Run all tests
docker exec task_management php artisan test

# Run only unit tests
docker exec task_management php artisan test --testsuite=Unit

# Run only feature/integration tests
docker exec task_management php artisan test --testsuite=Feature

# Run with coverage report
docker exec task_management php artisan test --coverage
```

#### Test Summary

| Suite     | Description                                  |
|-----------|----------------------------------------------|
| **Unit**  | Service tests (mocked repos) + Repository tests (RefreshDatabase) |
| **Feature** | Full API integration tests for all endpoints |

**161 tests, 401 assertions** covering:
- Auth (login, logout, me)
- User CRUD + duplicate email validation
- Project CRUD + ownership checks
- Task CRUD + filtering (status, due date, full-text search)
- Comment CRUD + author-only edit/delete
- Notification listing + mark as read

---

### API Endpoints

#### Authentication

| Method | Endpoint           | Auth | Rate Limit       | Description          |
|--------|--------------------|------|------------------|----------------------|
| POST   | `/api/auth/login`  | No   | 5/min per IP     | Login and get token  |
| POST   | `/api/auth/logout` | Yes  | 60/min per user  | Revoke current token |
| GET    | `/api/auth/me`     | Yes  | 60/min per user  | Get authenticated user |

#### Users

| Method | Endpoint         | Auth | Rate Limit       | Description        |
|--------|------------------|------|------------------|--------------------|
| POST   | `/api/users`     | No   | 3/min per IP     | Register new user  |
| GET    | `/api/users`     | Yes  | 60/min per user  | List all users     |
| GET    | `/api/users/{id}` | Yes | 60/min per user  | Get user by ID     |
| PUT    | `/api/users/{id}` | Yes | 60/min per user  | Update user        |
| DELETE | `/api/users/{id}` | Yes | 60/min per user  | Delete user        |

#### Projects

| Method | Endpoint              | Auth | Description              |
|--------|-----------------------|------|--------------------------|
| GET    | `/api/projects`       | Yes  | List own projects        |
| GET    | `/api/projects/{id}`  | Yes  | Get project (own only)   |
| POST   | `/api/projects`       | Yes  | Create project           |
| PUT    | `/api/projects/{id}`  | Yes  | Update project (own only)|
| DELETE | `/api/projects/{id}`  | Yes  | Delete project (own only)|

#### Tasks

| Method | Endpoint                                | Auth | Description                    |
|--------|-----------------------------------------|------|--------------------------------|
| GET    | `/api/projects/{projectId}/tasks`       | Yes  | List tasks (with filters)      |
| GET    | `/api/projects/{projectId}/tasks/{id}`  | Yes  | Get task                       |
| POST   | `/api/projects/{projectId}/tasks`       | Yes  | Create task                    |
| PUT    | `/api/projects/{projectId}/tasks/{id}`  | Yes  | Update task                    |
| DELETE | `/api/projects/{projectId}/tasks/{id}`  | Yes  | Delete task                    |

**Task Filters** (query params on GET list):
- `status` — Filter by status (`todo`, `in-progress`, `done`)
- `due_date_from` — Filter tasks with due date >= value
- `due_date_to` — Filter tasks with due date <= value
- `search` — Full-text search on title and description

#### Comments

| Method | Endpoint                                | Auth | Description                      |
|--------|-----------------------------------------|------|----------------------------------|
| GET    | `/api/tasks/{taskId}/comments`          | Yes  | List task comments               |
| GET    | `/api/tasks/{taskId}/comments/{id}`     | Yes  | Get comment                      |
| POST   | `/api/tasks/{taskId}/comments`          | Yes  | Create comment                   |
| PUT    | `/api/tasks/{taskId}/comments/{id}`     | Yes  | Update comment (author only)     |
| DELETE | `/api/tasks/{taskId}/comments/{id}`     | Yes  | Delete comment (author only)     |

### Notifications

| Method | Endpoint                         | Auth | Description               |
|--------|----------------------------------|------|---------------------------|
| GET    | `/api/notifications`             | Yes  | List notifications        |
| PATCH  | `/api/notifications/{id}/read`   | Yes  | Mark as read              |
| PATCH  | `/api/notifications/read-all`    | Yes  | Mark all as read          |

**Notification Filters** (query params on GET list):
- `type` — Filter by notification type (`task-created`, `task-updated`)
- `read` — Filter by read status (`true` / `false`)
- `from` — Filter notifications from date
- `to` — Filter notifications until date

---

### API Examples (curl)

#### Register a user

```bash
curl -X POST http://localhost:8000/api/users \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "secret123"
  }'
```

#### Login

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "secret123"
  }'
```

Response:
```json
{
  "status": "success",
  "data": {
    "token": "1|abc123...",
    "user": { "id": 1, "name": "John Doe", "email": "john@example.com" }
  }
}
```

#### Create a project

```bash
curl -X POST http://localhost:8000/api/projects \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "name": "My Project",
    "description": "Project description"
  }'
```

#### Create a task (with assignee notification)

```bash
curl -X POST http://localhost:8000/api/projects/1/tasks \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "title": "Implement feature X",
    "description": "Detailed description of the task",
    "status": "todo",
    "assigned_to": 2,
    "due_date": "2026-04-01"
  }'
```

> When `assigned_to` is set, the assigned user receives an email notification (viewable in MailHog at http://localhost:8025) and a database notification.

#### List tasks with filters

```bash
# Filter by status
curl -X GET "http://localhost:8000/api/projects/1/tasks?status=todo" \
  -H "Authorization: Bearer {token}"

# Filter by due date range
curl -X GET "http://localhost:8000/api/projects/1/tasks?due_date_from=2026-03-01&due_date_to=2026-04-01" \
  -H "Authorization: Bearer {token}"

# Full-text search
curl -X GET "http://localhost:8000/api/projects/1/tasks?search=implement" \
  -H "Authorization: Bearer {token}"
```

#### Add a comment to a task

```bash
curl -X POST http://localhost:8000/api/tasks/1/comments \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{"body": "This looks good, proceeding with implementation."}'
```

#### List notifications

```bash
## All notifications
curl -X GET http://localhost:8000/api/notifications \
  -H "Authorization: Bearer {token}"

## Only unread
curl -X GET "http://localhost:8000/api/notifications?read=false" \
  -H "Authorization: Bearer {token}"
```

#### Mark notification as read

```bash
curl -X PATCH http://localhost:8000/api/notifications/{id}/read \
  -H "Authorization: Bearer {token}"
```

#### Mark all notifications as read

```bash
curl -X PATCH http://localhost:8000/api/notifications/read-all \
  -H "Authorization: Bearer {token}"
```

---

### Features

#### Implemented

- **Authentication** — Registration and login via Laravel Sanctum with token-based auth
- **Projects** — Full CRUD with ownership enforcement (users can only access their own projects)
- **Tasks** — Full CRUD with filtering (status, due date range, full-text search via MySQL FULLTEXT index)
- **Comments** — Full CRUD with author-only edit/delete enforcement
- **Notifications** — Async email + database notifications on task create/update; listing with filters; mark as read
- **Caching** — Task listings cached in Redis (5 min TTL) with automatic invalidation on create/update/delete
- **Rate Limiting** — Login (5/min), registration (3/min), authenticated endpoints (60/min)
- **Standardized Responses** — JSend format (`status`, `data`, `message`) across all endpoints
- **Email** — Markdown email templates for task created/updated notifications via MailHog
- **Queue** — Redis-backed async queue for notification dispatch with retry logic
- **Validation** — DTO-level validation via Spatie Laravel Data (unique email, required fields)
- **Docker** — Full containerized setup with MySQL, Redis, MailHog, Supervisor (web server + queue worker + scheduler)

#### Testing

- **Unit Tests** — Services tested with mocked repositories; repositories tested with RefreshDatabase against real MySQL
- **Integration Tests** — Full API endpoint tests covering auth, CRUD, ownership, access control, and edge cases
- **161 tests, 401 assertions**

---

### Trade-offs & Future Improvements

#### Trade-offs Made

- **`artisan serve` vs Nginx/Apache** — Used PHP's built-in server via Supervisor for simplicity. In production, use Nginx + PHP-FPM.
- **No pagination on task listings** — Task listings are cached and returned as full arrays. Pagination was not yet implemented but would be a straightforward addition using Laravel's paginator.
- **Entities separate from Models** — Adds a mapping layer between Eloquent and domain logic. This is intentional to keep business rules framework-agnostic, but adds verbosity.

#### With More Time

- **Pagination** — Add cursor-based pagination for task and comment listings
- **CI/CD Pipeline** — GitHub Actions workflow for automated tests, PHPStan static analysis, PHP CS Fixer linting, and security audits
- **API Versioning** — Prefix routes with `/api/v1/` for future compatibility
- **Authorization Policies** — Replace manual ownership checks with Laravel Policies for cleaner authorization
- **API Documentation** — Generate OpenAPI/Swagger docs from route definitions
- **Soft Deletes** — Add soft delete support for projects and tasks to allow recovery
- **Event Sourcing** — Replace direct notification dispatch with domain events for better decoupling
- **Database Indexes** — Add composite indexes for frequently filtered queries
- **Health Check Endpoint** — Expand `/up` to verify MySQL and Redis connectivity
#
