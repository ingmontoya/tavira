# Tavira - Local Kubernetes Deployment

This directory contains Kubernetes manifests for deploying Tavira locally using OrbStack, Minikube, Docker Desktop, or any local Kubernetes cluster.

## 📋 Prerequisites

1. **Kubernetes Cluster**
   - [OrbStack](https://orbstack.dev/) (Recommended for macOS) - Includes Docker & Kubernetes
   - [Minikube](https://minikube.sigs.k8s.io/)
   - [Docker Desktop](https://www.docker.com/products/docker-desktop) with Kubernetes enabled
   - Any other local Kubernetes distribution

2. **kubectl CLI**
   ```bash
   # Check if kubectl is installed
   kubectl version --client

   # If not installed:
   # macOS (with Homebrew)
   brew install kubectl

   # Or download from: https://kubernetes.io/docs/tasks/tools/
   ```

3. **Verify Kubernetes is Running**
   ```bash
   kubectl cluster-info
   ```

## 🚀 Quick Start

### Option 1: Automated Deployment (Recommended)

```bash
# Navigate to the k8s/local directory
cd k8s/local

# Deploy everything
./deploy.sh

# Wait for deployment to complete
# The app will be available at: http://localhost:30080
```

### Option 2: Manual Deployment

```bash
# 1. Create namespace
kubectl apply -f namespace.yaml

# 2. Create persistent volumes
kubectl apply -f pvcs.yaml

# 3. Create config maps
kubectl apply -f postgres-config.yaml
kubectl apply -f nginx-config.yaml

# 4. Create secrets
kubectl apply -f laravel-env-secret.yaml

# 5. Deploy database and cache
kubectl apply -f postgres-deployment.yaml
kubectl apply -f postgres-service.yaml
kubectl apply -f redis-deployment.yaml
kubectl apply -f redis-service.yaml

# 6. Wait for database to be ready
kubectl wait --for=condition=ready pod -l app=postgres-local -n local --timeout=120s

# 7. Deploy application
kubectl apply -f deployment.yaml
kubectl apply -f service.yaml

# 8. Wait for app to be ready
kubectl wait --for=condition=ready pod -l app=tavira-local -n local --timeout=180s
```

## 🗄️ Database Setup

After deployment, run migrations and seeders:

```bash
# Run migrations
kubectl exec -n local deployment/tavira-app-local -c php-fpm -- php artisan migrate --force

# Run seeders (optional)
kubectl exec -n local deployment/tavira-app-local -c php-fpm -- php artisan db:seed

# Run specific seeder
kubectl exec -n local deployment/tavira-app-local -c php-fpm -- php artisan db:seed --class=ProductionTenantFeaturesSeeder
```

## 🔧 Useful Commands

### View Resources

```bash
# View all pods
kubectl get pods -n local

# View all services
kubectl get svc -n local

# View all resources
kubectl get all -n local

# Describe a pod
kubectl describe pod <pod-name> -n local
```

### View Logs

```bash
# Application logs (PHP-FPM)
kubectl logs -f deployment/tavira-app-local -n local -c php-fpm

# Nginx logs
kubectl logs -f deployment/tavira-app-local -n local -c nginx

# PostgreSQL logs
kubectl logs -f deployment/postgres-local -n local

# Redis logs
kubectl logs -f deployment/redis-local -n local
```

### Execute Commands

```bash
# Shell into application container
kubectl exec -it deployment/tavira-app-local -n local -c php-fpm -- sh

# Run artisan commands
kubectl exec -n local deployment/tavira-app-local -c php-fpm -- php artisan <command>

# Connect to PostgreSQL
kubectl exec -it deployment/postgres-local -n local -- psql -U tavira_user_local -d tavira_local

# Connect to Redis
kubectl exec -it deployment/redis-local -n local -- redis-cli
```

### Restart Deployment

```bash
# Using deploy script
./deploy.sh --restart

# Or manually
kubectl rollout restart deployment/tavira-app-local -n local
kubectl rollout status deployment/tavira-app-local -n local
```

### Destroy Everything

```bash
# Using deploy script
./deploy.sh --destroy

# Or manually
kubectl delete namespace local
```

## 🌐 Accessing the Application

Once deployed, the application is available at:

**URL:** [http://localhost:30080](http://localhost:30080)

The service uses a `NodePort` of `30080` to expose the application on your local machine.

## 🔐 Default Credentials

### Database
- **Database:** `tavira_local`
- **Username:** `tavira_user_local`
- **Password:** `tavira_local_password`
- **Host:** `postgres-local` (internal) or `localhost:5432` (via port-forward)

### Redis
- **Host:** `redis-local` (internal) or `localhost:6379` (via port-forward)
- **Port:** `6379`
- **Password:** None (default local setup)

### Laravel
- **APP_KEY:** Dummy key included (replace with real key for actual use)
- **APP_ENV:** `local`
- **APP_DEBUG:** `true`

## 🔌 Port Forwarding (Alternative Access)

If you prefer to access services on standard ports:

```bash
# Forward app to port 8000
kubectl port-forward -n local deployment/tavira-app-local 8000:80

# Forward PostgreSQL to port 5432
kubectl port-forward -n local deployment/postgres-local 5432:5432

# Forward Redis to port 6379
kubectl port-forward -n local deployment/redis-local 6379:6379
```

Then access the app at [http://localhost:8000](http://localhost:8000)

## 📁 Architecture

The deployment consists of:

### Deployments
- **tavira-app-local**: Main application (PHP-FPM + Nginx sidecar)
- **postgres-local**: PostgreSQL 16 database
- **redis-local**: Redis 7 cache/session store

### Services
- **tavira-service-local**: NodePort service (port 30080)
- **postgres-local**: ClusterIP service (port 5432)
- **redis-local**: ClusterIP service (port 6379)

### Storage
- **postgres-pvc-local**: 2Gi persistent storage for database
- **tavira-storage-pvc-local**: 2Gi persistent storage for app files

### ConfigMaps
- **tavira-nginx-config-local**: Nginx configuration
- **postgres-config-local**: PostgreSQL configuration

### Secrets
- **laravel-env-local**: Laravel environment variables

## 🐛 Troubleshooting

### Pod not starting

```bash
# Check pod status
kubectl get pods -n local

# Describe the pod to see events
kubectl describe pod <pod-name> -n local

# Check logs
kubectl logs <pod-name> -n local -c <container-name>
```

### Database connection issues

```bash
# Check if PostgreSQL is ready
kubectl get pods -n local -l app=postgres-local

# Check PostgreSQL logs
kubectl logs -f deployment/postgres-local -n local

# Test connection from app container
kubectl exec -it deployment/tavira-app-local -n local -c php-fpm -- sh
# Then inside container:
# php artisan tinker
# DB::connection()->getPdo();
```

### Storage issues

```bash
# Check PVCs
kubectl get pvc -n local

# Check PV bindings
kubectl get pv
```

### Reset everything

```bash
# Destroy and redeploy
./deploy.sh --destroy
./deploy.sh
```

## 🔄 Development Workflow

### Update application image

If you rebuild the Docker image:

```bash
# Restart deployment to pull latest image
./deploy.sh --restart

# Or manually
kubectl rollout restart deployment/tavira-app-local -n local
```

### Update configuration

```bash
# Update ConfigMap or Secret
kubectl apply -f <file>.yaml

# Restart deployment to pick up changes
./deploy.sh --restart
```

## 🎯 Next Steps

1. **Access the application**: [http://localhost:30080](http://localhost:30080)
2. **Run migrations**: Set up the database schema
3. **Create test data**: Use seeders to populate the database
4. **Configure tenants**: Set up your first residential complex

## 📚 Additional Resources

- [OrbStack Documentation](https://docs.orbstack.dev/)
- [Kubernetes Documentation](https://kubernetes.io/docs/)
- [Laravel Documentation](https://laravel.com/docs)
- [Tavira Project README](../../README.md)

## 💡 Tips

- **Resource Usage**: This configuration uses minimal resources suitable for local development
- **Data Persistence**: Database data persists across pod restarts thanks to PVCs
- **Hot Reload**: For faster development, consider mounting your local code directory instead of using the Docker image
- **Logs**: Use `kubectl logs -f` to stream logs in real-time during development

---

**Happy Development! 🚀**
