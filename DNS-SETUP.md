# Configuración DNS en GoDaddy para Tavira

## Información del Servidor

- **Dominio**: tavira.com.co
- **IP Pública**: 147.93.182.90
- **Servicios**: HTTP (80) y HTTPS (443)
- **Ingress Controller**: Traefik

---

## Paso 1: Acceder a GoDaddy DNS Management

1. Ve a [https://dcc.godaddy.com/manage/tavira.com.co/dns](https://dcc.godaddy.com/manage/tavira.com.co/dns)
2. Inicia sesión con tu cuenta de GoDaddy
3. Busca "tavira.com.co" en tus dominios
4. Click en "DNS" o "Manage DNS"

---

## Paso 2: Configurar Registros DNS

### A) Registro A para el dominio raíz

Agrega o edita el registro A principal:

```
Type:  A
Name:  @
Value: 147.93.182.90
TTL:   600 (10 minutos) o 3600 (1 hora)
```

**Explicación**:
- `@` representa el dominio raíz (tavira.com.co)
- Apunta directamente a tu servidor Kubernetes

### B) Registro A para www (Opcional pero recomendado)

```
Type:  A
Name:  www
Value: 147.93.182.90
TTL:   600
```

**Alternativa con CNAME**:
```
Type:  CNAME
Name:  www
Value: tavira.com.co
TTL:   3600
```

### C) Wildcard para subdominios de tenants (Importante para multitenancy)

```
Type:  A
Name:  *
Value: 147.93.182.90
TTL:   3600
```

**Explicación**:
- Permite que cualquier subdominio (conjunto-torres.tavira.com.co, edificio-central.tavira.com.co) apunte al servidor
- Necesario para la arquitectura multitenant de Tavira

---

## Paso 3: Verificar Configuración Actual

Antes de hacer cambios, verifica qué registros DNS tienes actualmente:

```bash
# En tu terminal local
nslookup tavira.com.co
dig tavira.com.co

# Verificar con diferentes servidores DNS
dig @8.8.8.8 tavira.com.co
dig @1.1.1.1 tavira.com.co
```

---

## Paso 4: Eliminar Registros Conflictivos

**IMPORTANTE**: Elimina estos registros si existen:

- ❌ Cualquier registro A que apunte a otra IP
- ❌ Registros CNAME para @ (no permitido)
- ❌ Registros de "Domain Forwarding" activos
- ❌ Registros de "Parking Page" de GoDaddy

---

## Configuración Final Recomendada

Tu zona DNS debería verse así en GoDaddy:

| Type | Name | Value | TTL |
|------|------|-------|-----|
| A | @ | 147.93.182.90 | 600 |
| A | www | 147.93.182.90 | 600 |
| A | * | 147.93.182.90 | 3600 |
| MX | @ | (tus registros de email) | 3600 |
| TXT | @ | (registros SPF, DKIM si aplica) | 3600 |

---

## Paso 5: Guardar y Esperar Propagación

1. **Guardar cambios** en GoDaddy
2. **Tiempo de propagación**: 5 minutos a 48 horas
   - GoDaddy: Usualmente 10-30 minutos
   - Global: Hasta 48 horas (raro)
3. **TTL bajo**: Durante la configuración inicial, usa TTL=600 (10 min) para cambios rápidos

---

## Verificación de DNS (Comandos)

### Verificar desde tu máquina local

```bash
# Verificar registro A
dig tavira.com.co +short
# Debería retornar: 147.93.182.90

# Verificar www
dig www.tavira.com.co +short
# Debería retornar: 147.93.182.90

# Verificar wildcard
dig cualquier-cosa.tavira.com.co +short
# Debería retornar: 147.93.182.90

# Verificar con nslookup
nslookup tavira.com.co
nslookup www.tavira.com.co

# Verificar propagación global
curl https://dns.google/resolve?name=tavira.com.co&type=A
```

### Herramientas Online

- [https://www.whatsmydns.net/#A/tavira.com.co](https://www.whatsmydns.net/#A/tavira.com.co)
- [https://dnschecker.org/#A/tavira.com.co](https://dnschecker.org/#A/tavira.com.co)
- [https://www.digwebinterface.com/](https://www.digwebinterface.com/)

---

## Paso 6: Probar Conectividad

Una vez que el DNS esté propagado (espera al menos 10 minutos):

```bash
# Probar HTTP (puerto 80)
curl -I http://tavira.com.co

# Probar HTTPS (puerto 443) - Puede fallar hasta que SSL esté configurado
curl -I https://tavira.com.co

# Probar con verbose
curl -v http://tavira.com.co

# Desde el navegador
# Abre: http://tavira.com.co
```

---

## Troubleshooting DNS

### Problema: DNS no resuelve después de 1 hora

**Soluciones**:

1. **Limpiar cache DNS local**:
```bash
# macOS
sudo dscacheutil -flushcache && sudo killall -HUP mDNSResponder

# Windows
ipconfig /flushdns

# Linux
sudo systemd-resolve --flush-caches
```

2. **Verificar en GoDaddy**:
   - Asegúrate de que los cambios se guardaron
   - Verifica que no hay "Domain Forwarding" activo
   - Desactiva "Parking Page" si está habilitado

3. **Probar con DNS público**:
```bash
# Google DNS
nslookup tavira.com.co 8.8.8.8

# Cloudflare DNS
nslookup tavira.com.co 1.1.1.1

# OpenDNS
nslookup tavira.com.co 208.67.222.222
```

### Problema: "Connection refused" después de DNS

**Soluciones**:

```bash
# 1. Verificar que el servidor está escuchando en puerto 80/443
curl http://147.93.182.90

# 2. Verificar Traefik en Kubernetes
kubectl get svc -n kube-system traefik

# 3. Verificar ingress
kubectl get ingress tavira-ingress

# 4. Ver logs de Traefik
kubectl logs -n kube-system -l app.kubernetes.io/name=traefik --tail=50
```

### Problema: ERR_SSL_PROTOCOL_ERROR

**Causa**: HTTPS no configurado todavía.

**Solución temporal**: Usa HTTP mientras configuramos SSL
```
http://tavira.com.co  ← Usar esto primero
```

---

## Configuración de Nameservers (Solo si es necesario)

Si quieres usar nameservers personalizados de GoDaddy:

**Nameservers de GoDaddy** (ya deberían estar configurados):
```
ns29.domaincontrol.com
ns30.domaincontrol.com
```

**No cambies los nameservers** a menos que sepas lo que haces.

---

## Siguiente Paso: Configurar SSL/HTTPS

Una vez que el DNS esté funcionando con HTTP:

1. Verificar cert-manager está instalado
2. Configurar ClusterIssuer de Let's Encrypt
3. Esperar que se genere el certificado automáticamente
4. Probar HTTPS

Esto lo configuraremos en el siguiente paso después de que HTTP funcione.

---

## Resumen de Configuración Rápida

**En GoDaddy DNS Manager**:

1. Agregar registro A: `@` → `147.93.182.90`
2. Agregar registro A: `www` → `147.93.182.90`
3. Agregar registro A: `*` → `147.93.182.90`
4. TTL: `600` (cambiar a 3600 después)
5. Guardar cambios

**Esperar 10-30 minutos**

**Verificar**:
```bash
dig tavira.com.co +short
curl -I http://tavira.com.co
```

**Si funciona HTTP**, proceder con SSL.

---

## Contacto y Soporte

Si tienes problemas con la configuración DNS:

1. Verifica los logs de Traefik en K8s
2. Verifica el Ingress está apuntando a la IP correcta
3. Usa herramientas de verificación DNS online
4. Espera más tiempo (a veces toma horas)

---

**Última actualización**: 2025-10-25
**IP del servidor**: 147.93.182.90
**Dominio**: tavira.com.co
