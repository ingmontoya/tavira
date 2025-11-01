# Guía de Despliegue - Ambientes Producción y Staging

Este documento describe la configuración y el proceso de despliegue para los ambientes de **Producción** y **Staging** de Tavira.

## 📋 Tabla de Contenidos

- [Arquitectura General](#arquitectura-general)
- [Ambientes](#ambientes)
- [Comparación de Recursos](#comparación-de-recursos)
- [Despliegue Automático (CI/CD)](#despliegue-automático-cicd)
- [Despliegue Manual](#despliegue-manual)
- [Gestión de Secrets](#gestión-de-secrets)
- [Monitoreo y Troubleshooting](#monitoreo-y-troubleshooting)

---

## 🏗️ Arquitectura General

Tavira se despliega en Kubernetes con la siguiente arquitectura:

```
┌─────────────────────────────────────────────────────────┐
│                    Traefik Ingress                      │
│            (SSL Termination + Load Balancing)           │
└─────────────────────┬───────────────────────────────────┘
                      │
          ┌───────────┴────────────┐
          │                        │
    ┌─────▼─────┐          ┌──────▼──────┐
    │Production │          │   Staging   │
    │ Namespace │          │  Namespace  │
    └─────┬─────┘          └──────┬──────┘
          │                       │
    ┌─────▼──────────────┐  ┌─────▼──────────────┐
    │ PHP-FPM + Nginx    │  │ PHP-FPM + Nginx    │
    │ (2 replicas)       │  │ (1 replica)        │
    ├────────────────────┤  ├────────────────────┤
    │ Queue Worker       │  │ Queue Worker       │
    │ (2 replicas)       │  │ (1 replica)        │
    ├────────────────────┤  ├────────────────────┤
    │ PostgreSQL (1)     │  │ PostgreSQL (1)     │
    ├────────────────────┤  ├────────────────────┤
    │ Redis (1)          │  │ Redis (1)          │
    └────────────────────┘  └────────────────────┘
```

## 🌍 Ambientes

### Producción

- **URL**: `https://tavira.com.co` + `*.tavira.com.co`
- **Rama Git**: `main`
- **Directorio**: `k8s/deployed/`
- **Workflow**: `.github/workflows/deploy.yml`
- **Propósito**: Ambiente de producción para clientes

### Staging

- **URL**: `https://staging.tavira.com.co` + `*.staging.tavira.com.co`
- **Rama Git**: `develop`
- **Directorio**: `k8s/staging/`
- **Workflow**: `.github/workflows/deploy-staging.yml`
- **Propósito**: Pruebas y validación antes de producción

---

## 📊 Comparación de Recursos

### Replicas

| Componente | Producción | Staging |
|------------|-----------|---------|
| **App (PHP-FPM + Nginx)** | 2 | 1 |
| **Queue Worker** | 2 | 1 |
| **PostgreSQL** | 1 | 1 |
| **Redis** | 1 | 1 |

**Reducción en Staging**: ~55% menos recursos

---

## 🚀 Despliegue Automático (CI/CD)

### Producción (main branch)

```bash
git push origin main
```

### Staging (develop branch)

```bash
git push origin develop
```

---

## 🛠️ Despliegue Manual

### Staging

```bash
./k8s/staging/deploy.sh all
```

Ver más detalles en `k8s/staging/README.md`

---

**Última actualización**: 2025-10-31
