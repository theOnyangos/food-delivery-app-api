# ASL API — Food Delivery Application APIs

This repository contains the backend APIs for a food delivery application. It powers authentication, user access control, media uploads, and notifications used by dashboard and client applications.

## What the Application Does

The API provides the core backend capabilities required in a food delivery ecosystem:

- **User onboarding and access**: register, login, logout, password reset, email verification.
- **Secure login flows**: optional two-factor authentication (2FA) with OTP challenge/verification.
- **Role and permission management**: admin APIs to manage users, roles, and permissions.
- **Food-delivery operations support**: secure file/media uploads for assets such as menu photos and content images.
- **Real-time communication**: notification stream (SSE), notification DataTable endpoint, unread counts, mark-as-read actions.
- **Notification preferences**: per-user controls for in-app notifications, preferred notification types, email notifications, and SMS notifications (SMS requires valid phone number).

## Core API Modules

### 1) Authentication (`/api/auth/*`)

- Register and login.
- 2FA login challenge + OTP verification.
- Token verification and logout.
- Forgot/reset password.
- Enable/disable 2FA for authenticated users.

### 2) Roles & Permissions (`/api/admin/*`)

- Manage roles (`admin/roles`).
- DataTables role listing (`admin/roles/datatables`).
- Manage permissions and assign roles to users.

### 3) Uploads (`/api/uploads/*`)

- Upload images/public/private assets.
- Generate secure signed URLs for protected media.
- Delete media by id or path.

### 4) Notifications (`/api/notifications/*`)

- Real-time stream: `GET /api/notifications/stream`.
- DataTables endpoint: `GET /api/notifications/datatable`.
- Unread list/count, mark one/all as read, delete, and test notifications.
- Preferences:
	- `GET /api/notifications/preferences`
	- `PUT/PATCH /api/notifications/preferences`

## Tech Stack

- Laravel 12
- Laravel Sanctum (token auth)
- Spatie Laravel Permission (roles/permissions)
- Yajra DataTables (server-side datatables)
- l5-swagger / swagger-php (OpenAPI docs)

## API Response Format

Most non-DataTables endpoints follow this envelope:

```json
{
	"success": true,
	"message": "...",
	"data": {}
}
```

DataTables endpoints return standard DataTables JSON (`draw`, `recordsTotal`, `recordsFiltered`, `data`).

## Local Setup

### Prerequisites

- PHP 8.2+
- Composer
- MySQL or compatible database
- Node.js + npm (for frontend assets if needed)

### Install

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Update your `.env` database credentials, then run:

```bash
php artisan migrate --seed
php artisan serve
```

## Swagger / API Docs

- Swagger UI: `/api/documentation`
- OpenAPI JSON: `/docs`

Regenerate docs:

```bash
php artisan l5-swagger:generate
```

## Important Paths

- Routes: `routes/api.php`
- Controllers: `app/Http/Controllers/Api`
- OpenAPI annotations: `app/OpenApi`
- Business logic/services: `app/Services`

## Notes

- All sensitive endpoints use Sanctum authentication.
- Role/permission middleware controls admin and privileged operations.
- Notification preferences are user-scoped and work across all user roles.
