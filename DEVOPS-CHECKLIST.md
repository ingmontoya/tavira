# ✅ DevOps Deployment Checklist - Tavira

**Purpose:** Ensure all systems are properly configured before and after deployment  
**Last Updated:** 2025-01-15  
**Version:** 1.0.0

---

## 📋 Pre-Deployment Checklist

### 1. Code & Repository

- [ ] All code committed to `main` branch
- [ ] No uncommitted changes in working directory
- [ ] Latest code pulled from remote
- [ ] All tests passing locally
- [ ] Code review completed
- [ ] No merge conflicts
- [ ] Changelog updated
- [ ] Version number updated (if applicable)

### 2. Database & Migrations

- [ ] All migrations created and tested locally
- [ ] Migration files follow naming convention
- [ ] Rollback migrations tested
- [ ] Tenant migrations prepared
- [ ] Database backup created
- [ ] Migration order verified
- [ ] No breaking changes to existing data
- [ ] Data seeding scripts updated (if needed)

### 3. Environment Configuration

- [ ] `.env` file reviewed
- [ ] All required environment variables defined
- [ ] Secrets created in Kubernetes
- [ ] Database credentials verified
- [ ] API keys and tokens valid
- [ ] Mail configuration tested
- [ ] Payment gateway credentials valid
- [ ] Sentry DSN configured
- [ ] CORS settings correct
- [ ] Trusted proxies configured

### 4. Docker Images

- [ ] Dockerfile reviewed for security
- [ ] `.dockerignore` up to date
- [ ] Image builds successfully locally
- [ ] Image size acceptable (~250-350MB)
- [ ] No hardcoded secrets in image
- [ ] All dependencies included
- [ ] PHP extensions verified
- [ ] OPcache configuration correct
- [ ] Image tagged with version
- [ ] Image pushed to Docker Hub

### 5. Kubernetes Configuration

- [ ] `deployment-optimized.yaml` reviewed
- [ ] Resource limits appropriate
- [ ] Health checks configured
- [ ] Probes have correct timeouts
- [ ] Init containers working
- [ ] Volume mounts correct
- [ ] Security context applied
- [ ] Service configuration correct
- [ ] Ingress rules correct
- [ ] ConfigMap updated
- [ ] Secrets created

### 6. Infrastructure

- [ ] Kubernetes cluster accessible
- [ ] kubectl configured correctly
- [ ] Cluster has sufficient resources
- [ ] Storage class available
- [ ] Ingress controller installed
- [ ] Cert-manager installed
- [ ] DNS records updated
- [ ] SSL certificate ready
- [ ] Network policies reviewed
- [ ] Firewall rules configured

### 7. Monitoring & Logging

- [ ] Sentry project configured
- [ ] Log aggregation setup
- [ ] Monitoring dashboards ready
- [ ] Alert rules configured
- [ ] Backup strategy verified
- [ ] Disaster recovery plan reviewed
- [ ] On-call rotation established
- [ ] Escalation procedures documented

### 8. Security

- [ ] Secrets not in code
- [ ] No default credentials
- [ ] HTTPS enabled
- [ ] Security headers configured
- [ ] CORS properly restricted
- [ ] Rate limiting configured
- [ ] Input validation enabled
- [ ] SQL injection prevention verified
- [ ] XSS protection enabled
- [ ] CSRF tokens configured

### 9. Performance

- [ ] OPcache enabled
- [ ] Database indexes created
- [ ] Query optimization done
- [ ] Caching strategy implemented
- [ ] Asset compression enabled
- [ ] CDN configured (if applicable)
- [ ] Load testing completed
- [ ] Performance baseline established

### 10. Documentation

- [ ] Deployment guide updated
- [ ] Runbook created
- [ ] Troubleshooting guide updated
- [ ] Architecture diagram current
- [ ] API documentation updated
- [ ] Database schema documented
- [ ] Environment variables documented
- [ ] Known issues documented

### 11. Communication

- [ ] Stakeholders notified
- [ ] Maintenance window scheduled
- [ ] User communication prepared
- [ ] Support team briefed
- [ ] Rollback plan communicated
- [ ] Escalation contacts confirmed

### 12. Final Verification

- [ ] All checklist items completed
- [ ] No blocking issues remaining
- [ ] Approval from team lead obtained
- [ ] Deployment window confirmed
- [ ] Team members available
- [ ] Rollback plan ready

---

## 🚀 Deployment Execution Checklist

### Pre-Deployment (30 minutes before)

- [ ] Notify team of deployment start
- [ ] Verify no critical issues in production
- [ ] Check system resources
- [ ] Verify database backup completed
- [ ] Confirm all team members ready
- [ ] Open communication channel (Slack/Teams)
- [ ] Start deployment monitoring

