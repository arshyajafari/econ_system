# ECON System

Backend API for the ECON ordering, distribution, inventory, accounting, delivery, doctor-visit and sample-management system.

## Stack

- PHP 8.2+
- Laravel 12
- MySQL
- Laravel Sanctum
- Spatie Permission
- Spatie Activity Log

The frontend is intentionally a separate React/PWA application and is not part of this Laravel repository.

## Backend responsibilities

- Authentication and authorization
- Customers, customer ledger and employees
- Doctors, visits and samples
- Products, brands and product categories
- Inventory batches, adjustments and movements
- Orders and order returns
- Invoices and payments
- Deliveries
- Dashboard/report endpoints

## API

All authenticated application endpoints are versioned under `/api/v1`.

Authentication:

- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`

Domain-specific route files live under `routes/api/` and are registered from `routes/api.php`.

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure MySQL in `.env`, then:

```bash
php artisan migrate:fresh --seed
php artisan optimize:clear
```

> `migrate:fresh --seed` is intended for development/reset environments. Never run it against production data.

## API verification

The API should be verified with Postman after the backend route/controller/action layer is finalized.

Recommended verification order:

1. Login and obtain the Sanctum token.
2. Verify CRUD endpoints.
3. Verify workflow/state endpoints such as submit, confirm, complete, cancel and restore.
4. Verify customer ledger calculations.
5. Verify authorization for each role.
6. Verify validation and error responses.
7. Verify pagination, filtering and sorting.
8. Verify inventory/order/return consistency.
9. Verify invoice/payment consistency.

## Frontend architecture

The React/PWA frontend is intentionally maintained as a separate repository. It consumes the Laravel API and is not coupled to Laravel's `resources/js` directory.

## Current development policy

Tests and factories are intentionally postponed for the current implementation phase. They should be added after the API and database contracts are stable and have been verified through Postman.
