# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Tavira is a comprehensive property management platform for residential complexes (condominiums/HOAs) in Colombia. It's built as a multitenant SaaS application using Laravel 12, Vue 3 with Composition API, Inertia.js, shadcn/ui Vue components, and stancl/tenancy for multitenancy.

## Development Commands

### Backend (Laravel/PHP)
- `composer dev` - Start full development environment (Laravel server, queue worker, logs, Vite)
- `composer dev:ssr` - Start development environment with SSR support
- `composer test` - Run PHP tests (PHPUnit/Pest)
- `php artisan serve` - Start Laravel development server only
- `php artisan migrate` - Run database migrations
- `php artisan db:seed` - Seed database with test data
- `php artisan db:seed --class=ChartOfAccountsSeeder` - Seed accounting chart of accounts
- `php artisan queue:work` - Start queue worker
- `php artisan pail` - Monitor logs in real-time
- `./vendor/bin/pint` - Format PHP code (Laravel Pint)
- `php artisan tenants:migrate` - Run tenant migrations
- `php artisan tenants:seed` - Seed tenant databases
- `php artisan tenants:run <command>` - Run artisan command for all tenants

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
- **Multitenancy Architecture**: Full multitenancy using stancl/tenancy package with domain-based tenant identification
- **Central vs Tenant Apps**: Separate central app for tenant management and tenant apps for residential complex operations
- **Authentication**: Laravel Breeze with tenant-scoped user management and central tenant administration
- **Security**: Comprehensive middleware for rate limiting, input sanitization, security headers, and audit logging
- **Key Models**: User, ConjuntoConfig, Apartment, ApartmentType, Resident (tenant-scoped)
- **Central Models**: Tenant, TenantFeature, Domain (central database)
- **Accounting Models**: ChartOfAccounts, AccountingTransaction, AccountingTransactionEntry, Budget, BudgetItem
- **Permissions**: Uses Spatie Laravel Permission for role-based access control (tenant-scoped)
- **Settings**: Spatie Laravel Settings for configurable application settings (tenant-scoped)

### Frontend Structure
- **Framework**: Vue 3 with Composition API and TypeScript
- **Routing**: Inertia.js for SPA-like experience without API endpoints
- **UI Components**: shadcn/ui Vue components with Tailwind CSS
- **State Management**: Composables in `resources/js/composables/`
- **Layout System**: Nested layouts for different app sections (auth, app, settings)

### Key Directories
- `app/Http/Controllers/` - Laravel controllers for each module (tenant-scoped)
- `app/Models/` - Eloquent models with relationships (includes both central and tenant models)
- `app/Http/Middleware/` - Security and application middleware
- `resources/js/pages/` - Vue page components organized by feature
- `resources/js/components/` - Reusable Vue components
- `resources/js/layouts/` - Layout components for different app sections
- `database/migrations/` - Central database schema definitions
- `database/migrations/tenant/` - Tenant database schema definitions
- `routes/tenant.php` - Tenant-specific routes with domain middleware
- `routes/web.php` - Central application routes

### Security Features
- Rate limiting middleware with different tiers (strict, search, default)
- Input sanitization middleware
- Security headers middleware
- Audit logging for user actions
- Two-factor authentication service
- Secure file upload handling
- Session security management

### Multitenancy Architecture (stancl/tenancy)

#### Domain-Based Identification
- **Central Domains**: `tavira.com.co` (production), `localhost`, `127.0.0.1`, `192.168.1.21` (development)
- **Tenant Domains**: Each residential complex gets its own subdomain/domain (e.g., `conjunto-torres.tavira.com.co`)
- **Automatic Resolution**: Middleware automatically identifies and initializes tenant context based on domain

#### Database Architecture
- **Central Database**: Stores tenant information, domains, subscriptions, and global configuration
- **Tenant Databases**: Each tenant gets its own database with prefix `tenant{tenant_id}`
- **Separate Migrations**: Central and tenant migrations are managed separately
- **Dynamic Connection**: Database connections are switched automatically based on tenant context

#### Tenant Features
- **User Impersonation**: Central admins can impersonate tenant users for support
- **Subscription Management**: Built-in subscription tracking with status, plans, and expiration
- **Feature Toggles**: Configurable features per tenant via TenantFeature model
- **File Isolation**: Each tenant has isolated file storage using filesystem tenancy
- **Cache Isolation**: Redis/cache keys are automatically prefixed per tenant

