import { test, expect } from '@playwright/test';

test.describe('Tenant Management Feature', () => {
    test.beforeEach(async ({ page }) => {
        // Navigate to login page
        await page.goto('http://127.0.0.1:8000/login');
        
        // Login as superadmin user
        await page.getByRole('textbox', { name: 'Correo electrónico' }).fill('mauricio.montoyav@gmail.com');
        await page.getByRole('textbox', { name: 'Contraseña' }).fill('Password123!');
        await page.getByRole('button', { name: 'Iniciar Sesión' }).click();
        
        // Wait for dashboard to load
        await page.waitForURL('**/dashboard');
    });

    test('should display central dashboard with correct tenant data', async ({ page }) => {
        // Check dashboard is loaded
        await expect(page.locator('h1')).toContainText('Panel Central Tavira');
        
        // Check tenant stats
        await expect(page.getByText('Total Tenants')).toBeVisible();
        await expect(page.getByText('Tenants Activos')).toBeVisible();
        await expect(page.getByText('Tenants Pendientes')).toBeVisible();
        
        // Check recent tenants section shows proper names (not "Sin nombre")
        await expect(page.getByText('Residencial El Parque')).toBeVisible();
        await expect(page.getByText('Conjunto Villa Hermosa')).toBeVisible();
        await expect(page.getByText('Torres de Villa Campestre')).toBeVisible();
        await expect(page.getByText('Conjunto Buenavista')).toBeVisible();
        
        // Check status badges are working
        await expect(page.getByText('Activo')).toBeVisible();
        await expect(page.getByText('Pendiente')).toBeVisible();
    });

    test('should show proper navigation items in central dashboard', async ({ page }) => {
        // Check main navigation items
        await expect(page.getByRole('link', { name: 'Dashboard' })).toBeVisible();
        
        // Expand tenant management dropdown
        await page.getByRole('button', { name: 'Gestión de Tenants' }).click();
        await expect(page.getByText('Ver Tenants')).toBeVisible();
        await expect(page.getByText('Crear Tenant')).toBeVisible();
        
        // Check footer navigation
        await expect(page.getByRole('link', { name: 'Soporte' })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Documentación' })).toBeVisible();
    });

    test('should navigate to tenant management page and display data correctly', async ({ page }) => {
        // Navigate to tenants page
        await page.goto('http://127.0.0.1:8000/tenants');
        
        // Check page loaded correctly
        await expect(page.locator('h1')).toContainText('Gestión de Tenants');
        await expect(page.getByText('Administra todos los conjuntos residenciales en la plataforma')).toBeVisible();
        
        // Check table headers
        await expect(page.getByRole('columnheader', { name: 'Conjunto' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Email' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Dominios' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Estado' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Creado' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Acciones' })).toBeVisible();
    });

    test('should display tenant data with proper names and emails', async ({ page }) => {
        await page.goto('http://127.0.0.1:8000/tenants');
        
        // Check that tenant names are displayed correctly (not "Sin nombre")
        await expect(page.getByText('Residencial El Parque')).toBeVisible();
        await expect(page.getByText('Conjunto Villa Hermosa')).toBeVisible();
        await expect(page.getByText('Torres de Villa Campestre')).toBeVisible();
        await expect(page.getByText('Conjunto Buenavista')).toBeVisible();
        
        // Check that emails are displayed correctly (not "Sin email")
        await expect(page.getByText('admin@elparque.com')).toBeVisible();
        await expect(page.getByText('admin@villahermosa.com')).toBeVisible();
        await expect(page.getByText('contact@torresdevilla.com')).toBeVisible();
        await expect(page.getByText('info@buenavista.com')).toBeVisible();
        
        // Check that domains are displayed
        await expect(page.getByText('epa.localhost')).toBeVisible();
        await expect(page.getByText('foo.localhost')).toBeVisible();
        await expect(page.getByText('bar.localhost')).toBeVisible();
        await expect(page.getByText('torresdevillacampestre.localhost')).toBeVisible();
        
        // Check status badges
        await expect(page.locator('tbody')).toContainText('Activo');
        await expect(page.locator('tbody')).toContainText('Pendiente');
    });

    test('should show action buttons for active tenants', async ({ page }) => {
        await page.goto('http://127.0.0.1:8000/tenants');
        
        // Check that active tenants have "Ingresar" button
        const activeRows = page.locator('tr:has-text("Activo")');
        await expect(activeRows.first().getByText('Ingresar')).toBeVisible();
        
        // Check that all tenants have dropdown menu button
        const dropdownButtons = page.locator('tbody button[role="button"]');
        await expect(dropdownButtons).toHaveCount(8); // 4 tenants × 2 buttons each (Ingresar + dropdown)
    });

    test('should have functional search and filter controls', async ({ page }) => {
        await page.goto('http://127.0.0.1:8000/tenants');
        
        // Check search input exists
        const searchInput = page.getByPlaceholder('Buscar por nombre, email o ID...');
        await expect(searchInput).toBeVisible();
        
        // Check status filter exists
        const statusFilter = page.getByRole('combobox');
        await expect(statusFilter).toBeVisible();
        
        // Check clear button exists
        const clearButton = page.getByRole('button', { name: 'Limpiar' });
        await expect(clearButton).toBeVisible();
        
        // Check create tenant button exists
        const createButton = page.getByRole('button', { name: 'Crear Tenant' });
        await expect(createButton).toBeVisible();
    });

    test('should not have console errors on main pages', async ({ page }) => {
        const consoleErrors: string[] = [];
        
        // Listen for console errors
        page.on('console', msg => {
            if (msg.type() === 'error') {
                consoleErrors.push(msg.text());
            }
        });
        
        // Visit dashboard
        await page.goto('http://127.0.0.1:8000/dashboard');
        await page.waitForTimeout(2000);
        
        // Visit tenants page
        await page.goto('http://127.0.0.1:8000/tenants');
        await page.waitForTimeout(2000);
        
        // Filter out expected errors (notification 404 is acceptable for central dashboard)
        const criticalErrors = consoleErrors.filter(error => 
            !error.includes('notifications/counts') && 
            !error.includes('SelectItem') &&
            !error.includes('[intlify]')
        );
        
        // Should not have critical console errors
        expect(criticalErrors).toHaveLength(0);
    });
});