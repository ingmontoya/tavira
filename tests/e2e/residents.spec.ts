import { test, expect } from '@playwright/test';

test.describe('Residents Management', () => {
  test.beforeEach(async ({ page }) => {
    // Login as admin user
    await page.goto('/login');
    await page.fill('input[name="email"]', 'admin@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('/dashboard');
  });

  test('should display residents list', async ({ page }) => {
    await page.goto('/residents');
    
    await expect(page.locator('h2')).toContainText('Administración de Residentes');
    await expect(page.locator('table')).toBeVisible();
    await expect(page.locator('button', { hasText: 'Nuevo Residente' })).toBeVisible();
  });

  test('should create a new resident', async ({ page }) => {
    await page.goto('/residents');
    
    // Click new resident button
    await page.click('button', { hasText: 'Nuevo Residente' });
    await page.waitForURL('/residents/create');
    
    // Fill form
    await page.selectOption('select[name="document_type"]', 'CC');
    await page.fill('input[name="document_number"]', '12345678');
    await page.fill('input[name="first_name"]', 'Juan');
    await page.fill('input[name="last_name"]', 'Pérez');
    await page.fill('input[name="email"]', 'juan.perez@example.com');
    await page.fill('input[name="apartment_number"]', '101');
    await page.selectOption('select[name="resident_type"]', 'Owner');
    await page.fill('input[name="start_date"]', '2024-01-01');
    
    // Submit form
    await page.click('button[type="submit"]');
    await page.waitForURL('/residents');
    
    // Verify success message
    await expect(page.locator('text=Residente creado exitosamente')).toBeVisible();
    
    // Verify resident appears in table
    await expect(page.locator('td', { hasText: 'Juan Pérez' })).toBeVisible();
    await expect(page.locator('td', { hasText: 'CC 12345678' })).toBeVisible();
  });

  test('should edit an existing resident', async ({ page }) => {
    // First create a resident
    await page.goto('/residents/create');
    
    await page.selectOption('select[name="document_type"]', 'CC');
    await page.fill('input[name="document_number"]', '87654321');
    await page.fill('input[name="first_name"]', 'María');
    await page.fill('input[name="last_name"]', 'González');
    await page.fill('input[name="email"]', 'maria.gonzalez@example.com');
    await page.fill('input[name="apartment_number"]', '202');
    await page.selectOption('select[name="resident_type"]', 'Tenant');
    await page.fill('input[name="start_date"]', '2024-01-01');
    
    await page.click('button[type="submit"]');
    await page.waitForURL('/residents');
    
    // Click edit button
    await page.click('button[aria-label="Editar"]', { hasText: 'edit' });
    
    // Update resident
    await page.fill('input[name="first_name"]', 'María Carmen');
    await page.fill('input[name="last_name"]', 'González López');
    
    await page.click('button[type="submit"]');
    await page.waitForURL('/residents');
    
    // Verify updated resident
    await expect(page.locator('td', { hasText: 'María Carmen González López' })).toBeVisible();
  });

  test('should view resident details', async ({ page }) => {
    // First create a resident
    await page.goto('/residents/create');
    
    await page.selectOption('select[name="document_type"]', 'CC');
    await page.fill('input[name="document_number"]', '11111111');
    await page.fill('input[name="first_name"]', 'Pedro');
    await page.fill('input[name="last_name"]', 'Martínez');
    await page.fill('input[name="email"]', 'pedro.martinez@example.com');
    await page.fill('input[name="apartment_number"]', '303');
    await page.selectOption('select[name="resident_type"]', 'Owner');
    await page.fill('input[name="start_date"]', '2024-01-01');
    
    await page.click('button[type="submit"]');
    await page.waitForURL('/residents');
    
    // Click view button
    await page.click('button[aria-label="Ver"]', { hasText: 'eye' });
    
    // Verify resident details
    await expect(page.locator('h2')).toContainText('Detalles del Residente');
    await expect(page.locator('text=Pedro Martínez')).toBeVisible();
    await expect(page.locator('text=CC 11111111')).toBeVisible();
    await expect(page.locator('text=pedro.martinez@example.com')).toBeVisible();
    await expect(page.locator('text=303')).toBeVisible();
    await expect(page.locator('text=Propietario')).toBeVisible();
  });

  test('should delete a resident', async ({ page }) => {
    // First create a resident
    await page.goto('/residents/create');
    
    await page.selectOption('select[name="document_type"]', 'CC');
    await page.fill('input[name="document_number"]', '99999999');
    await page.fill('input[name="first_name"]', 'Ana');
    await page.fill('input[name="last_name"]', 'Rodríguez');
    await page.fill('input[name="email"]', 'ana.rodriguez@example.com');
    await page.fill('input[name="apartment_number"]', '404');
    await page.selectOption('select[name="resident_type"]', 'Family');
    await page.fill('input[name="start_date"]', '2024-01-01');
    
    await page.click('button[type="submit"]');
    await page.waitForURL('/residents');
    
    // Handle confirmation dialog
    page.on('dialog', dialog => dialog.accept());
    
    // Click delete button
    await page.click('button[aria-label="Eliminar"]', { hasText: 'trash' });
    
    // Verify resident is deleted
    await expect(page.locator('text=Residente eliminado exitosamente')).toBeVisible();
    await expect(page.locator('td', { hasText: 'Ana Rodríguez' })).not.toBeVisible();
  });

  test('should search residents', async ({ page }) => {
    // First create multiple residents
    const residents = [
      { document: '11111111', name: 'Juan', lastname: 'Pérez', email: 'juan.perez@example.com', apartment: '101' },
      { document: '22222222', name: 'María', lastname: 'González', email: 'maria.gonzalez@example.com', apartment: '202' },
      { document: '33333333', name: 'Pedro', lastname: 'Martínez', email: 'pedro.martinez@example.com', apartment: '303' }
    ];

    for (const resident of residents) {
      await page.goto('/residents/create');
      await page.selectOption('select[name="document_type"]', 'CC');
      await page.fill('input[name="document_number"]', resident.document);
      await page.fill('input[name="first_name"]', resident.name);
      await page.fill('input[name="last_name"]', resident.lastname);
      await page.fill('input[name="email"]', resident.email);
      await page.fill('input[name="apartment_number"]', resident.apartment);
      await page.selectOption('select[name="resident_type"]', 'Owner');
      await page.fill('input[name="start_date"]', '2024-01-01');
      await page.click('button[type="submit"]');
      await page.waitForURL('/residents');
    }

    // Search for specific resident
    await page.fill('input[placeholder*="Buscar"]', 'Juan');
    await page.keyboard.press('Enter');
    
    await expect(page.locator('td', { hasText: 'Juan Pérez' })).toBeVisible();
    await expect(page.locator('td', { hasText: 'María González' })).not.toBeVisible();
    await expect(page.locator('td', { hasText: 'Pedro Martínez' })).not.toBeVisible();
  });

  test('should filter residents by status', async ({ page }) => {
    // Create active and inactive residents
    await page.goto('/residents/create');
    await page.selectOption('select[name="document_type"]', 'CC');
    await page.fill('input[name="document_number"]', '44444444');
    await page.fill('input[name="first_name"]', 'Carlos');
    await page.fill('input[name="last_name"]', 'López');
    await page.fill('input[name="email"]', 'carlos.lopez@example.com');
    await page.fill('input[name="apartment_number"]', '505');
    await page.selectOption('select[name="resident_type"]', 'Owner');
    await page.selectOption('select[name="status"]', 'Active');
    await page.fill('input[name="start_date"]', '2024-01-01');
    await page.click('button[type="submit"]');
    await page.waitForURL('/residents');

    await page.goto('/residents/create');
    await page.selectOption('select[name="document_type"]', 'CC');
    await page.fill('input[name="document_number"]', '55555555');
    await page.fill('input[name="first_name"]', 'Laura');
    await page.fill('input[name="last_name"]', 'Hernández');
    await page.fill('input[name="email"]', 'laura.hernandez@example.com');
    await page.fill('input[name="apartment_number"]', '606');
    await page.selectOption('select[name="resident_type"]', 'Tenant');
    await page.selectOption('select[name="status"]', 'Inactive');
    await page.fill('input[name="start_date"]', '2024-01-01');
    await page.click('button[type="submit"]');
    await page.waitForURL('/residents');

    // Filter by active status
    await page.selectOption('select[name="status"]', 'Active');
    await page.keyboard.press('Enter');
    
    await expect(page.locator('td', { hasText: 'Carlos López' })).toBeVisible();
    await expect(page.locator('td', { hasText: 'Laura Hernández' })).not.toBeVisible();
  });

  test('should validate required fields', async ({ page }) => {
    await page.goto('/residents/create');
    
    // Try to submit empty form
    await page.click('button[type="submit"]');
    
    // Verify validation errors
    await expect(page.locator('text=El campo tipo de documento es obligatorio')).toBeVisible();
    await expect(page.locator('text=El campo número de documento es obligatorio')).toBeVisible();
    await expect(page.locator('text=El campo nombres es obligatorio')).toBeVisible();
    await expect(page.locator('text=El campo apellidos es obligatorio')).toBeVisible();
    await expect(page.locator('text=El campo email es obligatorio')).toBeVisible();
  });

  test('should validate unique document number', async ({ page }) => {
    // Create first resident
    await page.goto('/residents/create');
    await page.selectOption('select[name="document_type"]', 'CC');
    await page.fill('input[name="document_number"]', '12345678');
    await page.fill('input[name="first_name"]', 'Juan');
    await page.fill('input[name="last_name"]', 'Pérez');
    await page.fill('input[name="email"]', 'juan.perez@example.com');
    await page.fill('input[name="apartment_number"]', '101');
    await page.selectOption('select[name="resident_type"]', 'Owner');
    await page.fill('input[name="start_date"]', '2024-01-01');
    await page.click('button[type="submit"]');
    await page.waitForURL('/residents');

    // Try to create second resident with same document
    await page.goto('/residents/create');
    await page.selectOption('select[name="document_type"]', 'CC');
    await page.fill('input[name="document_number"]', '12345678');
    await page.fill('input[name="first_name"]', 'María');
    await page.fill('input[name="last_name"]', 'González');
    await page.fill('input[name="email"]', 'maria.gonzalez@example.com');
    await page.fill('input[name="apartment_number"]', '202');
    await page.selectOption('select[name="resident_type"]', 'Tenant');
    await page.fill('input[name="start_date"]', '2024-01-01');
    await page.click('button[type="submit"]');

    // Verify validation error
    await expect(page.locator('text=El número de documento ya está en uso')).toBeVisible();
  });
});