# ⚡ DevOps Quick Reference Card - Tavira

**Quick access to common DevOps commands and procedures**

---

## 🚀 Deployment

### Quick Deploy
```bash
# Deploy with latest image
./scripts/deploy.sh

# Deploy with specific version
./scripts/deploy.sh v20250115-abc123
```

### Manual Deploy
```bash
# Build image
docker build -t ingmontoyav/tavira-app:latest .

# Push image
docker push ingmontoyav/tavira-app:latest

# Update deployment
kubectl set image deployment/tavira-app php-fpm=ingmontoyav/tavira-app:latest

# Check status
kubectl rollout status deployment/tavira-app
```

### Rollback
```bash
# Rollback to previous version
kubectl rollout undo deployment/tavira-app

# Rollback to specific revision
kubectl rollout undo deployment/tavira-app --to-revision=2

# View history
kubectl rollout history deployment/tavira-app
```

---

## 📊 Monitoring

### Pod Status
```bash
# List pods
kubectl get pods -l app=tavira

# Watch pods
kubectl get pods -l app=tavira -w

# Describe pod
kubectl describe pod <pod-name>

# Get pod details
kubectl get pod <pod-name> -o yaml
```

### Logs
```bash
# View logs
kubectl logs <pod-name> -c php-fpm

# Follow logs
kubectl logs -f <pod-name> -c php-fpm

# Last 100 lines
kubectl logs <pod-name> -c php-fpm --tail=100

# Previous logs (if crashed)
kubectl logs <pod-name> -c php-fpm --previous

# All containers
kubectl logs <pod-name> --all-containers=true
```

### Resources
```bash
# Pod resource usage
kubectl top pods -l app=tavira

# Node resource usage
kubectl top nodes

# Describe node
kubectl describe node <node-name>

# Get events
kubectl get events --sort-by='.lastTimestamp'
```

---

## 🔧 Troubleshooting

### Quick Diagnostics
```bash
# Get pod name
POD=$(kubectl get pods -l app=tavira -o jsonpath='{.items[0].metadata.name}')

# Check pod status
kubectl get pods -l app=tavira

# View logs
kubectl logs -f $POD -c php-fpm

# Describe pod
kubectl describe pod $POD

# Check events
kubectl get events --sort-by='.lastTimestamp' | tail -20
```

### Common Issues
```bash
# Pod in CrashLoopBackOff
kubectl logs <pod-name> -c php-fpm --previous

# Pod not ready
kubectl describe pod <pod-name> | grep -A 5 "Readiness"

# Database connection error
kubectl exec -it <pod-name> -c php-fpm -- php artisan db:show

# Permission denied
kubectl exec -it <pod-name> -c php-fpm -- ls -la storage/

# High memory
kubectl top pods -l app=tavira
```

---

## 🐳 Container Operations

### Execute Commands
```bash
# Open shell
kubectl exec -it <pod-name> -c php-fpm -- /bin/sh

# Run artisan command
kubectl exec -it <pod-name> -c php-fpm -- php artisan migrate

# Run tinker
kubectl exec -it <pod-name> -c php-fpm -- php artisan tinker

# Check PHP version
kubectl exec -it <pod-name> -c php-fpm -- php -v

# Check extensions
kubectl exec -it <pod-name> -c php-fpm -- php -m
```

### Database Operations
```bash
# Check database connection
kubectl exec -it <pod-name> -c php-fpm -- php artisan db:show

# Run migrations
kubectl exec -it <pod-name> -c php-fpm -- php artisan migrate --force

# Run tenant migrations
kubectl exec -it <pod-name> -c php-fpm -- php artisan tenants:migrate --force

# Check migration status
kubectl exec -it <pod-name> -c php-fpm -- php artisan migrate:status

# Rollback migrations
kubectl exec -it <pod-name> -c php-fpm -- php artisan migrate:rollback
```

### Cache Operations
```bash
# Clear all caches
kubectl exec -it <pod-name> -c php-fpm -- php artisan cache:clear

# Cache config
kubectl exec -it <pod-name> -c php-fpm -- php artisan config:cache

# Cache routes
kubectl exec -it <pod-name> -c php-fpm -- php artisan route:cache

# Cache views
kubectl exec -it <pod-name> -c php-fpm -- php artisan view:cache

# Clear config
kubectl exec -it <pod-name> -c php-fpm -- php artisan config:clear
```

