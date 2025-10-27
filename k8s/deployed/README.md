# Kubernetes Deployed Configuration

This directory contains the **actual deployed configurations** exported from the production Kubernetes cluster.

## 📁 Directory Structure

```
k8s/deployed/
├── README.md                           # This file
├── deployment.yaml                      # Tavira app deployment
├── service.yaml                        # Tavira app service
├── nginx-config.yaml                   # Nginx ConfigMap
├── ingress.yaml                        # Ingress configuration (with wildcard routing)
├── certificates.yaml                   # SSL certificates (main + tenant subdomains)
├── postgres-deployment.yaml            # PostgreSQL deployment
├── postgres-service.yaml               # PostgreSQL service
├── redis-deployment.yaml               # Redis deployment
├── redis-service.yaml                  # Redis service
├── pvcs.yaml                          # Persistent Volume Claims
└── laravel-env-secret-template.yaml   # Secret template (without real values)
```

## 🚀 Current Production Setup

### Application Stack

- **PHP Application**: Laravel 12 with Inertia.js
- **Image**: `ingmontoyav/tavira-app:v20251027-cf6bafe`
- **Replicas**: 2
- **Containers per Pod**: 2 (php-fpm + nginx sidecar)
- **Port**: 80 (nginx)

### Database & Cache

- **PostgreSQL**: Version 15
  - Database: `tavira_production`
  - Port: 5432
  - Storage: 10Gi PVC
- **Redis**: Latest
  - Port: 6379
  - Storage: 1Gi PVC

### Key Features

1. **Multi-container Pod Architecture**
   - `php-fpm` container: Runs PHP application
   - `nginx` sidecar: Serves static files and proxies to PHP-FPM
   - Shared volume via emptyDir for application files

2. **Environment Configuration**
   - All environment variables loaded from `laravel-env` secret via `envFrom`
   - No need to modify deployment when adding new environment variables

3. **Networking**
   - ClusterIP service on port 80
   - Ingress configured for `tavira.com.co` and wildcard `*.tavira.com.co`
   - Per-subdomain SSL certificates using Let's Encrypt (HTTP-01 challenge)

## 🔧 How These Files Were Generated

All files were exported from the live Kubernetes cluster:

```bash
# Export deployment
kubectl get deployment tavira-app -o yaml > deployment.yaml

# Export service
kubectl get service tavira-service -o yaml > service.yaml

# Export ConfigMap
kubectl get configmap tavira-nginx-config -o yaml > nginx-config.yaml

# Export Ingress
kubectl get ingress -o yaml > ingress.yaml

# Export database infrastructure
kubectl get deployment postgres -o yaml > postgres-deployment.yaml
kubectl get service postgres -o yaml > postgres-service.yaml

# Export Redis
kubectl get deployment redis -o yaml > redis-deployment.yaml
kubectl get service redis -o yaml > redis-service.yaml

# Export PVCs
kubectl get pvc -o yaml > pvcs.yaml
```

## 🔐 Secret Management

The `laravel-env` secret contains all application environment variables. For security, actual values are **not** committed to git.

- **Template**: `laravel-env-secret-template.yaml`
- **Documentation**: `../../PRODUCTION-ENV.md`

### Adding/Updating Environment Variables

1. **Encode the value**:
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

3. **Restart the deployment**:
   ```bash
   kubectl rollout restart deployment/tavira-app
   ```

## 📝 Important Configuration Details

### 1. Nginx Configuration (nginx-config.yaml)

- Serves static files with 1-year cache
- Proxies PHP requests to `127.0.0.1:9000` (same pod)
- Passes X-Forwarded-* headers to PHP-FPM
- Health check endpoint at `/health`
- Security headers enabled

### 2. PHP-FPM Configuration

- Listens on port 9000
- Runs as non-root user (www-data)
- Uses emptyDir volume for application files
- Init container copies files on startup

### 3. Environment Variables

Key environment variables configured in `laravel-env` secret:

- `APP_ENV=production`
- `APP_URL=https://tavira.com.co`
- `TENANCY_CENTRAL_DOMAINS=tavira.com.co` (production only)
- `APP_LOCALE=es`
- Database, Redis, Mail, Wompi, Sentry credentials

See `../../PRODUCTION-ENV.md` for complete list.

## 🔄 Deployment Workflow

1. **Code Changes**: Push to `main` branch
2. **CI/CD**: GitHub Actions builds Docker image
3. **Image Tag**: Format `v20251027-<short-sha>`
4. **Deploy**: Update deployment image
   ```bash
   kubectl set image deployment/tavira-app php-fpm=ingmontoyav/tavira-app:v20251027-<sha>
   kubectl rollout status deployment/tavira-app
   ```
5. **Clear Cache** (if needed):
   ```bash
   kubectl exec deployment/tavira-app -- php artisan config:clear
   kubectl exec deployment/tavira-app -- php artisan route:clear
   kubectl exec deployment/tavira-app -- php artisan view:clear
   ```

## 🔐 SSL Certificate Management

### Certificate Architecture

