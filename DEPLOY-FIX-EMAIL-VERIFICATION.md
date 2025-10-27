# Fix para Error 403 Invalid Signature en Verificación de Emails

## Problema Identificado

El error 403 "Invalid signature" en la verificación de emails se debe a que nginx estaba sobrescribiendo los headers X-Forwarded-* que envía Traefik (el ingress controller).

### Causa raíz:
- Traefik maneja HTTPS y envía `X-Forwarded-Proto: https`
- nginx recibe estos headers pero los sobrescribía con sus propios valores
- Como nginx escucha en puerto 80 (HTTP), usaba `$scheme = http`
- Laravel generaba URLs firmadas con `https://` pero las validaba con `http://`
- **Las firmas no coincidían** ❌

## Solución Implementada

Se modificó la configuración de nginx para **preservar** los headers que recibe de Traefik usando la directiva `map` de nginx.

### Archivo modificado:
- `k8s/deployed/nginx-config.yaml`

### Cambios realizados:

1. **Agregadas directivas `map`** al inicio de la configuración (antes del bloque `server`):
   ```nginx
   map $http_x_forwarded_proto $forwarded_proto {
       default $http_x_forwarded_proto;
       "" $scheme;
   }
   # ... (similar para host, port, for)
   ```

2. **Actualizado el bloque PHP-FPM** para usar las variables mapeadas:
   ```nginx
   fastcgi_param HTTP_X_FORWARDED_HOST $forwarded_host;
   fastcgi_param HTTP_X_FORWARDED_PROTO $forwarded_proto;
   fastcgi_param HTTP_X_FORWARDED_FOR $forwarded_for;
   fastcgi_param HTTP_X_FORWARDED_PORT $forwarded_port;
   ```

## Pasos para Aplicar el Fix

### 1. Aplicar el ConfigMap actualizado

```bash
# Aplicar el nuevo ConfigMap de nginx
kubectl apply -f k8s/deployed/nginx-config.yaml
```

### 2. Verificar que el ConfigMap se actualizó

```bash
# Ver los cambios en el ConfigMap
kubectl describe configmap tavira-nginx-config
```

### 3. Reiniciar los pods para que carguen la nueva configuración

```bash
# Opción 1: Reinicio rolling (recomendado)
kubectl rollout restart deployment tavira-app

# Opción 2: Reinicio forzado de todos los pods
kubectl delete pods -l app=tavira
```

### 4. Verificar que los pods están corriendo

```bash
# Ver el estado de los pods
kubectl get pods -l app=tavira

# Ver logs de nginx para verificar que inició correctamente
kubectl logs -l app=tavira -c nginx --tail=50
```

### 5. Probar la verificación de emails

1. Registrar un nuevo usuario en https://tavira.com.co
2. Verificar que el correo de verificación llegue correctamente
3. Hacer clic en el enlace de verificación
4. **Debe redirigir al dashboard sin error 403** ✅

## Verificación de que el fix funciona

Para verificar que nginx está pasando los headers correctamente, puedes ejecutar:

```bash
# Ver los logs de nginx en tiempo real
kubectl logs -f -l app=tavira -c nginx

# Luego, hacer clic en un enlace de verificación y verificar que:
# - No aparezca error 403
# - Los logs muestren una petición exitosa a /verify-email/...
```

## Reversión (si es necesario)

Si algo sale mal, puedes revertir a la configuración anterior:

```bash
# Ver la historia del ConfigMap
kubectl rollout history configmap tavira-nginx-config

# Restaurar del backup en git
git checkout HEAD~1 -- k8s/deployed/nginx-config.yaml
kubectl apply -f k8s/deployed/nginx-config.yaml
kubectl rollout restart deployment tavira-app
```

## Notas adicionales

- **No es necesario cambiar el APP_KEY**: El problema no era el APP_KEY sino los headers del proxy
- **Los correos anteriores seguirán sin funcionar**: Solo los nuevos correos de verificación funcionarán
- **Afecta a todos los dominios**: Este fix funciona tanto para el dominio central (tavira.com.co) como para los subdominios de tenants (*.tavira.com.co)
- **Compatible con desarrollo local**: La configuración tiene fallback a los valores de nginx si no hay headers de proxy

## Testing

Después de aplicar el fix, prueba los siguientes escenarios:

- [ ] Registro nuevo en dominio central (tavira.com.co)
- [ ] Registro nuevo en dominio de tenant (ej: torresdevillacampestre.tavira.com.co)
- [ ] Verificación de email en ambos dominios
- [ ] Password reset (usa el mismo mecanismo de firmas)
- [ ] Invitaciones de usuarios (si usan URLs firmadas)

## Commit y Deploy

```bash
# Agregar los cambios
git add k8s/deployed/nginx-config.yaml DEPLOY-FIX-EMAIL-VERIFICATION.md

# Crear commit
git commit -m "fix: Preserve proxy headers in nginx for signed URL validation

- Add nginx map directives to preserve X-Forwarded-* headers from Traefik
- Fix 403 Invalid signature error in email verification
- Critical fix for multitenancy signed URL validation in Kubernetes with HTTPS"

# Push a producción
git push origin main
```