---

## 🔐 Secrets & Configuration

### Manage Secrets
```bash
# List secrets
kubectl get secrets

# View secret
kubectl get secret laravel-env -o yaml

# Create secret
kubectl create secret generic laravel-env --from-literal=KEY=value

# Edit secret
kubectl edit secret laravel-env

# Delete secret
kubectl delete secret laravel-env

# Recreate secret
kubectl delete secret laravel-env
kubectl create secret generic laravel-env --from-literal=...
```

### Manage ConfigMaps
```bash
# List configmaps
kubectl get configmaps

# View configmap
kubectl get configmap tavira-nginx-config -o yaml

# Edit configmap
kubectl edit configmap tavira-nginx-config

# Delete configmap
kubectl delete configmap tavira-nginx-config
```

---

## 🌐 Network & Services

### Service Management
```bash
# List services
kubectl get services

# Describe service
kubectl describe service tavira-service

# Get service endpoints
kubectl get endpoints tavira-service

# Port forward
kubectl port-forward service/tavira-service 8080:80

# Test service
kubectl run -it --rm debug --image=alpine --restart=Never -- wget -O- http://tavira-service/health
```

### Ingress Management
```bash
# List ingress
kubectl get ingress

# Describe ingress
kubectl describe ingress tavira-ingress

# Edit ingress
kubectl edit ingress tavira-ingress

# Check TLS certificate
kubectl get certificate

# Describe certificate
kubectl describe certificate tavira-tls
```

---

## 💾 Storage

### PVC Management
```bash
# List PVCs
kubectl get pvc

# Describe PVC
kubectl describe pvc tavira-storage-pvc

# Check PV
kubectl get pv

# Check storage class
kubectl get storageclass
```

### File Operations
```bash
# Copy from pod
kubectl cp <pod-name>:/var/www/html/storage/logs/laravel.log ./laravel.log

# Copy to pod
kubectl cp ./file.txt <pod-name>:/var/www/html/

# List files
kubectl exec -it <pod-name> -c php-fpm -- ls -la storage/

# Check disk usage
kubectl exec -it <pod-name> -c php-fpm -- du -sh storage/
```

---

## 🔄 Restart & Scaling

### Restart
```bash
# Restart deployment
kubectl rollout restart deployment/tavira-app

# Restart pod
kubectl delete pod <pod-name>

# Restart all pods
kubectl delete pods -l app=tavira
```

### Scaling
```bash
# Scale deployment
kubectl scale deployment tavira-app --replicas=3

# Check current replicas
kubectl get deployment tavira-app

# Edit replicas
kubectl edit deployment tavira-app
```

---

## 📋 Useful Aliases

Add to your `.bashrc` or `.zshrc`:

```bash
# Kubernetes aliases
alias k='kubectl'
alias kgp='kubectl get pods'
alias kgd='kubectl get deployment'
alias kgs='kubectl get service'
alias kl='kubectl logs'
alias klf='kubectl logs -f'
alias kex='kubectl exec -it'
alias kdesc='kubectl describe'
alias kaf='kubectl apply -f'
alias kdel='kubectl delete'
alias kctx='kubectl config current-context'
alias kns='kubectl config set-context --current --namespace'

# Tavira specific
alias tavira-logs='kubectl logs -f deployment/tavira-app -c php-fpm'
alias tavira-shell='kubectl exec -it $(kubectl get pods -l app=tavira -o jsonpath="{.items[0].metadata.name}") -c php-fpm -- /bin/sh'
alias tavira-migrate='kubectl exec -it $(kubectl get pods -l app=tavira -o jsonpath="{.items[0].metadata.name}") -c php-fpm -- php artisan migrate --force'
alias tavira-cache='kubectl exec -it $(kubectl get pods -l app=tavira -o jsonpath="{.items[0].metadata.name}") -c php-fpm -- php artisan cache:clear'
alias tavira-status='kubectl get pods -l app=tavira && kubectl get service tavira-service && kubectl get ingress tavira-ingress'
```

---

## 🎯 Common Workflows

### Deploy New Version
```bash
# 1. Build and push
docker build -t ingmontoyav/tavira-app:v1.2.3 .
docker push ingmontoyav/tavira-app:v1.2.3

# 2. Deploy
./scripts/deploy.sh v1.2.3

# 3. Verify
kubectl rollout status deployment/tavira-app
kubectl logs -f deployment/tavira-app -c php-fpm
```

