# Production Environment Variables

This document lists all environment variables configured in the Kubernetes `laravel-env` secret for production deployment.

## Application Configuration

```bash
APP_ENV=production
APP_URL=https://tavira.com.co
APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_CO
```

## Tenancy Configuration

```bash
TENANCY_CENTRAL_DOMAINS=tavira.com.co
```

Note: In production, only the main domain should be listed. Localhost and 127.0.0.1 are automatically excluded.

## Mail Configuration

```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp.fastmail.com
MAIL_PORT=465
MAIL_USERNAME=hola@tavira.com.co
MAIL_PASSWORD=<secret>
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=hola@tavira.com.co
MAIL_FROM_NAME="Tavira"
```

**Important**: The Fastmail account `hola@tavira.com.co` needs to be enabled for SMTP sending through the Fastmail web interface.

## Payment Gateway (Wompi)

```bash
WOMPI_PUBLIC_KEY=pub_test_dtHgEkvvO0n7SaMGTaapM9QXm9rWGKGm
WOMPI_PRIVATE_KEY=<secret>
WOMPI_EVENT_SECRET=<secret>
WOMPI_INTEGRITY_SECRET=<secret>
```

## Error Tracking (Sentry)

```bash
SENTRY_LARAVEL_DSN=https://d4ea9896894c9ff980f0b46c73171809@o4509974733848576.ingest.us.sentry.io/4509974735683584
```

## How to Update Variables

1. **Get the base64-encoded value**:
   ```bash
   echo -n "your-value" | base64
   ```

2. **Patch the secret**:
   ```bash
   kubectl patch secret laravel-env --type='json' -p='[{
     "op": "add",
     "path": "/data/YOUR_VAR_NAME",
     "value": "base64-encoded-value"
   }]'
   ```

3. **Restart the deployment** to load the new values:
   ```bash
   kubectl rollout restart deployment/tavira-app
   ```

## Deployment Notes

- All variables are loaded automatically via `envFrom` in the deployment configuration
- No need to modify deployment.yaml when adding new variables to the secret
- Configuration cache should be cleared after environment changes:
  ```bash
  kubectl exec deployment/tavira-app -- php artisan config:clear
  ```
