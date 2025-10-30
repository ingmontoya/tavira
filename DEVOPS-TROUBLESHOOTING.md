# 🔧 DevOps Troubleshooting Guide - Tavira

**Purpose:** Quick reference for diagnosing and resolving common DevOps issues  
**Last Updated:** 2025-01-15  
**Version:** 1.0.0

---

## 📋 Table of Contents

1. [Pod Issues](#pod-issues)
2. [Container Issues](#container-issues)
3. [Network Issues](#network-issues)
4. [Database Issues](#database-issues)
5. [Storage Issues](#storage-issues)
6. [Performance Issues](#performance-issues)
7. [Security Issues](#security-issues)
8. [Deployment Issues](#deployment-issues)
9. [Debugging Tools](#debugging-tools)
10. [Quick Fixes](#quick-fixes)

---

## 🐳 Pod Issues

### Issue: Pod in CrashLoopBackOff

**Symptoms:**
- Pod keeps restarting
- Status shows `CrashLoopBackOff`
- Container exits immediately

**Diagnosis:**
```bash
# Check pod status
kubectl get pods -l app=tavira

# View logs from previous run
kubectl logs <pod-name> -c php-fpm --previous

# Describe pod for events
kubectl describe pod <pod-name>

# Check exit code
kubectl get pod <pod-name> -o jsonpath='{.status.containerStatuses[0].lastState.terminated.exitCode}'
```

**Common Causes & Solutions:**

| Cause | Solution |
|-------|----------|
| Missing environment variables | Verify secrets: `kubectl get secret laravel-env -o yaml` |
| Invalid APP_KEY | Regenerate: `php artisan key:generate --show` |
| Database connection failure | Check DB credentials and connectivity |
| Permission denied on storage | Fix permissions: `chmod -R 775 storage bootstrap/cache` |
| Out of memory | Increase memory limit in deployment |
| Disk space full | Check node disk usage: `kubectl top nodes` |

**Resolution Steps:**
```bash
# 1. Check logs
kubectl logs <pod-name> -c php-fpm --previous

# 2. Verify secrets
kubectl get secret laravel-env -o yaml | grep -A 1 "APP_KEY\|DB_"

# 3. Check pod events
kubectl describe pod <pod-name> | grep -A 10 "Events:"

# 4. Verify resource availability
kubectl describe node <node-name> | grep -A 5 "Allocated resources"

# 5. Delete pod to force restart
kubectl delete pod <pod-name>

# 6. Monitor new pod
kubectl logs -f <new-pod-name> -c php-fpm
```

---

### Issue: Pod Pending

**Symptoms:**
- Pod status shows `Pending`
- Pod not starting
- No containers running

**Diagnosis:**
```bash
# Check pod status
kubectl get pods -l app=tavira

# Describe pod
kubectl describe pod <pod-name>

# Check node resources
kubectl top nodes
kubectl describe nodes
```

**Common Causes & Solutions:**

| Cause | Solution |
|-------|----------|
| Insufficient CPU | Reduce CPU requests or add nodes |
| Insufficient memory | Reduce memory requests or add nodes |
| No available nodes | Scale cluster or free resources |
| PVC not bound | Check storage class: `kubectl get storageclass` |
| Node selector mismatch | Verify node labels: `kubectl get nodes --show-labels` |

**Resolution Steps:**
```bash
# 1. Check node resources
kubectl top nodes

# 2. Check PVC status
kubectl get pvc

# 3. Check storage class
kubectl get storageclass

# 4. Describe pod for details
kubectl describe pod <pod-name>

# 5. Add nodes if needed
# (Depends on your cluster provider)

# 6. Reduce resource requests if needed
kubectl edit deployment tavira-app
# Reduce: resources.requests.cpu and memory
```

---

### Issue: Pod Not Ready

**Symptoms:**
- Pod shows `0/2 Ready`
- Readiness probe failing
- Pod not receiving traffic

**Diagnosis:**
```bash
# Check pod readiness
kubectl get pods -l app=tavira

# Check readiness probe
kubectl describe pod <pod-name> | grep -A 5 "Readiness"

# Check logs
kubectl logs <pod-name> -c php-fpm

# Test connectivity
kubectl exec -it <pod-name> -c php-fpm -- nc -zv localhost 9000
```

**Common Causes & Solutions:**

| Cause | Solution |
|-------|----------|
| PHP-FPM not listening | Check PHP-FPM status and logs |
| Database not ready | Wait for database or check connectivity |
| Cache generation in progress | Increase initialDelaySeconds |
| Port not exposed | Verify port in deployment |
| Health check too strict | Adjust probe thresholds |

**Resolution Steps:**
```bash
# 1. Check PHP-FPM status
kubectl exec -it <pod-name> -c php-fpm -- netstat -tuln | grep 9000

# 2. Check database connectivity
kubectl exec -it <pod-name> -c php-fpm -- php artisan db:show

# 3. Check logs for errors
kubectl logs <pod-name> -c php-fpm | tail -50

# 4. Increase probe delays
kubectl edit deployment tavira-app
# Increase: readinessProbe.initialDelaySeconds

# 5. Monitor pod
kubectl logs -f <pod-name> -c php-fpm
```

---

## 🐳 Container Issues

### Issue: Container Exiting with Code 1

**Symptoms:**
- Container exits immediately
- Exit code 1 in pod status
- Application not starting

**Diagnosis:**
```bash
# Check exit code
kubectl get pod <pod-name> -o jsonpath='{.status.containerStatuses[0].lastState.terminated.exitCode}'

# View logs
kubectl logs <pod-name> -c php-fpm --previous

# Check entrypoint
kubectl describe pod <pod-name> | grep -A 5 "Command:"
```

**Common Causes:**
- PHP syntax error
- Missing required file
- Invalid configuration
- Database migration failure
- Permission denied

**Resolution:**
```bash
# 1. Check logs for error message
kubectl logs <pod-name> -c php-fpm --previous

# 2. Verify PHP syntax
docker run -it ingmontoyav/tavira-app:latest php -l app/Http/Controllers/SomeController.php

# 3. Check file permissions
kubectl exec -it <pod-name> -c php-fpm -- ls -la storage/

# 4. Verify configuration
kubectl exec -it <pod-name> -c php-fpm -- php artisan config:show

# 5. Check migrations
kubectl exec -it <pod-name> -c php-fpm -- php artisan migrate:status
```

---

### Issue: High Memory Usage

**Symptoms:**
- Container using > 80% of limit
- OOMKilled errors
- Pod restarting frequently

**Diagnosis:**
```bash
# Check memory usage
kubectl top pods -l app=tavira

# Check memory limit
kubectl describe pod <pod-name> | grep -A 5 "Limits:"

# Check memory requests
kubectl describe pod <pod-name> | grep -A 5 "Requests:"

# Monitor memory over time
kubectl top pods -l app=tavira --containers
```

**Common Causes:**
- Memory leak in application
- Large dataset in memory
- Inefficient query
- Cache not clearing
- Too many processes

**Resolution:**
```bash
# 1. Identify memory-heavy processes
kubectl exec -it <pod-name> -c php-fpm -- ps aux --sort=-%mem | head -10

# 2. Check for memory leaks
kubectl logs <pod-name> -c php-fpm | grep -i "memory\|leak"

# 3. Increase memory limit
kubectl edit deployment tavira-app
# Increase: resources.limits.memory

# 4. Optimize queries
# Review slow query logs

# 5. Clear caches
kubectl exec -it <pod-name> -c php-fpm -- php artisan cache:clear

# 6. Restart pod
kubectl rollout restart deployment/tavira-app
```

---

### Issue: High CPU Usage

**Symptoms:**
- Container using > 80% of limit
- Slow response times
- High load average

**Diagnosis:**
```bash
# Check CPU usage
kubectl top pods -l app=tavira

# Check CPU limit
kubectl describe pod <pod-name> | grep -A 5 "Limits:"

# Check running processes
kubectl exec -it <pod-name> -c php-fpm -- ps aux --sort=-%cpu | head -10

# Check load average
kubectl exec -it <pod-name> -c php-fpm -- uptime
```

**Common Causes:**
- Inefficient code
- Infinite loop
- Heavy computation
- Too many concurrent requests
- Unoptimized queries

**Resolution:**
```bash
# 1. Identify CPU-heavy processes
kubectl exec -it <pod-name> -c php-fpm -- ps aux --sort=-%cpu

# 2. Check for infinite loops
kubectl logs <pod-name> -c php-fpm | grep -i "loop\|hang"

# 3. Profile application
# Use Laravel Debugbar or Xdebug

# 4. Optimize queries
# Add indexes, use eager loading

# 5. Increase CPU limit
kubectl edit deployment tavira-app
# Increase: resources.limits.cpu

# 6. Scale horizontally
kubectl scale deployment tavira-app --replicas=3
```

---

## 🌐 Network Issues

### Issue: Service Not Accessible

**Symptoms:**
- Cannot reach application
- Connection refused
- Timeout errors

**Diagnosis:**
```bash
# Check service
kubectl get service tavira-service

# Check endpoints
kubectl get endpoints tavira-service

# Test connectivity from pod
kubectl exec -it <pod-name> -c nginx -- wget -O- http://localhost/health

# Test from another pod
kubectl run -it --rm debug --image=alpine --restart=Never -- wget -O- http://tavira-service/health
```

**Common Causes & Solutions:**

| Cause | Solution |
|-------|----------|
| Service has no endpoints | Check pod status and readiness |
| Wrong port mapping | Verify service port and targetPort |
| Network policy blocking | Check network policies |
| Ingress misconfigured | Verify ingress rules |
| DNS not resolving | Check DNS configuration |

**Resolution:**
```bash
# 1. Check service endpoints
kubectl get endpoints tavira-service

# 2. Verify pod readiness
kubectl get pods -l app=tavira

# 3. Check service configuration
kubectl describe service tavira-service

# 4. Test DNS
kubectl exec -it <pod-name> -- nslookup tavira-service

# 5. Test connectivity
kubectl exec -it <pod-name> -- wget -O- http://tavira-service:80/health

# 6. Check network policies
kubectl get networkpolicies
```

---

### Issue: DNS Resolution Failing

**Symptoms:**
- Cannot resolve service names
- `nslookup` fails
- Connection to database fails

**Diagnosis:**
```bash
# Test DNS from pod
kubectl exec -it <pod-name> -c php-fpm -- nslookup tavira-service

# Check DNS configuration
kubectl get configmap coredns -n kube-system -o yaml

# Check DNS pod
kubectl get pods -n kube-system -l k8s-app=kube-dns

# Test DNS resolution
kubectl exec -it <pod-name> -c php-fpm -- cat /etc/resolv.conf
```

**Common Causes:**
- CoreDNS not running
- DNS pod crashed
- Network connectivity issue
- Incorrect DNS configuration

**Resolution:**
```bash
# 1. Check CoreDNS pods
kubectl get pods -n kube-system -l k8s-app=kube-dns

# 2. Check CoreDNS logs
kubectl logs -n kube-system -l k8s-app=kube-dns

# 3. Restart CoreDNS
kubectl rollout restart deployment/coredns -n kube-system

# 4. Check DNS configuration
kubectl get configmap coredns -n kube-system -o yaml

# 5. Test DNS again
kubectl exec -it <pod-name> -c php-fpm -- nslookup tavira-service
```

---

### Issue: Ingress Not Working

**Symptoms:**
- Cannot access application via domain
- Ingress shows no IP
- 404 or 502 errors

**Diagnosis:**
```bash
# Check ingress
kubectl get ingress

# Describe ingress
kubectl describe ingress tavira-ingress

# Check ingress controller
kubectl get pods -n ingress-nginx

# Check ingress logs
kubectl logs -n ingress-nginx -l app.kubernetes.io/name=ingress-nginx
```

**Common Causes & Solutions:**

| Cause | Solution |
|-------|----------|
| Ingress controller not running | Install ingress controller |
| Ingress rules incorrect | Verify host and path |
| Service not found | Check service exists |
| TLS certificate missing | Create certificate |
| DNS not pointing to ingress | Update DNS records |

**Resolution:**
```bash
# 1. Check ingress controller
kubectl get pods -n ingress-nginx

# 2. Check ingress configuration
kubectl describe ingress tavira-ingress

# 3. Check service
kubectl get service tavira-service

# 4. Check TLS certificate
kubectl get certificate

# 5. Check DNS
nslookup tavira.com.co

# 6. Test ingress
curl -H "Host: tavira.com.co" http://<ingress-ip>/
```

---

## 💾 Database Issues

### Issue: Database Connection Timeout

**Symptoms:**
- "Connection timeout" errors
- Database queries failing
- Pod not becoming ready

**Diagnosis:**
```bash
# Test database connectivity
kubectl exec -it <pod-name> -c php-fpm -- php artisan db:show

# Check database credentials
kubectl get secret laravel-env -o yaml | grep DB_

# Test network connectivity
kubectl exec -it <pod-name> -c php-fpm -- nc -zv postgres 5432

# Check database pod
kubectl get pods -l app=postgres
```

**Common Causes & Solutions:**

| Cause | Solution |
|-------|----------|
| Database pod not running | Check database pod status |
| Wrong credentials | Verify DB_USERNAME and DB_PASSWORD |
| Wrong host | Verify DB_HOST (should be service name) |
| Network connectivity | Check network policies |
| Database not accepting connections | Check database logs |

**Resolution:**
```bash
# 1. Check database pod
kubectl get pods -l app=postgres

# 2. Check database logs
kubectl logs <postgres-pod> | tail -50

# 3. Verify credentials
kubectl get secret laravel-env -o yaml | grep DB_

# 4. Test connectivity
kubectl exec -it <pod-name> -c php-fpm -- nc -zv postgres 5432

# 5. Check database service
kubectl get service postgres

# 6. Restart database pod
kubectl delete pod <postgres-pod>
```

---

### Issue: Migration Failure

**Symptoms:**
- Migration errors in logs
- Database schema not updated
- Application errors about missing columns

**Diagnosis:**
```bash
# Check migration status
kubectl exec -it <pod-name> -c php-fpm -- php artisan migrate:status

# Check migration logs
kubectl logs <pod-name> -c php-fpm | grep -i "migration\|error"

# Check database schema
kubectl exec -it <pod-name> -c php-fpm -- php artisan db:show
```

**Common Causes:**
- Migration already run
- SQL syntax error
- Missing table
- Constraint violation
- Insufficient permissions

**Resolution:**
```bash
# 1. Check migration status
kubectl exec -it <pod-name> -c php-fpm -- php artisan migrate:status

# 2. Rollback last migration
kubectl exec -it <pod-name> -c php-fpm -- php artisan migrate:rollback

# 3. Fix migration file
# Edit migration file and fix errors

# 4. Run migration again
kubectl exec -it <pod-name> -c php-fpm -- php artisan migrate --force

# 5. Check for errors
kubectl logs <pod-name> -c php-fpm | grep -i "error"
```

---

## 💾 Storage Issues

### Issue: PVC Not Binding

**Symptoms:**
- PVC status shows `Pending`
- Pod cannot mount volume
- Pod stuck in `Pending` state

**Diagnosis:**
```bash
# Check PVC status
kubectl get pvc

# Describe PVC
kubectl describe pvc tavira-storage-pvc

# Check storage class
kubectl get storageclass

# Check PV
kubectl get pv
```

**Common Causes & Solutions:**

| Cause | Solution |
|-------|----------|
| Storage class not found | Create storage class or use default |
| No available PV | Create PV or enable dynamic provisioning |
| Insufficient space | Free up space or add storage |
| Node selector mismatch | Verify node labels |

**Resolution:**
```bash
# 1. Check storage class
kubectl get storageclass

# 2. Check available PVs
kubectl get pv

# 3. Describe PVC for details
kubectl describe pvc tavira-storage-pvc

# 4. Create storage class if missing
kubectl apply -f k8s/storage-class.yaml

# 5. Delete and recreate PVC
kubectl delete pvc tavira-storage-pvc
kubectl apply -f k8s/deployment-optimized.yaml
```

---

### Issue: Permission Denied on Storage

**Symptoms:**
- "Permission denied" errors
- Cannot write to storage
- Application cannot create files

**Diagnosis:**
```bash
# Check storage permissions
kubectl exec -it <pod-name> -c php-fpm -- ls -la storage/

# Check ownership
kubectl exec -it <pod-name> -c php-fpm -- stat storage/

# Try to write
kubectl exec -it <pod-name> -c php-fpm -- touch storage/test.txt
```

**Common Causes:**
- Wrong ownership
- Wrong permissions
- Read-only filesystem
- SELinux restrictions

**Resolution:**
```bash
# 1. Check current permissions
kubectl exec -it <pod-name> -c php-fpm -- ls -la storage/

# 2. Fix ownership
kubectl exec -it <pod-name> -c php-fpm -- chown -R www-data:www-data storage/

# 3. Fix permissions
kubectl exec -it <pod-name> -c php-fpm -- chmod -R 775 storage/

# 4. Verify write access
kubectl exec -it <pod-name> -c php-fpm -- touch storage/test.txt

# 5. Clean up test file
kubectl exec -it <pod-name> -c php-fpm -- rm storage/test.txt
```

---

## ⚡ Performance Issues

### Issue: Slow Response Times

**Symptoms:**
- Response time > 500ms
- Users reporting slowness
- High latency

**Diagnosis:**
```bash
# Check response times
# Use monitoring tools (Prometheus, Grafana)

# Check database query time
kubectl exec -it <pod-name> -c php-fpm -- php artisan tinker
# > DB::enableQueryLog();
# > // Run query
# > dd(DB::getQueryLog());

# Check OPcache hit rate
kubectl exec -it <pod-name> -c php-fpm -- php -i | grep opcache

# Check resource usage
kubectl top pods -l app=tavira
```

**Common Causes:**
- Unoptimized queries
- Missing database indexes
- OPcache disabled
- High memory usage
- High CPU usage

**Resolution:**
```bash
# 1. Enable query logging
kubectl exec -it <pod-name> -c php-fpm -- php artisan tinker
# > DB::enableQueryLog();

# 2. Identify slow queries
# Check Laravel logs for slow queries

# 3. Add database indexes
# Run: php artisan migrate

# 4. Verify OPcache
kubectl exec -it <pod-name> -c php-fpm -- php -i | grep opcache

# 5. Clear caches
kubectl exec -it <pod-name> -c php-fpm -- php artisan cache:clear

# 6. Scale horizontally
kubectl scale deployment tavira-app --replicas=3
```

---

### Issue: High Error Rate

**Symptoms:**
- Many 5xx errors
- Error logs filling up
- Sentry showing errors

**Diagnosis:**
```bash
# Check error logs
kubectl logs <pod-name> -c php-fpm | grep -i "error\|exception"

# Check Sentry
# Visit Sentry dashboard

# Check application logs
kubectl exec -it <pod-name> -c php-fpm -- tail -100 storage/logs/laravel.log
```

**Common Causes:**
- Database connection issues
- Missing environment variables
- Code errors
- External service failures
- Resource exhaustion

**Resolution:**
```bash
# 1. Check logs
kubectl logs <pod-name> -c php-fpm | grep -i "error"

# 2. Check Sentry for error details
# Visit Sentry dashboard

# 3. Check database connectivity
kubectl exec -it <pod-name> -c php-fpm -- php artisan db:show

# 4. Check environment variables
kubectl exec -it <pod-name> -c php-fpm -- env | grep APP_

# 5. Check external services
# Verify API connectivity

# 6. Restart pod if needed
kubectl rollout restart deployment/tavira-app
```

---

## 🔐 Security Issues

### Issue: Secrets Exposed in Logs

**Symptoms:**
- Secrets visible in logs
- Credentials in error messages
- API keys in output

**Diagnosis:**
```bash
# Search logs for secrets
kubectl logs <pod-name> -c php-fpm | grep -i "password\|key\|token"

# Check environment variables
kubectl exec -it <pod-name> -c php-fpm -- env | grep -i "password\|key"

# Check error logs
kubectl exec -it <pod-name> -c php-fpm -- tail storage/logs/laravel.log
```

**Resolution:**
```bash
# 1. Rotate exposed secrets
kubectl delete secret laravel-env
kubectl create secret generic laravel-env --from-literal=...

# 2. Update application to mask secrets
# Use Laravel's Str::mask() or similar

# 3. Clear logs
kubectl exec -it <pod-name> -c php-fpm -- rm storage/logs/laravel.log

# 4. Restart pod
kubectl rollout restart deployment/tavira-app

# 5. Review security practices
# Implement secret masking in logs
```

---

### Issue: Unauthorized Access

**Symptoms:**
- Users accessing resources they shouldn't
- Authentication bypass
- Authorization failures

**Diagnosis:**
```bash
# Check authentication logs
kubectl logs <pod-name> -c php-fpm | grep -i "auth\|unauthorized"

# Check user permissions
kubectl exec -it <pod-name> -c php-fpm -- php artisan tinker
# > User::find(1)->roles;

# Check CORS configuration
kubectl get configmap tavira-nginx-config -o yaml | grep -i "cors"
```

**Resolution:**
```bash
# 1. Review authentication code
# Check auth middleware

# 2. Verify authorization policies
# Check policy files

# 3. Check CORS configuration
# Verify allowed origins

# 4. Review user roles
# Check role assignments

# 5. Clear sessions if needed
kubectl exec -it <pod-name> -c php-fpm -- php artisan session:clear
```

---

## 🚀 Deployment Issues

### Issue: Deployment Stuck

**Symptoms:**
- Deployment not progressing
- Pods not updating
- Rollout status shows waiting

**Diagnosis:**
```bash
# Check deployment status
kubectl rollout status deployment/tavira-app

# Check pod status
kubectl get pods -l app=tavira

# Check events
kubectl get events --sort-by='.lastTimestamp' | tail -20

# Describe deployment
kubectl describe deployment tavira-app
```

**Common Causes:**
- Pod not becoming ready
- Resource constraints
- Image pull failure
- Health check failing

**Resolution:**
```bash
# 1. Check pod status
kubectl get pods -l app=tavira

# 2. Check pod logs
kubectl logs <pod-name> -c php-fpm

# 3. Check events
kubectl describe pod <pod-name>

# 4. Increase timeout
kubectl rollout status deployment/tavira-app --timeout=10m

# 5. Rollback if needed
kubectl rollout undo deployment/tavira-app
```

---

### Issue: Image Pull Failure

**Symptoms:**
- Pod status shows `ImagePullBackOff`
- Cannot pull image
- Authentication error

**Diagnosis:**
```bash
# Check pod status
kubectl get pods -l app=tavira

# Describe pod
kubectl describe pod <pod-name>

# Check image pull secrets
kubectl get secrets
```

**Common Causes:**
- Image doesn't exist
- Wrong image name
- Authentication failure
- Registry not accessible

**Resolution:**
```bash
# 1. Verify image exists
docker pull ingmontoyav/tavira-app:latest

# 2. Check image name in deployment
kubectl get deployment tavira-app -o yaml | grep image:

# 3. Create image pull secret if needed
kubectl create secret docker-registry regcred \
  --docker-server=docker.io \
  --docker-username=<username> \
  --docker-password=<password>

# 4. Update deployment to use secret
kubectl patch deployment tavira-app -p '{"spec":{"template":{"spec":{"imagePullSecrets":[{"name":"regcred"}]}}}}'

# 5. Retry deployment
kubectl rollout restart deployment/tavira-app
```

---

## 🛠️ Debugging Tools

### Essential Commands

```bash
# Pod information
kubectl get pods -l app=tavira
kubectl describe pod <pod-name>
kubectl logs <pod-name> -c php-fpm
kubectl logs <pod-name> -c nginx
kubectl logs <pod-name> -c php-fpm --previous
kubectl logs -f <pod-name> -c php-fpm

# Execute commands
kubectl exec -it <pod-name> -c php-fpm -- /bin/sh
kubectl exec -it <pod-name> -c php-fpm -- php artisan tinker
kubectl exec -it <pod-name> -c php-fpm -- php artisan db:show

# Port forwarding
kubectl port-forward <pod-name> 8080:80
kubectl port-forward service/tavira-service 8080:80

# Copy files
kubectl cp <pod-name>:/var/www/html/storage/logs/laravel.log ./laravel.log
kubectl cp ./file.txt <pod-name>:/var/www/html/

# Resource monitoring
kubectl top pods -l app=tavira
kubectl top nodes
kubectl describe node <node-name>

# Events
kubectl get events --sort-by='.lastTimestamp'
kubectl describe pod <pod-name> | grep -A 10 "Events:"
```

### Debugging Pod

```bash
# Create debug pod
kubectl run -it --rm debug --image=alpine --restart=Never -- /bin/sh

# Inside debug pod:
# Test DNS
nslookup tavira-service

# Test connectivity
wget -O- http://tavira-service/health

# Test database
nc -zv postgres 5432

# Test Redis
nc -zv redis 6379
```

### Monitoring Tools

```bash
# Prometheus
kubectl port-forward -n monitoring svc/prometheus 9090:9090

# Grafana
kubectl port-forward -n monitoring svc/grafana 3000:3000

# Kubernetes Dashboard
kubectl proxy
# Visit: http://localhost:8001/api/v1/namespaces/kubernetes-dashboard/services/https:kubernetes-dashboard:/proxy/

# Lens (Desktop)
# Download from: https://k8slens.dev/
```

---

## ⚡ Quick Fixes

### Pod Not Ready - Quick Fix

```bash
# 1. Check logs
kubectl logs <pod-name> -c php-fpm | tail -20

# 2. Restart pod
kubectl delete pod <pod-name>

# 3. Monitor new pod
kubectl logs -f <new-pod-name> -c php-fpm
```

### Database Connection Error - Quick Fix

```bash
# 1. Verify credentials
kubectl get secret laravel-env -o yaml | grep DB_

# 2. Test connectivity
kubectl exec -it <pod-name> -c php-fpm -- nc -zv postgres 5432

# 3. Check database pod
kubectl get pods -l app=postgres

# 4. Restart database
kubectl delete pod <postgres-pod>
```

### High Memory - Quick Fix

```bash
# 1. Clear caches
kubectl exec -it <pod-name> -c php-fpm -- php artisan cache:clear

# 2. Restart pod
kubectl rollout restart deployment/tavira-app

# 3. Scale up
kubectl scale deployment tavira-app --replicas=3
```

### Slow Response - Quick Fix

```bash
# 1. Clear caches
kubectl exec -it <pod-name> -c php-fpm -- php artisan cache:clear

# 2. Regenerate caches
kubectl exec -it <pod-name> -c php-fpm -- php artisan config:cache
kubectl exec -it <pod-name> -c php-fpm -- php artisan route:cache

# 3. Restart pod
kubectl rollout restart deployment/tavira-app
```

### Deployment Stuck - Quick Fix

```bash
# 1. Check status
kubectl rollout status deployment/tavira-app

# 2. Increase timeout
kubectl rollout status deployment/tavira-app --timeout=10m

# 3. Rollback if needed
kubectl rollout undo deployment/tavira-app

# 4. Check logs
kubectl logs <pod-name> -c php-fpm
```

---

## 📞 When to Escalate

Escalate to senior DevOps engineer if:

- [ ] Issue persists after trying all quick fixes
- [ ] Multiple pods affected
- [ ] Database corruption suspected
- [ ] Security breach suspected
- [ ] Data loss risk
- [ ] Production outage > 15 minutes
- [ ] Unknown error in logs
- [ ] Cluster-level issues

---

## 📚 Additional Resources

- [Kubernetes Troubleshooting](https://kubernetes.io/docs/tasks/debug-application-cluster/)
- [Docker Troubleshooting](https://docs.docker.com/config/containers/logging/)
- [Laravel Debugging](https://laravel.com/docs/debugging)
- [PostgreSQL Troubleshooting](./TROUBLESHOOTING-POSTGRESQL.md)

---

**Maintained by:** DevOps Team  
**Last Updated:** 2025-01-15  
**Version:** 1.0.0
