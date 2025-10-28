# Kubernetes Secrets Configuration for Tavira

This guide explains how to manage the Laravel environment secrets in Kubernetes.

## Files

- `laravel-secret.yaml` - Kubernetes Secret definition with all environment variables
- `deployment-optimized.yaml` - Updated deployment that references the secret

## Quick Start

### 1. Update Secret Values

Before applying, edit `laravel-secret.yaml` and update these placeholder values:

```bash
# Replace YOUR_APP_KEY_HERE with your actual Laravel app key
# Generate with: php artisan key:generate --show

# Replace YOUR_DB_PASSWORD_HERE with your actual database password
```

### 2. Apply the Secret

Apply the secret to your Kubernetes cluster:

```bash
# Create or update the secret
kubectl apply -f k8s/laravel-secret.yaml

# Verify the secret was created
kubectl get secret laravel-env -n default

# View secret keys (values will be hidden)
kubectl describe secret laravel-env -n default
```

### 3. Apply the Updated Deployment

After the secret is created, apply the updated deployment:

```bash
# Apply the deployment
kubectl apply -f k8s/deployment-optimized.yaml

# Check rollout status
kubectl rollout status deployment/tavira-app -n default

# Verify pods are running
kubectl get pods -l app=tavira -n default
```

## Updating Secret Values

When you need to update secret values:

### Option 1: Using kubectl (Quick Method)

```bash
# Update a single value
kubectl patch secret laravel-env -n default \
  --type='json' \
  -p='[{"op": "replace", "path": "/stringData/MAIL_PASSWORD", "value": "new_password"}]'

# Restart deployment to pick up changes
kubectl rollout restart deployment/tavira-app -n default
```

### Option 2: Using YAML File (Recommended)

```bash
# Edit laravel-secret.yaml with your changes
vim k8s/laravel-secret.yaml

# Apply the updated secret
kubectl apply -f k8s/laravel-secret.yaml

# Restart deployment to pick up changes
kubectl rollout restart deployment/tavira-app -n default
```

## Environment Variables Included

### Application
- APP_KEY
- APP_LOCALE (es)
- APP_FALLBACK_LOCALE (es)
- APP_FAKER_LOCALE (es)

### Database
- DB_DATABASE
- DB_USERNAME
- DB_PASSWORD

### Cache & Queue
- CACHE_DRIVER (redis)
- REDIS_HOST
- REDIS_PORT
- QUEUE_CONNECTION (redis)

### Session
- SESSION_DRIVER (database)
- SESSION_LIFETIME (120)
- SESSION_ENCRYPT (false)
- SESSION_PATH (/)
- SESSION_DOMAIN (.tavira.com.co)
- SESSION_SECURE_COOKIE (true)
- SESSION_SAME_SITE (lax)

### Mail (Fastmail)
- MAIL_MAILER (smtp)
- MAIL_HOST (smtp.fastmail.com)
- MAIL_PORT (465)
- MAIL_USERNAME
- MAIL_PASSWORD
- MAIL_FROM_ADDRESS
- MAIL_FROM_NAME
- MAIL_ENCRYPTION (ssl)

### Security & CORS
- SANCTUM_STATEFUL_DOMAINS
- CORS_ALLOWED_ORIGINS
- TRUSTED_PROXIES

### Payment Gateway (Wompi)
- WOMPI_PUBLIC_KEY (test keys)
- WOMPI_PRIVATE_KEY (test keys)
- WOMPI_PRIVATE_EVENT_KEY (test keys)

### Error Tracking (Sentry)
- SENTRY_LARAVEL_DSN
- SENTRY_SEND_DEFAULT_PII (true)
- SENTRY_TRACES_SAMPLE_RATE (1.0)

## Security Notes

1. **Never commit secrets to Git**: The `laravel-secret.yaml` file is for deployment only. Consider using sealed-secrets or external secret management for production.

2. **Use strong passwords**: Replace all placeholder values with strong, unique passwords.

3. **Production keys**: Update Wompi keys from test to production keys when going live.

4. **Sentry sample rate**: Consider reducing `SENTRY_TRACES_SAMPLE_RATE` in production (0.1 = 10% of requests).

## Troubleshooting

### Check if pods can access secrets:

```bash
# Get pod name
POD_NAME=$(kubectl get pods -l app=tavira -n default -o jsonpath='{.items[0].metadata.name}')

# Check environment variables in pod
kubectl exec -it $POD_NAME -n default -c php-fpm -- env | grep -E "MAIL|SESSION|WOMPI|SENTRY"
```

### View secret values (base64 encoded):

```bash
kubectl get secret laravel-env -n default -o yaml
```

### Decode a specific secret value:

```bash
kubectl get secret laravel-env -n default -o jsonpath='{.data.MAIL_PASSWORD}' | base64 --decode
```

## Production Considerations

1. **External Secrets**: Consider using [External Secrets Operator](https://external-secrets.io/) to sync secrets from AWS Secrets Manager, Azure Key Vault, or Google Secret Manager.

2. **Sealed Secrets**: Use [Sealed Secrets](https://github.com/bitnami-labs/sealed-secrets) to safely store encrypted secrets in Git.

3. **RBAC**: Restrict access to secrets using Kubernetes RBAC policies.

4. **Rotation**: Implement a secret rotation policy, especially for database passwords and API keys.
