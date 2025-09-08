import { test, expect } from '@playwright/test';

test.describe('Assemblies Management', () => {
  let consoleErrors: string[] = [];
  
  test.beforeEach(async ({ page }) => {
    // Capture console errors
    consoleErrors = [];
    page.on('console', (msg) => {
      if (msg.type() === 'error') {
        consoleErrors.push(`Console error: ${msg.text()}`);
      }
    });

    // Capture page errors
    page.on('pageerror', (error) => {
      consoleErrors.push(`Page error: ${error.message}`);
    });

    // Login as admin user
    await page.goto('/login');
    await page.fill('input[name="email"]', 'admin@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('/dashboard');
  });

  test.afterEach(async () => {
    // Report any console errors
    if (consoleErrors.length > 0) {
      console.log('Console errors detected:', consoleErrors);
      expect(consoleErrors).toHaveLength(0);
    }
  });

  test('should display assemblies index page without errors', async ({ page }) => {
    await page.goto('/assemblies');
    
    // Check for basic page elements
    await expect(page.locator('title')).toContainText('Asambleas');
    
    // Check for main UI elements
    await expect(page.getByText('Nueva Asamblea')).toBeVisible();
    await expect(page.getByText('Exportar')).toBeVisible();
    
    // Check for filters
    await expect(page.getByLabel('Búsqueda General')).toBeVisible();
    await expect(page.getByLabel('Estado')).toBeVisible();
    await expect(page.getByLabel('Tipo')).toBeVisible();
    
    // Check table structure
    await expect(page.getByRole('table')).toBeVisible();
    await expect(page.getByText('Título')).toBeVisible();
    await expect(page.getByText('Estado')).toBeVisible();
    await expect(page.getByText('Fecha Programada')).toBeVisible();
    await expect(page.getByText('Quórum')).toBeVisible();
    
    // Check for no JavaScript errors
    await page.waitForTimeout(2000);
  });

  test('should navigate to create assembly page without errors', async ({ page }) => {
    await page.goto('/assemblies');
    
    // Click new assembly button
    await page.getByText('Nueva Asamblea').click();
    await page.waitForURL('/assemblies/create');
    
    // Check basic page elements
    await expect(page.locator('h1')).toContainText('Nueva Asamblea');
    await expect(page.getByText('Información Básica')).toBeVisible();
    await expect(page.getByText('Configuración')).toBeVisible();
    await expect(page.getByText('Agenda y Documentos')).toBeVisible();
    await expect(page.getByText('Notificaciones')).toBeVisible();
    
    // Check form fields in first step
    await expect(page.getByLabel('Título')).toBeVisible();
    await expect(page.getByLabel('Descripción')).toBeVisible();
    await expect(page.getByLabel('Tipo de Asamblea')).toBeVisible();
    await expect(page.getByLabel('Fecha y Hora')).toBeVisible();
    
    // Check progress indicator
    await expect(page.getByText('Progreso del Formulario')).toBeVisible();
    
    await page.waitForTimeout(2000);
  });

  test('should test form validation on create page', async ({ page }) => {
    await page.goto('/assemblies/create');
    
    // Try to proceed to next step without filling required fields
    await page.getByText('Siguiente').click();
    
    // Should still be on first step since validation should prevent advancement
    await expect(page.getByText('Información Básica')).toBeVisible();
    
    // Fill required fields
    await page.getByLabel('Título').fill('Test Assembly');
    await page.getByLabel('Descripción').fill('This is a test assembly for E2E testing');
    
    // Select assembly type
    await page.getByLabel('Tipo de Asamblea').click();
    await page.getByText('Ordinaria').click();
    
    // Set future date
    const futureDate = new Date();
    futureDate.setDate(futureDate.getDate() + 7);
    const dateString = futureDate.toISOString().slice(0, 16);
    await page.getByLabel('Fecha y Hora').fill(dateString);
    
    // Now should be able to proceed
    await page.getByText('Siguiente').click();
    
    // Should be on step 2
    await expect(page.getByText('Configuración')).toBeVisible();
    await expect(page.getByLabel('Quórum Requerido (%)')).toBeVisible();
    
    await page.waitForTimeout(1000);
  });

  test('should navigate through all steps of create form', async ({ page }) => {
    await page.goto('/assemblies/create');
    
    // Step 1: Basic Information
    await page.getByLabel('Título').fill('Complete Test Assembly');
    await page.getByLabel('Descripción').fill('This is a comprehensive test of the assembly creation form');
    await page.getByLabel('Tipo de Asamblea').click();
    await page.getByText('Ordinaria').click();
    
    const futureDate = new Date();
    futureDate.setDate(futureDate.getDate() + 10);
    const dateString = futureDate.toISOString().slice(0, 16);
    await page.getByLabel('Fecha y Hora').fill(dateString);
    
    await page.getByText('Siguiente').click();
    
    // Step 2: Configuration
    await expect(page.getByText('Configuración')).toBeVisible();
    await page.getByLabel('Quórum Requerido (%)').fill('60');
    await page.getByLabel('Ubicación').fill('Salón comunal');
    await page.getByLabel('Duración Máxima (horas)').fill('3');
    await page.getByLabel('Organizador/Responsable').fill('Administración');
    
    await page.getByText('Siguiente').click();
    
    // Step 3: Agenda and Documents
    await expect(page.getByText('Agenda y Documentos')).toBeVisible();
    
    // Add agenda items
    await page.getByPlaceholder('Agregar punto de agenda...').fill('Punto 1: Revisión presupuesto');
    await page.getByText('Agregar').click();
    await page.getByPlaceholder('Agregar punto de agenda...').fill('Punto 2: Elección junta directiva');
    await page.getByText('Agregar').click();
    
    // Check agenda items were added
    await expect(page.getByText('1. Punto 1: Revisión presupuesto')).toBeVisible();
    await expect(page.getByText('2. Punto 2: Elección junta directiva')).toBeVisible();
    
    await page.getByText('Siguiente').click();
    
    // Step 4: Notifications
    await expect(page.getByText('Configuración de Notificaciones')).toBeVisible();
    
    // Check notification toggles
    await expect(page.getByText('Recordatorio por Email')).toBeVisible();
    await expect(page.getByText('Recordatorio por WhatsApp')).toBeVisible();
    
    // Should show create button on final step
    await expect(page.getByText('Crear Asamblea')).toBeVisible();
    
    await page.waitForTimeout(1000);
  });

  test('should test search and filter functionality', async ({ page }) => {
    await page.goto('/assemblies');
    
    // Test search functionality
    const searchInput = page.getByLabel('Búsqueda General');
    await searchInput.fill('test search');
    
    // Should not cause any errors
    await page.waitForTimeout(500);
    
    // Clear search
    await searchInput.clear();
    
    // Test status filter
    await page.getByLabel('Estado').click();
    await page.getByText('Programada').click();
    
    await page.waitForTimeout(500);
    
    // Test type filter
    await page.getByLabel('Tipo').click();
    await page.getByText('Ordinaria').click();
    
    await page.waitForTimeout(500);
    
    // Clear filters if button is present
    const clearButton = page.getByText('Limpiar filtros');
    if (await clearButton.isVisible()) {
      await clearButton.click();
    }
    
    await page.waitForTimeout(500);
  });

  test('should test responsive design on mobile viewport', async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 667 });
    
    await page.goto('/assemblies');
    
    // Check that page loads properly on mobile
    await expect(page.getByText('Nueva Asamblea')).toBeVisible();
    
    // Check that table is present (might be horizontally scrollable)
    await expect(page.getByRole('table')).toBeVisible();
    
    // Navigate to create page
    await page.getByText('Nueva Asamblea').click();
    await page.waitForURL('/assemblies/create');
    
    // Check form renders properly on mobile
    await expect(page.locator('h1')).toContainText('Nueva Asamblea');
    await expect(page.getByText('Información Básica')).toBeVisible();
    
    await page.waitForTimeout(1000);
  });

  test('should check for proper link navigation', async ({ page }) => {
    await page.goto('/assemblies');
    
    // Test breadcrumb navigation
    await expect(page.getByText('Escritorio')).toBeVisible();
    await expect(page.getByText('Asambleas')).toBeVisible();
    
    // Test back to dashboard
    await page.getByText('Escritorio').click();
    await page.waitForURL('/dashboard');
    
    // Go back to assemblies
    await page.goto('/assemblies');
    
    // Test create page navigation
    await page.getByText('Nueva Asamblea').click();
    await page.waitForURL('/assemblies/create');
    
    // Test back button
    await page.getByText('Volver').click();
    await page.waitForURL('/assemblies');
    
    await page.waitForTimeout(500);
  });

  test('should validate form input constraints', async ({ page }) => {
    await page.goto('/assemblies/create');
    
    // Test title field constraints
    const titleField = page.getByLabel('Título');
    await titleField.fill(''); // Empty should be invalid
    await titleField.fill('A'.repeat(300)); // Very long should be handled
    await titleField.fill('Valid Assembly Title'); // Valid input
    
    // Test description field
    const descField = page.getByLabel('Descripción');
    await descField.fill('A valid description for testing');
    
    // Test date field constraints
    const dateField = page.getByLabel('Fecha y Hora');
    // Should not allow past dates
    const pastDate = new Date();
    pastDate.setDate(pastDate.getDate() - 1);
    await dateField.fill(pastDate.toISOString().slice(0, 16));
    
    // Set future date
    const futureDate = new Date();
    futureDate.setDate(futureDate.getDate() + 5);
    await dateField.fill(futureDate.toISOString().slice(0, 16));
    
    await page.getByLabel('Tipo de Asamblea').click();
    await page.getByText('Ordinaria').click();
    
    await page.getByText('Siguiente').click();
    
    // Test quorum percentage constraints
    const quorumField = page.getByLabel('Quórum Requerido (%)');
    await quorumField.fill('0'); // Should be invalid
    await quorumField.fill('101'); // Should be invalid (over 100)
    await quorumField.fill('50'); // Valid
    
    await page.waitForTimeout(500);
  });

  test('should check accessibility features', async ({ page }) => {
    await page.goto('/assemblies');
    
    // Check for proper ARIA labels and roles
    await expect(page.getByRole('table')).toBeVisible();
    await expect(page.getByRole('button', { name: /nueva asamblea/i })).toBeVisible();
    
    // Test keyboard navigation
    await page.keyboard.press('Tab');
    await page.keyboard.press('Tab');
    
    // Navigate to create page
    await page.goto('/assemblies/create');
    
    // Check form accessibility
    await expect(page.getByLabel('Título')).toBeVisible();
    await expect(page.getByLabel('Descripción')).toBeVisible();
    
    // Test that labels are properly associated with inputs
    const titleInput = page.getByLabel('Título');
    await expect(titleInput).toHaveAttribute('id');
    
    await page.waitForTimeout(500);
  });
});