### Deployment Steps

#### Step 1: Build & Push Image
```bash
# Verify image builds
docker build -t ingmontoyav/tavira-app:v20250115-abc123 .

# Verify image pushes
docker push ingmontoyav/tavira-app:v20250115-abc123

- [ ] Image builds successfully
- [ ] Image pushes to Docker Hub
- [ ] Image size acceptable
- [ ] No build warnings
```

#### Step 2: Update Kubernetes
```bash
# Update deployment
./scripts/deploy.sh v20250115-abc123

- [ ] Deployment command executes
- [ ] Rollout starts
- [ ] Old pods terminating
- [ ] New pods starting
```

#### Step 3: Monitor Rollout
```bash
# Watch rollout progress
kubectl rollout status deployment/tavira-app

- [ ] Rollout progresses normally
- [ ] No pod crashes
- [ ] Health checks passing
- [ ] Readiness probes passing
```

#### Step 4: Post-Deployment Tasks
```bash
# Run migrations and cache
kubectl exec -it $POD -c php-fpm -- php artisan migrate --force
kubectl exec -it $POD -c php-fpm -- php artisan config:cache

- [ ] Migrations complete successfully
- [ ] Cache generation successful
- [ ] No database errors
- [ ] No permission errors
```

#### Step 5: Verification
```bash
# Verify application
kubectl logs -f deployment/tavira-app -c php-fpm

- [ ] Application logs show no errors
- [ ] Database connection successful
- [ ] Cache generation complete
- [ ] All services initialized
```

### Post-Deployment (Immediate)

- [ ] Check application health endpoint
- [ ] Verify critical user flows
- [ ] Check error logs (Sentry)
- [ ] Monitor resource usage
- [ ] Verify database connectivity
- [ ] Check external service integrations
- [ ] Test payment gateway (if applicable)
- [ ] Verify email sending
- [ ] Check file uploads
- [ ] Test authentication

### Post-Deployment (1 hour)

- [ ] Monitor error rates
- [ ] Check performance metrics
- [ ] Verify no data corruption
- [ ] Check user reports
- [ ] Review logs for warnings
- [ ] Verify scheduled jobs running
- [ ] Check queue processing
- [ ] Verify cache hit rates
- [ ] Monitor database performance
- [ ] Check API response times

### Post-Deployment (24 hours)

- [ ] Review all logs for errors
- [ ] Check performance trends
- [ ] Verify no memory leaks
- [ ] Check disk usage
- [ ] Review error tracking (Sentry)
- [ ] Verify backup completion
- [ ] Check user feedback
- [ ] Document any issues
- [ ] Update runbook if needed
- [ ] Celebrate successful deployment! 🎉

---

## 🔄 Rollback Checklist

### Decision to Rollback

- [ ] Critical issue identified
- [ ] Issue cannot be fixed quickly
- [ ] Rollback approved by team lead
- [ ] Rollback plan reviewed
- [ ] Team notified

### Rollback Execution

```bash
# Rollback deployment
kubectl rollout undo deployment/tavira-app

- [ ] Rollback command executes
- [ ] Old pods restarting
- [ ] New pods terminating
- [ ] Health checks passing
```

### Post-Rollback Verification

- [ ] Application responding normally
- [ ] Database accessible
- [ ] No data corruption
- [ ] Users can access application
- [ ] Critical functions working
- [ ] Error rates normal
- [ ] Performance acceptable

### Post-Rollback Actions

- [ ] Notify stakeholders
- [ ] Document issue
- [ ] Schedule post-mortem
- [ ] Create bug report
- [ ] Update runbook
- [ ] Communicate timeline for fix

---

## 🔍 Health Check Verification

### Application Health

```bash
# Check pod status
kubectl get pods -l app=tavira

- [ ] All pods running
- [ ] No pods in CrashLoopBackOff
- [ ] No pods pending
- [ ] Ready count matches desired count
```

### Container Health

```bash
# Check container logs
kubectl logs deployment/tavira-app -c php-fpm

- [ ] No error messages
- [ ] No exception traces
- [ ] Application initialized
- [ ] Database connected
```

### Service Health

```bash
# Check service
kubectl get service tavira-service

- [ ] Service has endpoints
- [ ] Endpoints are ready
- [ ] Port mapping correct
```

### Ingress Health

```bash
# Check ingress
kubectl get ingress tavira-ingress

- [ ] Ingress has IP/hostname
- [ ] TLS certificate valid
- [ ] Rules configured correctly
```

