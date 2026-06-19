# Guía de Despliegue - nexusMK en cPanel

> **Proyecto:** MkController v3.0 → nexusMK  
> **Dominio:** `nexusmk.nexussolutionsyl.com`  
> **Servidor:** `159.198.46.227` (cPanel: `nexussolutionsyl.com:2083`)  
> **Usuario cPanel:** `nexuyl`  
> **Aplicación:** Node.js + Express + Frontend SPA

---

## 📋 Requisitos Previos

- Acceso a cPanel en [`https://nexussolutionsyl.com:2083`](https://nexussolutionsyl.com:2083)
- Credenciales de cPanel: `nexuyl` / contraseña proporcionada
- Acceso a **MySQL** desde cPanel (phpMyAdmin o MySQL Databases)
- **Node.js** habilitado en cPanel (Setup Node.js App)

---

## 🔧 Paso 1: Crear Subdominio

1. Inicia sesión en cPanel: [`https://nexussolutionsyl.com:2083`](https://nexussolutionsyl.com:2083)
2. Ve a **"Domains"** → **"Create A New Domain"**
3. Configura:
   - **Domain:** `nexusmk.nexussolutionsyl.com`
   - **Document Root:** `nexusmk.nexussolutionsyl.com` (se crea automáticamente)
   - **Create an addon domain:** ✅ Marcado
4. Haz clic en **"Submit"**

Esto creará la carpeta:  
`/home/nexuyl/public_html/nexusmk.nexussolutionsyl.com/`

---

## 🚀 Paso 2: Configurar Aplicación Node.js en cPanel

1. En cPanel, ve a **"Software"** → **"Setup Node.js App"**
2. Haz clic en **"Create Application"**
3. Configura los siguientes campos:

| Campo | Valor |
|-------|-------|
| **Node.js version** | Selecciona **20.x.x** o **18.x.x** (LTS recomendado) |
| **Application mode** | `Production` |
| **Application root** | `nexusmk.nexussolutionsyl.com` |
| **Application URL** | `https://nexusmk.nexussolutionsyl.com` |
| **Application startup file** | `start.js` |
| **Passenger log file** | `log.txt` (o dejar vacío) |
| **Environment variables** | Agregar: `NODE_ENV=production` |

4. Haz clic en **"Create"**

> ⚠️ **Nota importante:** cPanel asigna un puerto interno automáticamente. La aplicación usará `process.env.PORT` o el puerto por defecto 3000. El proxy inverso de cPanel se encarga de redirigir el tráfico desde el puerto 443 (HTTPS) al puerto de la app.

---

## 📂 Paso 3: Subir Archivos del Proyecto

### Opción A: File Manager de cPanel

1. En cPanel, ve a **"Files"** → **"File Manager"**
2. Navega a: `/home/nexuyl/public_html/nexusmk.nexussolutionsyl.com/`
3. Sube todos los archivos del proyecto **EXCLUYENDO**:
   - `node_modules/` (se instalarán después)
   - `.env` (se creará manualmente)
   - `package-lock.json` (opcional)
   - `ssh-connect.ps1`
   - `ssh-deploy.ps1`
   - `.idea/`

### Opción B: FTP (recomendado para archivos grandes)

Usa un cliente FTP como **FileZilla** con:
- **Host:** `nexussolutionsyl.com` o `159.198.46.227`
- **Usuario:** `nexuyl`
- **Contraseña:** (la de cPanel)
- **Puerto:** `21`
- **Directorio remoto:** `/public_html/nexusmk.nexussolutionsyl.com/`

Sube todo el contenido del proyecto (sin `node_modules/`).

---

## 📦 Paso 4: Instalar Dependencias npm

1. En cPanel, ve a **"Setup Node.js App"**
2. Localiza tu aplicación `nexusmk` y haz clic en el botón **"Run npm install"**
3. Espera a que se complete la instalación (puede tomar 1-2 minutos)

> Alternativamente, puedes conectarte por SSH (si está habilitado) y ejecutar:
> ```bash
> cd ~/public_html/nexusmk.nexussolutionsyl.com
> npm install --production
> ```

---

## 🗄️ Paso 5: Configurar Base de Datos MySQL (nexusMK)

### 5.1 Crear Base de Datos y Usuario

1. En cPanel, ve a **"Databases"** → **"MySQL Databases"**
2. **Create a new database:**
   - **Database name:** `nexuyl_nexusmk`
   - Haz clic en **"Create Database"**
3. **Create a new user:**
   - **Username:** `nexuyl_nexusmk_user`
   - **Password:** Genera una contraseña segura (ej: `MkC0ntr0ll3r#2024!`)
   - Haz clic en **"Create User"**
4. **Add User to Database:**
   - Selecciona el usuario y la base de datos
   - Marca **"ALL PRIVILEGES"**
   - Haz clic en **"Add"**

### 5.2 Importar Estructura de Tablas

1. En cPanel, ve a **"Databases"** → **"phpMyAdmin"**
2. Selecciona la base de datos `nexuyl_nexusmk`
3. Ve a la pestaña **"SQL"**
4. Ejecuta el siguiente SQL para crear las tablas:

```sql
-- Tabla de usuarios nexusMK
CREATE TABLE `usuarios_nexusmk` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `rol` enum('admin','operador') DEFAULT 'operador',
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar usuario admin por defecto (contraseña: admin123)
INSERT INTO `usuarios_nexusmk` (`username`, `password`, `nombre`, `email`, `rol`, `activo`)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'admin@nexussolutionsyl.com', 'admin', 1);

-- Tabla de dispositivos MikroTik
CREATE TABLE `dispositivos_mikrotik` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `tipo` enum('CHR','RouterBoard','Switch','Otros') DEFAULT 'RouterBoard',
  `puerto_api` int(11) DEFAULT 8728,
  `puerto_ssh` int(11) DEFAULT 22,
  `username` varchar(50) DEFAULT 'admin',
  `password` varchar(255) DEFAULT NULL,
  `version_routeros` varchar(20) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `ultima_conexion` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de interfaces WireGuard
CREATE TABLE `interfaces_wireguard` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dispositivo_id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `puerto` int(11) DEFAULT 13231,
  `direccion_ip` varchar(45) DEFAULT NULL,
  `clave_privada` text DEFAULT NULL,
  `clave_publica` varchar(255) DEFAULT NULL,
  `mtu` int(11) DEFAULT 1420,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `dispositivo_id` (`dispositivo_id`),
  CONSTRAINT `interfaces_wireguard_ibfk_1` FOREIGN KEY (`dispositivo_id`) REFERENCES `dispositivos_mikrotik` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de peers WireGuard
CREATE TABLE `peers_wireguard` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `interfaz_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `clave_publica` varchar(255) NOT NULL,
  `allowed_ips` varchar(255) DEFAULT '0.0.0.0/0',
  `endpoint` varchar(255) DEFAULT NULL,
  `puerto` int(11) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `interfaz_id` (`interfaz_id`),
  CONSTRAINT `peers_wireguard_ibfk_1` FOREIGN KEY (`interfaz_id`) REFERENCES `interfaces_wireguard` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de reglas de firewall
CREATE TABLE `reglas_firewall` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dispositivo_id` int(11) NOT NULL,
  `chain` varchar(50) DEFAULT 'forward',
  `action` varchar(50) DEFAULT 'accept',
  `protocolo` varchar(20) DEFAULT NULL,
  `puerto_destino` int(11) DEFAULT NULL,
  `src_address` varchar(45) DEFAULT NULL,
  `dst_address` varchar(45) DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `orden` int(11) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `dispositivo_id` (`dispositivo_id`),
  CONSTRAINT `reglas_firewall_ibfk_1` FOREIGN KEY (`dispositivo_id`) REFERENCES `dispositivos_mikrotik` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## ⚙️ Paso 6: Configurar Variables de Entorno

1. En cPanel, ve a **"Setup Node.js App"**
2. Localiza tu app y haz clic en el botón **"Edit"**
3. En la sección **"Environment variables"**, agrega:

| Variable | Valor |
|----------|-------|
| `NODE_ENV` | `production` |
| `PORT` | `3000` |
| `JWT_SECRET` | `[genera_un_token_seguro_aqui]` |
| `JWT_EXPIRES_IN` | `24h` |
| `CORS_ORIGIN` | `https://nexusmk.nexussolutionsyl.com` |
| `DB_MYSQL_HOST` | `localhost` |
| `DB_MYSQL_USER` | `nexuyl_nexusmk_user` |
| `DB_MYSQL_PASSWORD` | `[contraseña_generada_en_paso_5]` |
| `DB_MYSQL_DATABASE` | `nexuyl_nexusmk` |

4. Haz clic en **"Save"**

> Alternativamente, puedes crear un archivo `.env` en la raíz del proyecto con el mismo contenido.

---

## ▶️ Paso 7: Iniciar la Aplicación

1. En cPanel, ve a **"Setup Node.js App"**
2. Localiza tu app `nexusmk`
3. Si el estado muestra **"Stopped"**, haz clic en el botón **"Start"** (ícono de play ▶️)
4. Espera unos segundos y verifica que el estado cambie a **"Running"** ✅

---

## ✅ Paso 8: Verificar el Despliegue

### 8.1 Verificar Health Check

Visita: [`https://nexusmk.nexussolutionsyl.com/api/health`](https://nexusmk.nexussolutionsyl.com/api/health)

Deberías ver una respuesta JSON como:
```json
{
  "success": true,
  "message": "MkController API funcionando",
  "version": "3.0.0",
  "timestamp": "2026-06-19T..."
}
```

### 8.2 Verificar Frontend

Visita: [`https://nexusmk.nexussolutionsyl.com`](https://nexusmk.nexussolutionsyl.com)

Deberías ver la página de login de MkController.

### 8.3 Probar Login

- **SuperAdmin:** `admin` / `admin123`
- **Cliente Demo:** `demo` / `demo123`

### 8.4 Verificar nexusMK (MySQL)

Visita: [`https://nexusmk.nexussolutionsyl.com/api/nexusmk/health`](https://nexusmk.nexussolutionsyl.com/api/nexusmk/health)

Deberías ver:
```json
{
  "success": true,
  "message": "Conexión a MySQL establecida",
  "data": { "db": "nexuyl_nexusmk", "state": "connected" }
}
```

---

## 🔒 Paso 9: Seguridad Post-Despliegue

### 9.1 Cambiar contraseñas por defecto

Inicia sesión como `admin` y cambia la contraseña inmediatamente:
1. Ve a **Usuarios** en el panel
2. Edita el usuario `admin`
3. Cambia la contraseña a una segura

### 9.2 Generar JWT_SECRET seguro

Genera un secreto JWT seguro usando Node.js:
```bash
node -e "console.log(require('crypto').randomBytes(32).toString('hex'))"
```
Usa el resultado como valor de `JWT_SECRET` en las variables de entorno.

### 9.3 Configurar HTTPS

cPanel ya debe tener SSL instalado automáticamente (AutoSSL). Verifica:
1. Ve a **"Security"** → **"SSL/TLS Status"**
2. Asegúrate de que `nexusmk.nexussolutionsyl.com` tenga un certificado válido
3. Si no, haz clic en **"Run AutoSSL"**

---

## 🛠️ Solución de Problemas

### La app no inicia (Internal Server Error 500)

1. Revisa el log de la app en **"Setup Node.js App"** → haz clic en el nombre de la app → **"Logs"**
2. Verifica que todas las dependencias estén instaladas (`node_modules/`)
3. Verifica que `start.js` exista en la raíz del proyecto

### Error de conexión MySQL

1. Verifica que las credenciales en las variables de entorno sean correctas
2. Asegúrate de que el usuario MySQL tenga acceso a la base de datos
3. Verifica que el host sea `localhost` (no la IP del servidor)

### Error 404 en rutas del frontend

El SPA fallback está configurado en [`backend/app.js`](backend/app.js:105) para servir `index.html` en cualquier ruta que no sea API. Si no funciona, verifica que la línea:
```js
app.get('*', (req, res) => {
  res.sendFile(path.join(__dirname, '..', 'frontend', 'index.html'));
});
```
esté presente al final del archivo.

### Puerto en uso

Si el puerto 3000 está ocupado, cambia el `PORT` en las variables de entorno a otro (ej: 3001, 8080, etc.)

---

## 📁 Estructura del Proyecto en el Servidor

```
/home/nexuyl/public_html/nexusmk.nexussolutionsyl.com/
├── start.js                    # Punto de entrada
├── .env                        # Variables de entorno (NO subir a git)
├── package.json                # Dependencias
├── node_modules/               # Dependencias instaladas
├── backend/
│   ├── app.js                  # Servidor Express
│   ├── config/config.js        # Configuración
│   ├── controllers/            # Controladores API
│   ├── routes/                 # Rutas API
│   ├── middleware/auth.js      # Middleware JWT
│   ├── models/database.js      # Base de datos JSON
│   ├── services/               # Servicios (MikroTik)
│   └── data/                   # Archivos JSON (datos)
└── frontend/
    ├── index.html              # SPA principal
    ├── css/styles.css          # Estilos
    ├── js/                     # JavaScript frontend
    └── pages/                  # Páginas HTML parciales
```

---

## 🔄 Actualizaciones Futuras

Para actualizar la aplicación:

1. Sube los nuevos archivos reemplazando los existentes (vía FTP o File Manager)
2. Si hay nuevas dependencias, ejecuta **"Run npm install"** desde cPanel
3. Si hay cambios en la estructura de MySQL, ejecuta las migraciones en phpMyAdmin
4. Reinicia la app desde **"Setup Node.js App"** → botón **"Restart"** 🔄

---

## 📞 Soporte

Si encuentras problemas durante el despliegue:

1. Revisa los logs de la aplicación en cPanel
2. Verifica que todas las variables de entorno estén configuradas correctamente
3. Asegúrate de que la versión de Node.js en cPanel sea compatible (18.x o 20.x)

---

*Documento generado el 19/06/2026 - MkController v3.0 → nexusMK*