The application uses **per-subdomain SSL certificates** instead of wildcard certificates:
- Main domain (`tavira.com.co`) has its own certificate
- Each tenant subdomain gets its own certificate
- Uses Let's Encrypt with HTTP-01 challenge validation
- Automatically issued via cert-manager

**Why not wildcard?** Wildcard certificates (`*.tavira.com.co`) require DNS-01 challenge validation, which needs DNS provider API credentials. Per-subdomain certificates are simpler and more secure.

### Current Certificates

Check all certificates:
```bash
kubectl get certificates
```

Example output:
```
NAME                         READY   SECRET                       AGE
tavira-tls                   True    tavira-tls                   47h
torresdevillacampestre-tls   True    torresdevillacampestre-tls   5m
```

### Adding SSL Certificate for New Tenant

When a new tenant subdomain is created (e.g., `nuevo-conjunto.tavira.com.co`):

1. **Create Certificate resource**:
   ```bash
   cat <<EOF | kubectl apply -f -
   apiVersion: cert-manager.io/v1
   kind: Certificate
   metadata:
     name: nuevo-conjunto-tls
     namespace: default
   spec:
     secretName: nuevo-conjunto-tls
     issuerRef:
       name: letsencrypt-prod
       kind: ClusterIssuer
     dnsNames:
     - nuevo-conjunto.tavira.com.co
   EOF
   ```

2. **Update Ingress** to include the subdomain:
   ```bash
   kubectl patch ingress tavira-ingress --type=json -p='[
     {
       "op": "add",
       "path": "/spec/rules/-",
       "value": {
         "host": "nuevo-conjunto.tavira.com.co",
         "http": {
           "paths": [{
             "path": "/",
             "pathType": "Prefix",
             "backend": {
               "service": {
                 "name": "tavira-service",
                 "port": {"number": 80}
               }
             }
           }]
         }
       }
     },
     {
       "op": "add",
       "path": "/spec/tls/-",
       "value": {
         "hosts": ["nuevo-conjunto.tavira.com.co"],
         "secretName": "nuevo-conjunto-tls"
       }
     }
   ]'
   ```

3. **Verify certificate issuance** (usually takes 30-60 seconds):
   ```bash
   kubectl get certificate nuevo-conjunto-tls
   # Wait until READY: True
   ```

4. **Test HTTPS access**:
   ```bash
   curl -I https://nuevo-conjunto.tavira.com.co
   ```

### Certificate Troubleshooting

If certificate is not issuing (READY: False):

```bash
# Check certificate details
kubectl describe certificate <certificate-name>

# Check certificate request
kubectl get certificaterequest

# Check ACME order
kubectl get order

# Check challenge
kubectl get challenge

# View cert-manager logs
kubectl logs -n cert-manager deployment/cert-manager
```

Common issues:
- **DNS not pointing to server**: Ensure subdomain DNS A record points to server IP
- **Ingress not updated**: Subdomain must be in Ingress rules for HTTP-01 challenge to work
- **Rate limits**: Let's Encrypt has rate limits (50 certificates per domain per week)

### Automating Certificate Creation

**TODO**: Create a Laravel event listener that automatically creates Kubernetes Certificate resources when a new tenant is created.

## 🐛 Troubleshooting

### View Logs
```bash
kubectl logs deployment/tavira-app -c php-fpm --tail=100
kubectl logs deployment/tavira-app -c nginx --tail=100
```

### Check Pod Status
```bash
kubectl get pods -l app=tavira-app
kubectl describe pod <pod-name>
```

### Execute Commands
```bash
kubectl exec -it deployment/tavira-app -- php artisan tinker
kubectl exec -it deployment/tavira-app -- sh
```

### Check Environment Variables
```bash
kubectl exec deployment/tavira-app -- env | grep APP
```

## 📊 Resource Monitoring

### CPU & Memory
```bash
kubectl top pod -l app=tavira-app
kubectl top pod -l app=postgres
kubectl top pod -l app=redis
```

### Storage
```bash
kubectl get pvc
kubectl describe pvc postgres-storage
```

## 🔗 Related Documentation

- [Production Environment Variables](../../PRODUCTION-ENV.md)
- [Deployment Guide](../../DEPLOYMENT.md)
- [DNS Setup](../../DNS-SETUP.md)

## ⚠️ Important Notes

1. **DO NOT commit actual secrets** - Only commit templates
2. **Image tags**: Always use specific commit-based tags, not `:latest`
3. **Database migrations**: Run tenant migrations with `php artisan tenants:migrate --force`
4. **Config cache**: Clear after environment changes
5. **URL generation**: Ensure `TENANCY_CENTRAL_DOMAINS` is set correctly for production
6. **SSL Certificates**: Create a new Certificate resource for each tenant subdomain
7. **Ingress updates**: Add tenant subdomain to Ingress after creating certificate

## 📅 Last Updated

- **Date**: 2025-10-27
- **Image**: `ingmontoyav/tavira-app:v20251027-cf6bafe`
- **Kubernetes**: K3s cluster
- **Status**: Production-ready ✅
