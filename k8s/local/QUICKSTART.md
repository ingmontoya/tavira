# Tavira Local K8s - Quick Start Guide

## ⚡ 5-Minute Setup

### 1. Prerequisites

Ensure you have **OrbStack** running with Kubernetes enabled:

```bash
# Check if kubectl works
kubectl cluster-info
```

### 2. Deploy

```bash
cd k8s/local
./deploy.sh
```

Wait ~2-3 minutes for all pods to be ready.

### 3. Setup Database

```bash
# Run migrations
kubectl exec -n local deployment/tavira-app-local -c php-fpm -- php artisan migrate --force

# Run seeders (optional)
kubectl exec -n local deployment/tavira-app-local -c php-fpm -- php artisan db:seed
```

### 4. Access

Open your browser: **[http://localhost:30080](http://localhost:30080)**

## 🎯 Common Commands

```bash
# View status
kubectl get pods -n local

# View logs
kubectl logs -f deployment/tavira-app-local -n local -c php-fpm

# Shell into container
kubectl exec -it deployment/tavira-app-local -n local -c php-fpm -- sh

# Restart app
./deploy.sh --restart

# Destroy everything
./deploy.sh --destroy
```

## 🐛 Problems?

```bash
# Check what's wrong
kubectl get pods -n local
kubectl describe pod <pod-name> -n local
kubectl logs <pod-name> -n local

# Nuclear option: reset everything
./deploy.sh --destroy
./deploy.sh
```

## 📚 More Info

See [README.md](./README.md) for detailed documentation.

---

**That's it! You're ready to develop! 🚀**