### Run Migrations
```bash
# 1. Get pod name
POD=$(kubectl get pods -l app=tavira -o jsonpath='{.items[0].metadata.name}')

# 2. Run migrations
kubectl exec -it $POD -c php-fpm -- php artisan migrate --force

# 3. Run tenant migrations
kubectl exec -it $POD -c php-fpm -- php artisan tenants:migrate --force

# 4. Verify
kubectl exec -it $POD -c php-fpm -- php artisan migrate:status
```

### Clear Caches
```bash
# 1. Get pod name
POD=$(kubectl get pods -l app=tavira -o jsonpath='{.items[0].metadata.name}')

# 2. Clear all caches
kubectl exec -it $POD -c php-fpm -- php artisan cache:clear
kubectl exec -it $POD -c php-fpm -- php artisan config:clear
kubectl exec -it $POD -c php-fpm -- php artisan route:clear
kubectl exec -it $POD -c php-fpm -- php artisan view:clear

# 3. Regenerate caches
kubectl exec -it $POD -c php-fpm -- php artisan config:cache
kubectl exec -it $POD -c php-fpm -- php artisan route:cache
kubectl exec -it $POD -c php-fpm -- php artisan view:cache
```

### Troubleshoot Pod
```bash
# 1. Get pod name
POD=$(kubectl get pods -l app=tavira -o jsonpath='{.items[0].metadata.name}')

# 2. Check status
kubectl describe pod $POD

# 3. View logs
kubectl logs $POD -c php-fpm

# 4. Check database
kubectl exec -it $POD -c php-fpm -- php artisan db:show

# 5. Open shell
kubectl exec -it $POD -c php-fpm -- /bin/sh
```

---

## 📞 Emergency Procedures

### Application Down
```bash
# 1. Check pod status
kubectl get pods -l app=tavira

# 2. Check logs
kubectl logs <pod-name> -c php-fpm

# 3. Restart pod
kubectl delete pod <pod-name>

# 4. Monitor recovery
kubectl logs -f <new-pod-name> -c php-fpm

# 5. If still down, rollback
kubectl rollout undo deployment/tavira-app
```

### Database Down
```bash
# 1. Check database pod
kubectl get pods -l app=postgres

# 2. Check database logs
kubectl logs <postgres-pod>

# 3. Restart database
kubectl delete pod <postgres-pod>

# 4. Verify connectivity
kubectl exec -it <app-pod> -c php-fpm -- php artisan db:show
```

### High Memory/CPU
```bash
# 1. Check resource usage
kubectl top pods -l app=tavira

# 2. Clear caches
kubectl exec -it <pod-name> -c php-fpm -- php artisan cache:clear

# 3. Restart pod
kubectl rollout restart deployment/tavira-app

# 4. Scale up if needed
kubectl scale deployment tavira-app --replicas=3
```

---

## 🔗 Useful Links

- [Deployment Guide](./DEPLOYMENT.md)
- [DevOps Summary](./DEVOPS-SUMMARY.md)
- [Troubleshooting Guide](./DEVOPS-TROUBLESHOOTING.md)
- [Deployment Checklist](./DEVOPS-CHECKLIST.md)
- [Kubernetes Docs](https://kubernetes.io/docs/)
- [Docker Docs](https://docs.docker.com/)
- [Laravel Docs](https://laravel.com/docs/)

---

## 💡 Tips & Tricks

### Get Pod Name Quickly
```bash
POD=$(kubectl get pods -l app=tavira -o jsonpath='{.items[0].metadata.name}')
echo $POD
```

### Watch Deployment
```bash
kubectl get pods -l app=tavira -w
```

### Follow Logs in Real-Time
```bash
kubectl logs -f deployment/tavira-app -c php-fpm
```

### Port Forward to Local
```bash
kubectl port-forward service/tavira-service 8080:80
# Visit: http://localhost:8080
```

### Debug Pod
```bash
kubectl run -it --rm debug --image=alpine --restart=Never -- /bin/sh
```

### Check All Resources
```bash
kubectl get all -l app=tavira
```

---

**Last Updated:** 2025-01-15
**Version:** 1.0.0
**Maintained by:** DevOps Team
