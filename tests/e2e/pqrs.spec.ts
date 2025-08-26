import { test, expect, type Page } from '@playwright/test';

// Test data setup
const testPqrs = {
  type: 'queja',
  subject: 'Prueba de PQRS Automatizada',
  description: 'Esta es una descripción de prueba para el PQRS creado durante la revisión de diseño automatizada.',
  priority: 'media',
  contact_name: 'Usuario Prueba',
  contact_email: 'usuario.prueba@test.com',
  contact_phone: '3001234567'
};

test.describe('PQRS Design Review - Comprehensive Testing', () => {
  test.beforeEach(async ({ page }) => {
    // Login as admin user
    await page.goto('/login');
    await page.fill('input[name="email"]', 'mauricio.montoyav@gmail.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('/dashboard');
    
    // Navigate to PQRS module
    await page.goto('/pqrs');
    await page.waitForLoadState('networkidle');
  });

  test.describe('Phase 1: Interaction and User Flow Testing', () => {
    test('should display PQRS index page with proper structure', async ({ page }) => {
      // Check main elements
      await expect(page.locator('h1')).toContainText('PQRS');
      await expect(page.locator('text=Gestión de Peticiones, Quejas, Reclamos y Sugerencias')).toBeVisible();
      
      // Check filters section
      await expect(page.locator('input[placeholder*="Buscar"]')).toBeVisible();
      await expect(page.locator('[placeholder="Tipo"]')).toBeVisible();
      await expect(page.locator('[placeholder="Estado"]')).toBeVisible();
      await expect(page.locator('[placeholder="Prioridad"]')).toBeVisible();
      
      // Check table structure
      await expect(page.locator('table')).toBeVisible();
      await expect(page.locator('th:has-text("Ticket")')).toBeVisible();
      await expect(page.locator('th:has-text("Tipo")')).toBeVisible();
      await expect(page.locator('th:has-text("Asunto")')).toBeVisible();
      await expect(page.locator('th:has-text("Estado")')).toBeVisible();
    });

    test('should navigate to create PQRS form', async ({ page }) => {
      await page.click('text=Nuevo PQRS');
      await page.waitForURL('/pqrs/create');
      
      // Check form structure
      await expect(page.locator('h1')).toContainText('Nuevo PQRS');
      await expect(page.locator('select[name="type"]')).toBeVisible();
      await expect(page.locator('input[name="subject"]')).toBeVisible();
      await expect(page.locator('textarea[name="description"]')).toBeVisible();
      await expect(page.locator('select[name="priority"]')).toBeVisible();
      await expect(page.locator('input[name="contact_name"]')).toBeVisible();
      await expect(page.locator('input[name="contact_email"]')).toBeVisible();
    });

    test('should create a new PQRS successfully', async ({ page }) => {
      await page.click('text=Nuevo PQRS');
      await page.waitForURL('/pqrs/create');
      
      // Fill form
      await page.selectOption('select[name="type"]', testPqrs.type);
      await page.fill('input[name="subject"]', testPqrs.subject);
      await page.fill('textarea[name="description"]', testPqrs.description);
      await page.selectOption('select[name="priority"]', testPqrs.priority);
      await page.fill('input[name="contact_name"]', testPqrs.contact_name);
      await page.fill('input[name="contact_email"]', testPqrs.contact_email);
      await page.fill('input[name="contact_phone"]', testPqrs.contact_phone);
      
      // Submit form
      await page.click('button[type="submit"]');
      await page.waitForURL('/pqrs');
      
      // Verify creation success
      await expect(page.locator('text=' + testPqrs.subject)).toBeVisible();
    });

    test('should test search functionality', async ({ page }) => {
      // Wait for page to load completely
      await page.waitForSelector('input[placeholder*="Buscar"]');
      
      // Test search
      await page.fill('input[placeholder*="Buscar"]', 'Prueba');
      await page.waitForTimeout(1000); // Wait for debounced search
      
      // Should show filtered results or empty state
      const hasResults = await page.locator('tbody tr').first().isVisible();
      if (hasResults) {
        // Check if search term appears in results
        const firstRow = await page.locator('tbody tr').first().textContent();
        expect(firstRow).toMatch(/prueba/i);
      }
    });

    test('should test filter dropdowns', async ({ page }) => {
      // Test type filter
      await page.click('[placeholder="Tipo"]');
      await page.click('text=Queja');
      await page.waitForTimeout(500);
      
      // Test priority filter
      await page.click('[placeholder="Prioridad"]');
      await page.click('text=Alta');
      await page.waitForTimeout(500);
      
      // Test status filter
      await page.click('[placeholder="Estado"]');
      await page.click('text=Abierto');
      await page.waitForTimeout(500);
      
      // Clear filters
      await page.click('text=Limpiar filtros');
      await page.waitForTimeout(500);
    });
  });

  test.describe('Phase 2: Responsiveness Testing', () => {
    test('should display properly on desktop (1440x900)', async ({ page }) => {
      await page.setViewportSize({ width: 1440, height: 900 });
      await page.reload();
      await page.waitForLoadState('networkidle');
      
      // Take screenshot for visual review
      await page.screenshot({ path: 'pqrs-desktop-1440.png', fullPage: true });
      
      // Check table is fully visible
      await expect(page.locator('table')).toBeVisible();
      
      // Check all filter elements are visible in a single row
      const filtersContainer = page.locator('.grid-cols-1.lg\\:grid-cols-6');
      await expect(filtersContainer).toBeVisible();
    });

    test('should adapt layout for tablet (768px)', async ({ page }) => {
      await page.setViewportSize({ width: 768, height: 1024 });
      await page.reload();
      await page.waitForLoadState('networkidle');
      
      // Take screenshot for visual review
      await page.screenshot({ path: 'pqrs-tablet-768.png', fullPage: true });
      
      // Check table is still accessible
      await expect(page.locator('table')).toBeVisible();
      
      // Check no horizontal scrolling in main container
      const bodyWidth = await page.evaluate(() => document.body.scrollWidth);
      expect(bodyWidth).toBeLessThanOrEqual(768);
    });

    test('should be mobile optimized (375px)', async ({ page }) => {
      await page.setViewportSize({ width: 375, height: 667 });
      await page.reload();
      await page.waitForLoadState('networkidle');
      
      // Take screenshot for visual review
      await page.screenshot({ path: 'pqrs-mobile-375.png', fullPage: true });
      
      // Check main elements are accessible
      await expect(page.locator('h1')).toBeVisible();
      await expect(page.locator('text=Nuevo PQRS')).toBeVisible();
      
      // Check no horizontal overflow
      const bodyWidth = await page.evaluate(() => document.body.scrollWidth);
      expect(bodyWidth).toBeLessThanOrEqual(375);
      
      // Check table responsiveness
      await expect(page.locator('table')).toBeVisible();
    });
  });

  test.describe('Phase 4: Accessibility Compliance Testing', () => {
    test('should have proper keyboard navigation', async ({ page }) => {
      // Test tab order
      await page.keyboard.press('Tab'); // Search input
      await expect(page.locator('input[placeholder*="Buscar"]')).toBeFocused();
      
      await page.keyboard.press('Tab'); // Type filter
      await expect(page.locator('[placeholder="Tipo"]')).toBeFocused();
      
      await page.keyboard.press('Tab'); // Status filter
      await expect(page.locator('[placeholder="Estado"]')).toBeFocused();
      
      await page.keyboard.press('Tab'); // Priority filter
      await expect(page.locator('[placeholder="Prioridad"]')).toBeFocused();
    });

    test('should have proper ARIA labels and semantic HTML', async ({ page }) => {
      // Check for proper heading structure
      const h1 = await page.locator('h1').count();
      expect(h1).toBe(1);
      
      // Check table has proper structure
      await expect(page.locator('table')).toHaveAttribute('role');
      await expect(page.locator('thead')).toBeVisible();
      await expect(page.locator('tbody')).toBeVisible();
      
      // Check buttons have accessible names
      const createButton = page.locator('text=Nuevo PQRS');
      await expect(createButton).toBeVisible();
      
      const filterButtons = page.locator('button[placeholder]');
      const buttonCount = await filterButtons.count();
      expect(buttonCount).toBeGreaterThan(0);
    });

    test('should have visible focus indicators', async ({ page }) => {
      // Test focus visibility on interactive elements
      await page.keyboard.press('Tab');
      const focusedElement = page.locator(':focus');
      
      // Check focus styles are applied
      const focusStyles = await focusedElement.evaluate((el) => {
        const styles = window.getComputedStyle(el);
        return {
          outline: styles.outline,
          outlineWidth: styles.outlineWidth,
          boxShadow: styles.boxShadow
        };
      });
      
      // Should have some form of focus indication
      const hasFocusIndicator = focusStyles.outline !== 'none' || 
                               focusStyles.outlineWidth !== '0px' || 
                               focusStyles.boxShadow.includes('focus');
      expect(hasFocusIndicator).toBeTruthy();
    });
  });

  test.describe('Phase 5: Robustness Testing', () => {
    test('should handle empty states gracefully', async ({ page }) => {
      // Search for something that likely doesn't exist
      await page.fill('input[placeholder*="Buscar"]', 'xyz123nonexistent');
      await page.waitForTimeout(1000);
      
      // Should show empty state message
      await expect(page.locator('text=No se encontraron registros')).toBeVisible();
    });

    test('should validate form inputs', async ({ page }) => {
      await page.click('text=Nuevo PQRS');
      await page.waitForURL('/pqrs/create');
      
      // Try to submit empty form
      await page.click('button[type="submit"]');
      
      // Should show validation errors or prevent submission
      const currentUrl = await page.url();
      expect(currentUrl).toContain('/pqrs/create'); // Should stay on create page
    });

    test('should handle long content gracefully', async ({ page }) => {
      // Create PQRS with very long content
      await page.click('text=Nuevo PQRS');
      await page.waitForURL('/pqrs/create');
      
      const longText = 'A'.repeat(1000);
      await page.selectOption('select[name="type"]', 'peticion');
      await page.fill('input[name="subject"]', longText);
      await page.fill('textarea[name="description"]', longText);
      await page.selectOption('select[name="priority"]', 'baja');
      await page.fill('input[name="contact_name"]', 'Test User');
      await page.fill('input[name="contact_email"]', 'test@example.com');
      
      // Form should handle long content without breaking layout
      const subjectInput = page.locator('input[name="subject"]');
      const isVisible = await subjectInput.isVisible();
      expect(isVisible).toBeTruthy();
    });
  });

  test.describe('Phase 7: Console and Error Testing', () => {
    test('should not have console errors', async ({ page }) => {
      const consoleErrors: string[] = [];
      
      page.on('console', (msg) => {
        if (msg.type() === 'error') {
          consoleErrors.push(msg.text());
        }
      });
      
      // Navigate through the module
      await page.reload();
      await page.waitForLoadState('networkidle');
      
      await page.click('text=Nuevo PQRS');
      await page.waitForLoadState('networkidle');
      
      // Check for console errors
      expect(consoleErrors).toHaveLength(0);
    });

    test('should handle network errors gracefully', async ({ page }) => {
      // Simulate slow network
      await page.route('**/pqrs**', async route => {
        await page.waitForTimeout(100);
        await route.continue();
      });
      
      await page.reload();
      
      // Should still load even with slow network
      await expect(page.locator('h1')).toContainText('PQRS');
    });
  });

  test.describe('Phase 1.5: Advanced User Flow Testing', () => {
    test('should test PQRS detail view', async ({ page }) => {
      // Click on first row's view button if available
      const firstRowActions = page.locator('tbody tr').first().locator('button[aria-haspopup="menu"]');
      if (await firstRowActions.isVisible()) {
        await firstRowActions.click();
        
        const viewLink = page.locator('text=Ver detalles');
        if (await viewLink.isVisible()) {
          await viewLink.click();
          await page.waitForURL(/\/pqrs\/\d+$/);
          
          // Check detail view elements
          await expect(page.locator('h1')).toBeVisible();
          await expect(page.locator('text=Información del PQRS')).toBeVisible();
        }
      }
    });

    test('should test hover states and interactions', async ({ page }) => {
      // Test table row hover
      const firstRow = page.locator('tbody tr').first();
      if (await firstRow.isVisible()) {
        await firstRow.hover();
        
        // Should have hover styles
        const hoverClass = await firstRow.getAttribute('class');
        expect(hoverClass).toContain('hover:bg-gray-50');
      }
      
      // Test button hover states
      const createButton = page.locator('text=Nuevo PQRS');
      await createButton.hover();
      
      // Button should be interactive
      await expect(createButton).toBeEnabled();
    });
  });
});