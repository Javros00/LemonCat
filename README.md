# Sistema de Ventas (Laravel)

Registro de ventas diarias en línea, catálogo de artículos con precios y costos, corte diario, corte mensual, exportación para tu sistema local y póliza contable de fin de mes.

## Qué hace

| Usuario | Puede |
|---|---|
| **admin** | Todo: panel, ventas, cancelar ventas, corte diario, corte mensual, póliza, exportar, artículos, precios, usuarios |
| **vendedor1, vendedor2, vendedor3** | Solo registrar ventas y ver sus ventas del día (sin costos) |

- **Nueva venta:** buscar por clave o nombre, filtrar por familia, tocar para agregar, cobrar en efectivo (calcula cambio), tarjeta o transferencia. Funciona en computadora, tablet y celular.
- Los artículos con **precio 0** aparecen como "Sin precio" y **no se pueden vender** hasta que el administrador les ponga precio.
- Cada venta guarda el precio y el costo **del momento**: si cambias precios, las ventas pasadas no se alteran.
- **Corte diario y mensual:** total, costo, utilidad, por vendedor, por forma de pago, por familia y por artículo. Las ventas canceladas no se suman y se listan aparte.
- **Exportar para el sistema local:** Excel o CSV con `clave, cantidad, monto` agrupado por artículo (por día o por mes). También hay detalle venta por venta.
- **Póliza mensual:** Caja / Bancos contra Ventas e IVA, y Costo de ventas contra Almacén. Se descarga en Excel o CSV.
- **Artículos y precios:** alta, edición, activar/desactivar, cambio masivo de precios (con ajuste por % o $), historial de cambios, e importar/descargar el catálogo en Excel.

## Requisitos

- **PHP 8.3 o más nuevo** con extensiones `pdo_sqlite` (o `pdo_mysql`), `mbstring`, `xml`, `zip`, `gd`
- **Composer**

> En Windows lo más fácil es instalar [Laravel Herd](https://herd.laravel.com) (trae PHP y Composer). Si usas XAMPP, revisa que tu PHP sea 8.3+ (`php -v`) y activa `extension=zip` y `extension=gd` en `php.ini`.

## Instalación

```bash
cd ventas
composer run setup
php artisan serve
```

`composer run setup` instala dependencias, crea el archivo `.env`, crea la base SQLite, crea las tablas y carga los 4 usuarios y tus 85 productos.

Abre http://localhost:8000

| Usuario | Contraseña inicial |
|---|---|
| admin | admin123 |
| vendedor1 / vendedor2 / vendedor3 | venta123 |

**Cambia las contraseñas** en *Usuarios* la primera vez que entres.

### Usar MySQL en lugar de SQLite

En `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ventas
DB_USERNAME=root
DB_PASSWORD=
```

Crea la base `ventas` y ejecuta `php artisan migrate --seed`.

## Configuración (`.env`)

```
VENTAS_NEGOCIO="Mi Negocio"     # nombre que aparece arriba y en el ticket

# Cuentas contables de la póliza (pide las reales a tu contador)
CTA_CAJA=101.01
CTA_BANCOS=102.01
CTA_VENTAS=401.01
CTA_IVA=208.01
CTA_COSTO=501.01
CTA_ALMACEN=115.01
POLIZA_INCLUIR_COSTO=true        # false para no incluir costo de ventas vs almacén
```

Después de cambiar `.env` en un servidor, ejecuta `php artisan config:clear`.

### IVA

Los precios son **netos** (IVA incluido). Cada artículo tiene su tasa de IVA (0%, 8% o 16%); la póliza desglosa el IVA del total según esa tasa. Al cargar tu lista todos quedaron en **0%** (alimentos). Si vendes refrescos u otros productos con 16%, cámbiales la tasa en *Artículos → Editar* (confírmalo con tu contador).

## Actualizar precios desde Excel

1. *Artículos → Descargar Excel*
2. Cambia precios o costos, o agrega renglones nuevos
3. *Artículos → Importar Excel* y súbelo

Encabezados que reconoce: `plu` o `clave`, `producto` o `descripcion`, `tipo` o `familia`, `precio`, `costo`, y opcionales `iva` y `activo` (SI/NO). Si la clave existe se actualiza; si no, se crea. Una celda vacía deja el dato como está.

## Subir a GitHub

```bash
git init
git add .
git commit -m "Sistema de ventas"
git branch -M main
git remote add origin https://github.com/TU_USUARIO/ventas.git
git push -u origin main
```

El archivo `.env` y la base de datos no se suben (están en `.gitignore`).

## Publicarlo en internet

Para que los vendedores registren ventas desde cualquier lugar necesitas un hosting con PHP 8.3 y MySQL (por ejemplo Laravel Cloud, Hostinger, DigitalOcean o un VPS). Pasos generales: subir el código, `composer install --no-dev`, configurar `.env` con `APP_ENV=production`, `APP_DEBUG=false` y los datos de MySQL, `php artisan key:generate`, `php artisan migrate --seed --force`, y apuntar el dominio a la carpeta `public/`.

## Estructura del código

```
app/Http/Controllers/
  AuthController.php      inicio y cierre de sesión
  VentaController.php     captura de venta, mis ventas, ticket, lista y cancelación
  ArticuloController.php  catálogo, precios, historial, importar/exportar
  ReporteController.php   panel, cortes, póliza y exportaciones
  UsuarioController.php   nombres, contraseñas y acceso
app/Http/Middleware/SoloAdmin.php   bloquea las pantallas de administrador
app/Models/               User, Articulo, CambioPrecio, Venta, VentaDetalle
app/Services/
  ReporteVentas.php       todos los cálculos de cortes y póliza
  Hoja.php                exportar/leer Excel y CSV
config/ventas.php         cuentas de la póliza y nombre del negocio
database/migrations/      tablas
database/seeders/         usuarios y productos iniciales (data/productos.csv)
resources/views/          pantallas (Blade)
public/css/ventas.css     estilos
routes/web.php            rutas
```
