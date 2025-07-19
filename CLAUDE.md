# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Habitta is a comprehensive property management platform for residential complexes (condominiums/HOAs) in Colombia. It's built as a multitenant SaaS application using Laravel 12, Vue 3 with Composition API, Inertia.js, and shadcn/ui Vue components.

## Development Commands

### Backend (Laravel/PHP)
- `composer dev` - Start full development environment (Laravel server, queue worker, logs, Vite)
- `composer dev:ssr` - Start development environment with SSR support
- `composer test` - Run PHP tests (PHPUnit/Pest)
- `php artisan serve` - Start Laravel development server only
- `php artisan migrate` - Run database migrations
- `php artisan db:seed` - Seed database with test data
- `php artisan queue:work` - Start queue worker
- `php artisan pail` - Monitor logs in real-time
- `./vendor/bin/pint` - Format PHP code (Laravel Pint)

### Frontend (Vue/TypeScript)
- `npm run dev` - Start Vite development server
- `npm run build` - Build for production
- `npm run build:ssr` - Build with SSR support
- `npm run lint` - Lint JavaScript/TypeScript with ESLint
- `npm run format` - Format code with Prettier
- `npm run format:check` - Check formatting
- `vue-tsc --noEmit` - TypeScript type checking

### Testing
- `npm run test:e2e` - Run Playwright E2E tests
- `npm run test:e2e:ui` - Run E2E tests with UI
- `npm run test:e2e:headed` - Run E2E tests in headed mode
- `npm run test:e2e:debug` - Debug E2E tests

## Architecture Overview

### Backend Structure
- **Multitenant Architecture**: Each residential complex (conjunto) is isolated via `conjunto_config_id`
- **Authentication**: Laravel Breeze with role-based access (company/individual users)
- **Security**: Comprehensive middleware for rate limiting, input sanitization, security headers, and audit logging
- **Key Models**: User, ConjuntoConfig, Apartment, ApartmentType, Resident
- **Permissions**: Uses Spatie Laravel Permission for role-based access control
- **Settings**: Spatie Laravel Settings for configurable application settings

### Frontend Structure
- **Framework**: Vue 3 with Composition API and TypeScript
- **Routing**: Inertia.js for SPA-like experience without API endpoints
- **UI Components**: shadcn/ui Vue components with Tailwind CSS
- **State Management**: Composables in `resources/js/composables/`
- **Layout System**: Nested layouts for different app sections (auth, app, settings)

### Key Directories
- `app/Http/Controllers/` - Laravel controllers for each module
- `app/Models/` - Eloquent models with relationships
- `app/Http/Middleware/` - Security and application middleware
- `resources/js/pages/` - Vue page components organized by feature
- `resources/js/components/` - Reusable Vue components
- `resources/js/layouts/` - Layout components for different app sections
- `database/migrations/` - Database schema definitions

### Security Features
- Rate limiting middleware with different tiers (strict, search, default)
- Input sanitization middleware
- Security headers middleware
- Audit logging for user actions
- Two-factor authentication service
- Secure file upload handling
- Session security management

### Module Organization
The application is organized into logical modules:
- **Resident Management**: CRUD operations for residents and apartment assignments
- **Conjunto Configuration**: Management of residential complex settings and apartment generation
- **Finance**: Billing, payments, and financial reporting (planned)
- **Communication**: Correspondence, announcements, and notifications (planned)
- **Security**: Access control and visitor management (planned)
- **Documents**: Official documents and meeting minutes (planned)

### Development Patterns
- Controllers follow RESTful resource patterns
- Vue components use Composition API with `<script setup>`
- TypeScript for type safety across the frontend
- Tailwind CSS for styling with custom component variants
- Form validation using Laravel Form Requests
- Database relationships properly defined with Eloquent

### Configuration Files
- `vite.config.ts` - Vite configuration with Laravel plugin
- `tsconfig.json` - TypeScript configuration with path aliases
- `tailwind.config.js` - Tailwind CSS configuration
- `components.json` - shadcn/ui component configuration
- `config/` directory contains Laravel configuration files

### Testing Strategy
- **Backend**: PHPUnit/Pest tests in `tests/Feature/` and `tests/Unit/`
- **Frontend**: Playwright E2E tests in `tests/e2e/`
- Both unit and integration testing approaches are used

## Important Notes

- Always respect the multitenant architecture - data should be isolated by `conjunto_config_id`
- Use existing middleware patterns for security and rate limiting
- Follow the established component patterns when creating new Vue components
- Maintain type safety with TypeScript definitions
- Use the existing composables for common functionality
- Security is a priority - leverage existing security services and patterns