#### Bootstrappers Enabled
- **DatabaseTenancyBootstrapper**: Switches database connections per tenant
- **CacheTenancyBootstrapper**: Isolates cache per tenant with prefixed keys
- **FilesystemTenancyBootstrapper**: Separates file storage per tenant
- **QueueTenancyBootstrapper**: Maintains tenant context in queued jobs
- **ViteBundler**: Handles tenant-specific asset bundling

#### Central vs Tenant Applications
- **Central App** (`routes/web.php`): Tenant management, subscriptions, global administration
- **Tenant App** (`routes/tenant.php`): Residential complex management, residents, apartments, finance
- **Shared Middleware**: Security and common functionality work across both contexts
- **Tenant Context**: All tenant operations automatically scoped to current tenant

#### Tenant Model Extensions
- **Subscription Fields**: `subscription_status`, `subscription_plan`, `subscription_expires_at`
- **Admin Credentials**: `admin_name`, `admin_email`, `admin_user_id` for tenant setup
- **Pending Updates**: JSON field for tracking configuration changes
- **Custom Data**: Extensible `data` JSON field for additional tenant metadata

### Module Organization
The application is organized into logical modules:
- **Conjunto Configuration**: Management of the residential complex settings and configuration
- **Resident Management**: CRUD operations for residents and apartment assignments
- **Apartment Management**: Management of apartments and apartment types
- **Dashboard**: Property management analytics with mock data combined with real data
- **Finance**: Billing, payments, and financial reporting ✅ **IMPLEMENTED**
  - **Accounting System**: Complete double-entry bookkeeping with Colombian chart of accounts
  - **Automatic Integration**: Invoice and payment events generate accounting entries automatically
  - **Budget Management**: Annual budgets with monthly distribution and execution tracking
  - **Chart of Accounts**: 60+ accounts following Decreto 2650 (Colombian GAAP)
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

- **Multitenancy Context**: All tenant operations are automatically scoped to the current tenant
- **Database Separation**: Each tenant has its own database - avoid cross-tenant data access
- **Domain Routing**: Use tenant domains for all tenant-specific operations
- **Migration Separation**: Use `database/migrations/tenant/` for tenant-specific tables
- **Central vs Tenant Models**: Place tenant-scoped models in tenant context, central models in central context
- Use existing middleware patterns for security and rate limiting
- Follow the established component patterns when creating new Vue components (apartments/index.vue, Edit.vue, Create.vue and Show.vue)
- Maintain type safety with TypeScript definitions
- Use the existing composables for common functionality
- Security is a priority - leverage existing security services and patterns
- **Tenant Commands**: Use `php artisan tenants:migrate` and `php artisan tenants:seed` for tenant operations

## Current Data Structure

### Conjunto Configuration (User-Defined)
- **No Default Examples**: Users must create their own conjunto configurations
- **Flexible Structure**: Support for variable apartment counts per floor
- **Advanced Configuration**: Floor-specific settings and penthouse support
- **Total Units**: Dynamically calculated based on configuration

### Advanced Configuration Features
- **Floor-specific Configuration**: Different apartment counts per floor via `configuration_metadata.floor_configuration`
- **Penthouse Support**: Automatic penthouse detection and configuration on top floors
- **Flexible Apartment Generation**: Smart apartment type assignment based on position and floor
- **Configuration Metadata**: Extensible JSON field for additional settings

### Apartment Types (Configurable per conjunto)
- **Tipo A**: 1 bedroom, 1 bathroom, compact units
- **Tipo B**: 2 bedrooms, 2 bathrooms, family units
- **Tipo C**: 3 bedrooms, 2 bathrooms, large family units
- **Penthouse**: 3 bedrooms, 3 bathrooms, luxury units with terraces

### Key Relationships

#### Central Database Relationships
- `Tenant` hasMany `Domain` and `TenantFeature`
- `Domain` belongsTo `Tenant`
- `TenantFeature` belongsTo `Tenant`

#### Tenant Database Relationships (Scoped per Tenant)
- `ConjuntoConfig` hasMany `ApartmentType` and `Apartment`
- `ApartmentType` belongsTo `ConjuntoConfig` and hasMany `Apartment`
- `Apartment` belongsTo `ConjuntoConfig` and `ApartmentType`
- `Resident` belongsTo `Apartment`
- `User` belongsTo tenant context (automatically scoped)
