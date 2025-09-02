# Deployment Instructions for Tavira Tenancy

## Asset Loading Issues Fix

### Problem
Tenants in production show 404 errors for assets with URLs like `/tenancy/assets/build/assets/*`

### Solution Steps

#### 1. Enable ViteBundler Feature
In `config/tenancy.php`, ensure ViteBundler is enabled:
```php
'features' => [
    Stancl\Tenancy\Features\UserImpersonation::class,
    Stancl\Tenancy\Features\ViteBundler::class, // <- This must be enabled
],
```

#### 2. Update .htaccess Configuration
The `.htaccess` file must include rules to handle tenancy assets:
```apache
# Handle tenancy assets - IMPORTANT: This must be BEFORE other rewrites
RewriteCond %{REQUEST_URI} ^/tenancy/assets/(.*)$
RewriteRule ^tenancy/assets/(.*)$ /build/$1 [L]
```

#### 3. Build Assets for Production
Run the build command:
```bash
npm run build
```

#### 4. Verify Permissions
Ensure the `public/build/` directory has proper read permissions:
```bash
chmod -R 755 public/build/
```

#### 5. Environment Configuration
In production `.env`, ensure:
```env
APP_ENV=production
APP_DEBUG=false
ASSET_URL=https://yourdomain.com
```

#### 6. Web Server Configuration

##### Apache
Use the provided `.htaccess-production-example` file as reference.

##### Nginx (Alternative)
If using nginx, add this location block:
```nginx
location ~* ^/tenancy/assets/(.+)$ {
    try_files /build/$1 =404;
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

### Troubleshooting

1. **Check build directory exists**: `ls -la public/build/`
2. **Verify manifest**: `head public/build/manifest.json`
3. **Test asset access**: `curl -I https://yourtenant.tavira.com.co/tenancy/assets/build/assets/app-[hash].js`
4. **Check server logs** for 404 errors

### Notes
- The ViteBundler feature uses the `global_asset()` helper instead of `asset()`
- Assets are served from `/tenancy/assets/*` for tenant domains
- Central domain assets work normally from `/build/*`