### Database Health

```bash
# Check database connectivity
kubectl exec -it $POD -c php-fpm -- php artisan db:show

- [ ] Database connection successful
- [ ] All tables present
- [ ] No connection errors
```

### Cache Health

```bash
# Check cache
kubectl exec -it $POD -c php-fpm -- php artisan cache:clear

- [ ] Cache operations successful
- [ ] No cache errors
- [ ] Cache directory writable
```

---

## 📊 Performance Verification

### Response Times

- [ ] Average response time < 200ms
- [ ] P95 response time < 500ms
- [ ] P99 response time < 1000ms
- [ ] No timeout errors

### Resource Usage

- [ ] CPU usage < 80% of limit
- [ ] Memory usage < 80% of limit
- [ ] Disk usage < 80% of available
- [ ] Network bandwidth acceptable

### Database Performance

- [ ] Query response time acceptable
- [ ] No slow queries
- [ ] Connection pool healthy
- [ ] No deadlocks

### Cache Performance

- [ ] OPcache hit rate > 90%
- [ ] Redis connection healthy
- [ ] Cache eviction rate acceptable
- [ ] No cache corruption

---

## 🔐 Security Verification

### Secrets & Credentials

- [ ] No secrets in logs
- [ ] No secrets in error messages
- [ ] Secrets properly mounted
- [ ] Credentials not exposed
- [ ] API keys valid

### HTTPS & TLS

- [ ] HTTPS working
- [ ] Certificate valid
- [ ] No SSL errors
- [ ] Redirect to HTTPS working
- [ ] Security headers present

### Access Control

- [ ] Authentication working
- [ ] Authorization enforced
- [ ] CORS properly configured
- [ ] Rate limiting active
- [ ] No unauthorized access

### Data Protection

- [ ] Sensitive data encrypted
- [ ] Passwords hashed
- [ ] No data leaks
- [ ] Backup encrypted
- [ ] PII properly handled

---

## 📝 Documentation Checklist

### Update Documentation

- [ ] Deployment guide updated
- [ ] Runbook updated
- [ ] Troubleshooting guide updated
- [ ] Architecture diagram updated
- [ ] API documentation updated
- [ ] Known issues documented
- [ ] Changelog updated
- [ ] Release notes created

### Create Records

- [ ] Deployment log created
- [ ] Issues documented
- [ ] Decisions recorded
- [ ] Timeline documented
- [ ] Lessons learned captured

---

## 🎯 Sign-Off Checklist

### Technical Sign-Off

- [ ] All tests passing
- [ ] Code review approved
- [ ] Deployment successful
- [ ] Health checks passing
- [ ] Performance acceptable
- [ ] Security verified
- [ ] Documentation updated

### Business Sign-Off

- [ ] Features working as expected
- [ ] No critical bugs
- [ ] User acceptance confirmed
- [ ] Stakeholders notified
- [ ] Go-live approved

### Final Sign-Off

- [ ] Deployment completed successfully
- [ ] All checklist items verified
- [ ] Team debriefing completed
- [ ] Post-mortem scheduled (if needed)
- [ ] Deployment marked as complete

---

## 📞 Emergency Contacts

| Role | Name | Phone | Email |
|------|------|-------|-------|
| DevOps Lead | [Name] | [Phone] | [Email] |
| On-Call Engineer | [Name] | [Phone] | [Email] |
| Database Admin | [Name] | [Phone] | [Email] |
| Security Lead | [Name] | [Phone] | [Email] |
| Product Manager | [Name] | [Phone] | [Email] |

---

## 📚 Quick Reference Links

- [Deployment Guide](./DEPLOYMENT.md)
- [Deployment Guide (Optimized)](./DEPLOYMENT-GUIDE.md)
- [DevOps Summary](./DEVOPS-SUMMARY.md)
- [Troubleshooting Guide](./TROUBLESHOOTING-POSTGRESQL.md)
- [Kubernetes Docs](https://kubernetes.io/docs/)
- [Docker Docs](https://docs.docker.com/)
- [Laravel Docs](https://laravel.com/docs/)

---

## 🔄 Deployment History

| Date | Version | Status | Notes |
|------|---------|--------|-------|
| 2025-01-15 | v1.0.0 | ✅ Success | Initial deployment |
| | | | |
| | | | |

---

**Prepared by:** DevOps Team  
**Last Updated:** 2025-01-15  
**Next Review:** 2025-02-15

---

## Notes

Use this checklist for every deployment to ensure consistency and reduce errors. Update it as you discover new items or procedures.

**Remember:** A successful deployment is one where nothing goes wrong. Take your time and follow the checklist!
