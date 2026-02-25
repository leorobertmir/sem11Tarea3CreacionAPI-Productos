# Documentación API REST - Arquitectura DDD con Laravel

## Tabla de Contenidos
1. [Instalación y Configuración](#instalación-y-configuración)
2. [Arquitectura DDD](#arquitectura-ddd)
3. [Autenticación con Sanctum](#autenticación-con-sanctum)
4. [Estructura de Módulos](#estructura-de-módulos)
5. [Modelos y Base de Datos](#modelos-y-base-de-datos)
6. [API Endpoints](#api-endpoints)
7. [Ejemplos de cURL](#ejemplos-de-curl)

---

## Instalación y Configuración

### Requisitos Previos

Antes de instalar el proyecto, asegúrate de tener instalado:

- **PHP** >= 8.2
- **Composer** (Gestor de dependencias de PHP)
- **PostgreSQL** >= 13
- **Git** (opcional, para clonar el repositorio)

### 1. Instalar Composer

Si aún no tienes Composer instalado, sigue estos pasos:

**En macOS/Linux:**
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

**En Windows:**
Descarga el instalador desde [getcomposer.org](https://getcomposer.org/download/) y ejecútalo.

**Verificar instalación:**
```bash
composer --version
```

### 2. Clonar o Descargar el Proyecto

```bash
# Si usas Git
git clone <url-del-repositorio>
cd api-rest-base

# O si descargaste un ZIP, extráelo y accede a la carpeta
cd api-rest-base
```

### 3. Instalar Dependencias de Laravel

```bash
composer install
```

Este comando instalará todas las dependencias de PHP especificadas en el archivo `composer.json`.

### 4. Configurar Variables de Entorno

```bash
# Copiar el archivo de ejemplo de variables de entorno
cp .env.example .env
```

Edita el archivo `.env` y configura las variables de base de datos:

```env
APP_NAME="API REST Base"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=api_rest_base
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña
```

### 5. Generar Clave de Aplicación

```bash
php artisan key:generate
```

Este comando genera una clave de cifrado única para tu aplicación y la guarda en el archivo `.env`.

### 6. Crear la Base de Datos

Accede a PostgreSQL y crea la base de datos:

```bash
# Acceder a PostgreSQL
psql -U postgres

# Crear la base de datos
CREATE DATABASE api_rest_base;

# Salir
\q
```

### 7. Ejecutar Migraciones

```bash
php artisan migrate
```

Este comando creará todas las tablas en la base de datos según las migraciones definidas en el proyecto.

### 8. (Opcional) Ejecutar Seeders

Si el proyecto tiene seeders para poblar la base de datos con datos de prueba:

```bash
php artisan db:seed
```

### 9. Ejecutar el Servidor de Desarrollo

```bash
php artisan serve
```

Esto iniciará el servidor de desarrollo en `http://localhost:8000`.

**Salida esperada:**
```
Starting Laravel development server: http://127.0.0.1:8000
Press Ctrl+C to stop the server
```

### 10. Instalar y Configurar Laravel Sanctum

Laravel Sanctum proporciona un sistema de autenticación ligero mediante tokens API para SPAs, aplicaciones móviles y APIs simples.

#### ¿Qué es Laravel Sanctum?

**Laravel Sanctum** es un paquete oficial de Laravel que permite:
- Autenticación mediante tokens API (Bearer tokens)
- Proteger rutas de API con middleware `auth:sanctum`
- Generar y revocar tokens de acceso
- Autenticación stateless (sin sesiones del lado del servidor)

#### Instalación de Sanctum

```bash
# 1. Instalar el paquete Sanctum
composer require laravel/sanctum

# 2. Publicar los archivos de configuración y migraciones
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# 3. Ejecutar las migraciones para crear la tabla personal_access_tokens
php artisan migrate
```

#### Configuración del Modelo User

Agrega el trait `HasApiTokens` al modelo de usuario:

**Archivo:** `src/Auth/Infrastructure/Models/UserEloquentModel.php`

```php
<?php

namespace Src\Auth\Infrastructure\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class UserEloquentModel extends Authenticatable
{
    use HasApiTokens; // ← Agregar este trait

    protected $table = 'users';

    protected $fillable = ['id', 'name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];
}
```

#### ¿Qué es `auth:sanctum`?

`auth:sanctum` es un **middleware** de Laravel que protege las rutas de tu API.

**Funcionamiento:**
1. El cliente envía una petición HTTP con el header: `Authorization: Bearer {token}`
2. El middleware `auth:sanctum` intercepta la petición
3. Valida que el token sea válido y exista en la base de datos
4. Si es válido → permite el acceso y carga el usuario autenticado
5. Si es inválido → retorna error 401 Unauthorized

**Ejemplo de uso en rutas:**

**Archivo:** `routes/api.php`

```php
Route::prefix('v1')->group(function () {
    // Rutas públicas (sin autenticación)
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Rutas protegidas con Sanctum
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::apiResource('clientes', ClienteController::class);
        Route::apiResource('productos', ProductoController::class);
        Route::apiResource('facturas', FacturaController::class);
    });
});
```

#### Flujo de Autenticación con Sanctum

```
┌─────────────┐                                    ┌─────────────┐
│   Cliente   │                                    │   Servidor  │
└──────┬──────┘                                    └──────┬──────┘
       │                                                  │
       │  1. POST /auth/login                            │
       │     { email, password }                         │
       ├────────────────────────────────────────────────>│
       │                                                  │
       │  2. Valida credenciales                         │
       │     Genera token con createToken()              │
       │                                                  │
       │  3. Response: { user, token }                   │
       │<────────────────────────────────────────────────┤
       │                                                  │
       │  Guarda token en localStorage                   │
       │                                                  │
       │  4. GET /clientes                               │
       │     Header: Authorization: Bearer {token}       │
       ├────────────────────────────────────────────────>│
       │                                                  │
       │  5. Middleware auth:sanctum valida token        │
       │                                                  │
       │  6. Response: [clientes...]                     │
       │<────────────────────────────────────────────────┤
       │                                                  │
       │  7. POST /auth/logout                           │
       │     Header: Authorization: Bearer {token}       │
       ├────────────────────────────────────────────────>│
       │                                                  │
       │  8. Elimina token con delete()                  │
       │                                                  │
       │  9. Response: { message: "Sesión cerrada" }     │
       │<────────────────────────────────────────────────┤
       │                                                  │
```

#### Generar Tokens

En tu Action de login/register:

```php
// Generar token
$token = $user->createToken('auth-token')->plainTextToken;

return [
    'user' => $user,
    'token' => $token
];
```

#### Revocar Tokens (Logout)

```php
// Eliminar token actual
$request->user()->currentAccessToken()->delete();

// O eliminar todos los tokens del usuario
$request->user()->tokens()->delete();
```

#### Obtener Usuario Autenticado

```php
// En un controlador o action protegida por auth:sanctum
$user = $request->user(); // Usuario autenticado

// O usar el helper auth()
$user = auth()->user();
```

#### Tabla personal_access_tokens

Sanctum crea una tabla para almacenar los tokens:

```sql
CREATE TABLE personal_access_tokens (
    id BIGINT PRIMARY KEY,
    tokenable_type VARCHAR(255),  -- Modelo asociado (User)
    tokenable_id BIGINT,          -- ID del usuario
    name VARCHAR(255),            -- Nombre del token
    token VARCHAR(64) UNIQUE,     -- Hash del token
    abilities TEXT,               -- Permisos del token
    last_used_at TIMESTAMP,       -- Última vez usado
    expires_at TIMESTAMP,         -- Fecha de expiración
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### 11. Verificar la Instalación

Abre tu navegador o cliente HTTP y accede a:

```
http://localhost:8000/api/v1/auth/register
```

O usa cURL para probar un endpoint:

```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Usuario Prueba",
    "email": "prueba@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

Si recibes una respuesta JSON con el usuario y token, la instalación fue exitosa.

### Comandos Útiles

```bash
# Limpiar caché de configuración
php artisan config:clear

# Limpiar caché de rutas
php artisan route:clear

# Ver todas las rutas disponibles
php artisan route:list

# Crear una nueva migración
php artisan make:migration nombre_de_la_migracion

# Revertir la última migración
php artisan migrate:rollback

# Recargar todas las migraciones (¡CUIDADO! Elimina todos los datos)
php artisan migrate:fresh

# Recargar migraciones y ejecutar seeders
php artisan migrate:fresh --seed
```

### Estructura de Archivos Importante

```
api-rest-base/
├── app/                    # Código de aplicación Laravel tradicional
├── src/                    # Arquitectura DDD (Domain, Application, Infrastructure)
│   ├── Auth/
│   ├── Cliente/
│   ├── Producto/
│   ├── Factura/
│   └── ...
├── config/                 # Archivos de configuración
├── database/
│   ├── migrations/        # Migraciones de base de datos
│   └── seeders/          # Seeders para datos de prueba
├── routes/
│   ├── api.php           # Rutas de la API
│   └── web.php
├── .env                   # Variables de entorno (NO subir a Git)
├── .env.example          # Plantilla de variables de entorno
├── composer.json         # Dependencias de PHP
└── artisan              # CLI de Laravel
```

### Solución de Problemas Comunes

**Error: "Class 'PDO' not found"**
- Instala la extensión PDO para PostgreSQL:
  ```bash
  # Ubuntu/Debian
  sudo apt-get install php8.2-pgsql

  # macOS (con Homebrew)
  brew install php@8.2
  ```

**Error: "SQLSTATE[08006] Could not connect to server"**
- Verifica que PostgreSQL esté corriendo
- Confirma las credenciales en el archivo `.env`
- Verifica el puerto (por defecto 5432)

**Error: "permission denied for schema public"**
```bash
# Accede a PostgreSQL y otorga permisos
psql -U postgres -d api_rest_base
GRANT ALL ON SCHEMA public TO tu_usuario;
```

**Error: "The stream or file could not be opened"**
```bash
# Da permisos a las carpetas de storage y bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

---

## Arquitectura DDD

Este proyecto implementa **Domain-Driven Design (DDD)** con una clara separación en 3 capas:

### Estructura de Capas

```
src/
├── {BoundedContext}/
│   ├── Domain/                  # Capa de Dominio (Lógica de negocio pura)
│   │   ├── Entities/           # Entidades del dominio
│   │   └── Contracts/          # Interfaces (Repository Interfaces)
│   │
│   ├── Application/            # Capa de Aplicación (Casos de uso)
│   │   ├── Actions/           # Use Cases (CreateAction, UpdateAction, etc.)
│   │   └── Controllers/       # Controladores HTTP
│   │
│   └── Infrastructure/         # Capa de Infraestructura (Implementación técnica)
│       ├── Models/            # Modelos Eloquent
│       ├── Repositories/      # Implementación de repositorios
│       ├── Mappers/          # Conversión Entity ↔ Eloquent
│       ├── Requests/         # Form Requests (Validación)
│       ├── Resources/        # API Resources (Transformación JSON)
│       └── Migrations/       # Migraciones de BD
```

### Principios DDD Implementados

1. **Entities (Entidades de Dominio)**
   - Representan conceptos del negocio
   - Contienen lógica de dominio
   - Inmutables (solo se modifican mediante métodos específicos)
   - No dependen de frameworks

2. **Repository Pattern**
   - Abstracción del acceso a datos
   - Interfaces en Domain, implementación en Infrastructure
   - Permite cambiar la BD sin afectar la lógica de negocio

3. **Actions (Use Cases)**
   - Representan casos de uso del negocio
   - Una clase = una acción
   - Orquestan la lógica entre repositorios y entidades

4. **Mappers**
   - Convierten entre Eloquent Models y Domain Entities
   - Mantienen las capas desacopladas

5. **Dependency Injection**
   - Inyección de dependencias en Controllers y Actions
   - Configurado en `BoundedContextServiceProvider`

---

## Autenticación con Sanctum

### ¿Qué es Laravel Sanctum?

Laravel Sanctum proporciona un sistema de autenticación ligero para SPAs y APIs simples mediante tokens.

### Características

- **Token API**: Genera tokens de acceso para autenticación
- **Bearer Authentication**: Los tokens se envían en el header `Authorization: Bearer {token}`
- **Stateful/Stateless**: Soporta autenticación con sesión (web) y tokens (API)
- **Revocación de tokens**: Permite eliminar tokens (logout)

### Configuración

**Archivo:** `config/sanctum.php`

```php
'guard' => ['web'],
'stateful' => ['localhost', 'localhost:3000', '127.0.0.1', ...],
```

**Modelo de Usuario:** Usa el trait `HasApiTokens`

```php
use Laravel\Sanctum\HasApiTokens;

class UserEloquentModel extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuid;
}
```

### Flujo de Autenticación

1. **Registro/Login** → Genera token con `createToken()`
2. **Cliente guarda token** en localStorage/sessionStorage
3. **Peticiones protegidas** → Envía header `Authorization: Bearer {token}`
4. **Middleware** `auth:sanctum` valida el token
5. **Logout** → Elimina el token actual con `currentAccessToken()->delete()`

### Middleware de Protección

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('clientes', ClienteController::class);
    Route::apiResource('facturas', FacturaController::class);
    // ...
});
```

---

## Estructura de Módulos

### Módulo de Autenticación (Auth)

**Ubicación:** `src/Auth/`

#### Domain Layer
- **Entity:** `UserEntity`
  - Propiedades: id, name, email, password, createdAt, updatedAt
  - Sin lógica de framework, solo getters/setters

- **Contract:** `UserRepositoryInterface`
  ```php
  interface UserRepositoryInterface
  {
      public function findByEmail(string $email): ?User;
      public function create(array $data): User;
  }
  ```

#### Application Layer
- **Actions:**
  - `RegisterAction`: Crea usuario, hash password, genera token
  - `LoginAction`: Valida credenciales, genera token
  - `LogoutAction`: Revoca token actual
  - `GetMeAction`: Obtiene usuario autenticado

- **Controller:** `AuthController`
  ```php
  public function login(LoginRequest $request)
  {
      $result = $this->loginAction->execute(
          $request->validated()
      );

      return response()->json([
          'user' => new UserResource($result['user']),
          'token' => $result['token']
      ]);
  }
  ```

#### Infrastructure Layer
- **Modelo Eloquent:** `UserEloquentModel`
  ```php
  protected $fillable = ['id', 'name', 'email', 'password'];

  protected $hidden = ['password', 'remember_token'];

  protected $casts = [
      'email_verified_at' => 'datetime',
      'password' => 'hashed',
  ];
  ```

- **Repository:** `EloquentUserRepository`
  ```php
  public function findByEmail(string $email): ?User
  {
      $model = UserEloquentModel::where('email', $email)->first();
      return $model ? UserMapper::toDomain($model) : null;
  }
  ```

- **Requests:**
  - `RegisterRequest`: Valida name, email (único), password (confirmación)
  - `LoginRequest`: Valida email, password

- **Resource:** `UserResource`
  ```php
  public function toArray($request): array
  {
      return [
          'id' => $this->id,
          'name' => $this->name,
          'email' => $this->email,
          'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
          'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),
      ];
  }
  ```

---

### Módulo Cliente

**Ubicación:** `src/Cliente/`

#### Domain Layer
- **Entity:** `Cliente`
  ```php
  class Cliente
  {
      private string $id;
      private string $tipoDocumento;
      private string $numeroDocumento;
      private string $razonSocial;
      private string $direccion;
      private string $telefono;
      private string $email;

      public function updateRazonSocial(string $razonSocial): void
      {
          $this->razonSocial = $razonSocial;
      }
  }
  ```

- **Contract:** `ClienteRepositoryInterface`
  ```php
  interface ClienteRepositoryInterface
  {
      public function findAll(): Collection;
      public function findById(string $id): ?Cliente;
      public function save(Cliente $cliente): Cliente;
      public function update(string $id, array $data): ?Cliente;
      public function delete(string $id): bool;
      public function searchPaginated(?string $search, int $perPage): LengthAwarePaginator;
  }
  ```

#### Application Layer
- **Actions:**
  ```php
  class CreateClienteAction
  {
      public function __construct(
          private ClienteRepositoryInterface $repository
      ) {}

      public function execute(array $data): Cliente
      {
          $cliente = new Cliente(...$data);
          return $this->repository->save($cliente);
      }
  }
  ```

- **Controller:** `ClienteController`
  - Inyecta todas las Actions
  - Maneja respuestas HTTP
  - Usa Resources para formatear JSON

#### Infrastructure Layer
- **Modelo Eloquent:** `ClienteEloquentModel`
  ```php
  protected $table = 'clientes';

  protected $fillable = [
      'id', 'tipo_documento', 'numero_documento',
      'razon_social', 'direccion', 'telefono', 'email'
  ];

  public function facturas(): HasMany
  {
      return $this->hasMany(FacturaEloquentModel::class, 'cliente_id');
  }
  ```

- **Mapper:** `ClienteMapper`
  ```php
  public static function toDomain(ClienteEloquentModel $model): Cliente
  {
      return new Cliente(
          id: $model->id,
          tipoDocumento: $model->tipo_documento,
          numeroDocumento: $model->numero_documento,
          // ...
      );
  }
  ```

- **Repository:** `EloquentClienteRepository`
  ```php
  public function delete(string $id): bool
  {
      $cliente = ClienteEloquentModel::find($id);

      if (!$cliente) return false;

      // Regla de negocio: no eliminar si tiene facturas
      if ($cliente->facturas()->exists()) {
          return false;
      }

      return (bool) $cliente->delete();
  }
  ```

- **Requests:**
  ```php
  class StoreClienteRequest extends FormRequest
  {
      protected function prepareForValidation()
      {
          // Convierte camelCase a snake_case
          $this->merge([
              'tipo_documento' => $this->tipoDocumento,
              'numero_documento' => $this->numeroDocumento,
              'razon_social' => $this->razonSocial,
          ]);
      }

      public function rules(): array
      {
          return [
              'tipo_documento' => 'required|in:DNI,RUC,CE,PASAPORTE',
              'numero_documento' => 'required|unique:clientes',
              'razon_social' => 'required|max:255',
              'email' => 'required|email|unique:clientes',
              // ...
          ];
      }
  }
  ```

- **Resource:** `ClienteResource`
  ```php
  public function toArray($request): array
  {
      return [
          'id' => $this->id,
          'tipoDocumento' => $this->tipo_documento,
          'numeroDocumento' => $this->numero_documento,
          'razonSocial' => $this->razon_social,
          'direccion' => $this->direccion,
          'telefono' => $this->telefono,
          'email' => $this->email,
          'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
          'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),
      ];
  }
  ```

---

### Módulo Factura

**Ubicación:** `src/Factura/`

#### Domain Layer
- **Entity:** `Factura`
  ```php
  class Factura
  {
      private string $id;
      private string $numeroFactura;
      private string $serie;
      private string $clienteId;
      private string $usuarioId;
      private DateTime $fechaEmision;
      private ?DateTime $fechaVencimiento;
      private float $subtotal;
      private float $igv;
      private float $descuento;
      private float $total;
      private string $estado; // emitida, pagada, anulada
      private ?string $observaciones;
  }
  ```

#### Infrastructure Layer
- **Modelo Eloquent:** `FacturaEloquentModel`
  ```php
  protected $fillable = [
      'id', 'numero_factura', 'serie', 'cliente_id', 'usuario_id',
      'fecha_emision', 'fecha_vencimiento', 'subtotal', 'igv',
      'descuento', 'total', 'estado', 'observaciones'
  ];

  protected $casts = [
      'fecha_emision' => 'date',
      'fecha_vencimiento' => 'date',
      'subtotal' => 'decimal:2',
      'igv' => 'decimal:2',
      'descuento' => 'decimal:2',
      'total' => 'decimal:2',
  ];

  public function cliente(): BelongsTo
  {
      return $this->belongsTo(ClienteEloquentModel::class, 'cliente_id');
  }

  public function usuario(): BelongsTo
  {
      return $this->belongsTo(UserEloquentModel::class, 'usuario_id');
  }

  public function detalles(): HasMany
  {
      return $this->hasMany(DetalleFacturaEloquentModel::class, 'factura_id');
  }
  ```

- **Request:** `StoreFacturaRequest`
  ```php
  public function rules(): array
  {
      return [
          'numero_factura' => 'required|unique:facturas',
          'serie' => 'required|max:10',
          'cliente_id' => 'required|exists:clientes,id',
          'fecha_emision' => 'required|date',
          'estado' => 'in:emitida,pagada,anulada',
          'detalles' => 'required|array|min:1',
          'detalles.*.producto_id' => 'required|exists:productos,id',
          'detalles.*.cantidad' => 'required|integer|min:1',
          'detalles.*.precio_unitario' => 'required|numeric|min:0',
      ];
  }
  ```

- **Resource:** `FacturaResource`
  ```php
  public function toArray($request): array
  {
      return [
          'id' => $this->id,
          'numeroFactura' => $this->numero_factura,
          'serie' => $this->serie,
          'clienteId' => $this->cliente_id,
          'cliente' => $this->whenLoaded('cliente', function () {
              return [
                  'id' => $this->cliente->id,
                  'razonSocial' => $this->cliente->razon_social,
              ];
          }),
          'detalles' => DetalleFacturaResource::collection(
              $this->whenLoaded('detalles')
          ),
          // ...
      ];
  }
  ```

---

## Modelos y Base de Datos

### Schema de Base de Datos

#### Tabla: `users`
```sql
CREATE TABLE users (
    id UUID PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### Tabla: `clientes`
```sql
CREATE TABLE clientes (
    id UUID PRIMARY KEY,
    tipo_documento VARCHAR(20) NOT NULL,
    numero_documento VARCHAR(20) UNIQUE NOT NULL,
    razon_social VARCHAR(255) NOT NULL,
    direccion VARCHAR(255) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### Tabla: `categorias`
```sql
CREATE TABLE categorias (
    id UUID PRIMARY KEY,
    nombre VARCHAR(100) UNIQUE NOT NULL,
    descripcion TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### Tabla: `productos`
```sql
CREATE TABLE productos (
    id UUID PRIMARY KEY,
    categoria_id UUID NOT NULL,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    stock INTEGER NOT NULL DEFAULT 0,
    tipo VARCHAR(20) NOT NULL, -- 'bien' o 'servicio'
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
);
```

#### Tabla: `facturas`
```sql
CREATE TABLE facturas (
    id UUID PRIMARY KEY,
    numero_factura VARCHAR(20) UNIQUE NOT NULL,
    serie VARCHAR(10) NOT NULL,
    cliente_id UUID NOT NULL,
    usuario_id UUID NOT NULL,
    fecha_emision DATE NOT NULL,
    fecha_vencimiento DATE NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    igv DECIMAL(10,2) NOT NULL,
    descuento DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    estado VARCHAR(20) DEFAULT 'emitida', -- 'emitida', 'pagada', 'anulada'
    observaciones TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (usuario_id) REFERENCES users(id)
);
```

#### Tabla: `detalle_facturas`
```sql
CREATE TABLE detalle_facturas (
    id UUID PRIMARY KEY,
    factura_id UUID NOT NULL,
    producto_id UUID NOT NULL,
    cantidad INTEGER NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    descuento DECIMAL(10,2) DEFAULT 0,
    subtotal DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id)
);
```

### Relaciones entre Modelos

```
User (1) ----< (N) Factura
Cliente (1) ----< (N) Factura
Factura (1) ----< (N) DetalleFactura
Producto (1) ----< (N) DetalleFactura
Categoria (1) ----< (N) Producto
```

---

## API Endpoints

### Base URL
```
http://localhost:8000/api/v1
```

### Autenticación

| Método | Endpoint | Middleware | Descripción |
|--------|----------|-----------|-------------|
| POST | `/auth/register` | - | Registrar nuevo usuario |
| POST | `/auth/login` | - | Iniciar sesión |
| POST | `/auth/logout` | auth:sanctum | Cerrar sesión |
| GET | `/auth/me` | auth:sanctum | Obtener usuario autenticado |

### Clientes

| Método | Endpoint | Middleware | Descripción |
|--------|----------|-----------|-------------|
| GET | `/clientes` | auth:sanctum | Listar todos los clientes |
| POST | `/clientes` | auth:sanctum | Crear cliente |
| GET | `/clientes/{id}` | auth:sanctum | Obtener cliente por ID |
| PUT | `/clientes/{id}` | auth:sanctum | Actualizar cliente |
| DELETE | `/clientes/{id}` | auth:sanctum | Eliminar cliente |

### Productos

| Método | Endpoint | Middleware | Descripción |
|--------|----------|-----------|-------------|
| GET | `/productos` | auth:sanctum | Listar todos los productos |
| POST | `/productos` | auth:sanctum | Crear producto |
| GET | `/productos/{id}` | auth:sanctum | Obtener producto por ID |
| PUT | `/productos/{id}` | auth:sanctum | Actualizar producto |
| DELETE | `/productos/{id}` | auth:sanctum | Eliminar producto |

### Categorías

| Método | Endpoint | Middleware | Descripción |
|--------|----------|-----------|-------------|
| GET | `/categorias` | auth:sanctum | Listar todas las categorías |
| POST | `/categorias` | auth:sanctum | Crear categoría |
| GET | `/categorias/{id}` | auth:sanctum | Obtener categoría por ID |
| PUT | `/categorias/{id}` | auth:sanctum | Actualizar categoría |
| DELETE | `/categorias/{id}` | auth:sanctum | Eliminar categoría |

### Facturas

| Método | Endpoint | Middleware | Descripción |
|--------|----------|-----------|-------------|
| GET | `/facturas` | auth:sanctum | Listar todas las facturas |
| POST | `/facturas` | auth:sanctum | Crear factura con detalles |
| GET | `/facturas/{id}` | auth:sanctum | Obtener factura por ID |
| PUT | `/facturas/{id}` | auth:sanctum | Actualizar factura |
| DELETE | `/facturas/{id}` | auth:sanctum | Eliminar factura |

---

## Ejemplos de cURL

### 1. Registro de Usuario

```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Juan Pérez",
    "email": "juan.perez@example.com",
    "password": "password123",
    "passwordConfirmation": "password123"
  }'
```

**Respuesta:**
```json
{
  "user": {
    "id": "9d3e4b5a-6c7d-8e9f-0a1b-2c3d4e5f6a7b",
    "name": "Juan Pérez",
    "email": "juan.perez@example.com",
    "createdAt": "2026-01-04 10:30:00",
    "updatedAt": "2026-01-04 10:30:00"
  },
  "token": "1|abcdefghijklmnopqrstuvwxyz123456789"
}
```

### 2. Login

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "juan.perez@example.com",
    "password": "password123"
  }'
```

**Respuesta:**
```json
{
  "user": {
    "id": "9d3e4b5a-6c7d-8e9f-0a1b-2c3d4e5f6a7b",
    "name": "Juan Pérez",
    "email": "juan.perez@example.com",
    "createdAt": "2026-01-04 10:30:00",
    "updatedAt": "2026-01-04 10:30:00"
  },
  "token": "2|zyxwvutsrqponmlkjihgfedcba987654321"
}
```

### 3. Obtener Usuario Autenticado

```bash
TOKEN="2|zyxwvutsrqponmlkjihgfedcba987654321"

curl -X GET http://localhost:8000/api/v1/auth/me \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

**Respuesta:**
```json
{
  "id": "9d3e4b5a-6c7d-8e9f-0a1b-2c3d4e5f6a7b",
  "name": "Juan Pérez",
  "email": "juan.perez@example.com",
  "createdAt": "2026-01-04 10:30:00",
  "updatedAt": "2026-01-04 10:30:00"
}
```

### 4. Crear Cliente

```bash
TOKEN="2|zyxwvutsrqponmlkjihgfedcba987654321"

curl -X POST http://localhost:8000/api/v1/clientes \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "tipoDocumento": "RUC",
    "numeroDocumento": "20123456789",
    "razonSocial": "Empresa ABC SAC",
    "direccion": "Av. Principal 123, Lima",
    "telefono": "987654321",
    "email": "contacto@empresaabc.com"
  }'
```

**Respuesta:**
```json
{
  "id": "8c2d3b4a-5c6d-7e8f-9a0b-1c2d3e4f5a6b",
  "tipoDocumento": "RUC",
  "numeroDocumento": "20123456789",
  "razonSocial": "Empresa ABC SAC",
  "direccion": "Av. Principal 123, Lima",
  "telefono": "987654321",
  "email": "contacto@empresaabc.com",
  "createdAt": "2026-01-04 11:00:00",
  "updatedAt": "2026-01-04 11:00:00"
}
```

### 5. Listar Clientes

```bash
TOKEN="2|zyxwvutsrqponmlkjihgfedcba987654321"

curl -X GET http://localhost:8000/api/v1/clientes \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

**Respuesta:**
```json
[
  {
    "id": "8c2d3b4a-5c6d-7e8f-9a0b-1c2d3e4f5a6b",
    "tipoDocumento": "RUC",
    "numeroDocumento": "20123456789",
    "razonSocial": "Empresa ABC SAC",
    "direccion": "Av. Principal 123, Lima",
    "telefono": "987654321",
    "email": "contacto@empresaabc.com",
    "createdAt": "2026-01-04 11:00:00",
    "updatedAt": "2026-01-04 11:00:00"
  }
]
```

### 6. Actualizar Cliente

```bash
TOKEN="2|zyxwvutsrqponmlkjihgfedcba987654321"
CLIENTE_ID="8c2d3b4a-5c6d-7e8f-9a0b-1c2d3e4f5a6b"

curl -X PUT http://localhost:8000/api/v1/clientes/$CLIENTE_ID \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "telefono": "998877665",
    "direccion": "Av. Los Olivos 456, Lima"
  }'
```

**Respuesta:**
```json
{
  "id": "8c2d3b4a-5c6d-7e8f-9a0b-1c2d3e4f5a6b",
  "tipoDocumento": "RUC",
  "numeroDocumento": "20123456789",
  "razonSocial": "Empresa ABC SAC",
  "direccion": "Av. Los Olivos 456, Lima",
  "telefono": "998877665",
  "email": "contacto@empresaabc.com",
  "createdAt": "2026-01-04 11:00:00",
  "updatedAt": "2026-01-04 11:15:00"
}
```

### 7. Eliminar Cliente

```bash
TOKEN="2|zyxwvutsrqponmlkjihgfedcba987654321"
CLIENTE_ID="8c2d3b4a-5c6d-7e8f-9a0b-1c2d3e4f5a6b"

curl -X DELETE http://localhost:8000/api/v1/clientes/$CLIENTE_ID \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

**Respuesta (Éxito):**
```json
{
  "success": true,
  "message": "Cliente eliminado exitosamente"
}
```

**Respuesta (Error - Cliente con facturas):**
```json
{
  "success": false,
  "message": "No se puede eliminar este cliente porque tiene facturas asociadas"
}
```

### 8. Crear Categoría

```bash
TOKEN="2|zyxwvutsrqponmlkjihgfedcba987654321"

curl -X POST http://localhost:8000/api/v1/categorias \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "nombre": "Electrónica",
    "descripcion": "Productos electrónicos y tecnología"
  }'
```

**Respuesta:**
```json
{
  "id": "7b1c2d3e-4f5a-6b7c-8d9e-0f1a2b3c4d5e",
  "nombre": "Electrónica",
  "descripcion": "Productos electrónicos y tecnología",
  "createdAt": "2026-01-04 11:30:00",
  "updatedAt": "2026-01-04 11:30:00"
}
```

### 9. Crear Producto

```bash
TOKEN="2|zyxwvutsrqponmlkjihgfedcba987654321"
CATEGORIA_ID="7b1c2d3e-4f5a-6b7c-8d9e-0f1a2b3c4d5e"

curl -X POST http://localhost:8000/api/v1/productos \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "categoriaId": "'$CATEGORIA_ID'",
    "codigo": "LAPTOP-001",
    "nombre": "Laptop HP Pavilion 15",
    "descripcion": "Laptop HP Pavilion 15 pulgadas, Intel Core i5, 8GB RAM, 256GB SSD",
    "precioUnitario": 2500.00,
    "stock": 10,
    "tipo": "bien",
    "activo": true
  }'
```

**Respuesta:**
```json
{
  "id": "6a0b1c2d-3e4f-5a6b-7c8d-9e0f1a2b3c4d",
  "categoriaId": "7b1c2d3e-4f5a-6b7c-8d9e-0f1a2b3c4d5e",
  "codigo": "LAPTOP-001",
  "nombre": "Laptop HP Pavilion 15",
  "descripcion": "Laptop HP Pavilion 15 pulgadas, Intel Core i5, 8GB RAM, 256GB SSD",
  "precioUnitario": "2500.00",
  "stock": 10,
  "tipo": "bien",
  "activo": true,
  "createdAt": "2026-01-04 12:00:00",
  "updatedAt": "2026-01-04 12:00:00"
}
```

### 10. Listar Productos

```bash
TOKEN="2|zyxwvutsrqponmlkjihgfedcba987654321"

curl -X GET http://localhost:8000/api/v1/productos \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

### 11. Crear Factura con Detalles

```bash
TOKEN="2|zyxwvutsrqponmlkjihgfedcba987654321"
CLIENTE_ID="8c2d3b4a-5c6d-7e8f-9a0b-1c2d3e4f5a6b"
PRODUCTO_ID="6a0b1c2d-3e4f-5a6b-7c8d-9e0f1a2b3c4d"

curl -X POST http://localhost:8000/api/v1/facturas \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "numeroFactura": "FAC-001",
    "serie": "F001",
    "clienteId": "'$CLIENTE_ID'",
    "fechaEmision": "2026-01-04",
    "fechaVencimiento": "2026-02-04",
    "subtotal": 2500.00,
    "igv": 450.00,
    "descuento": 0.00,
    "total": 2950.00,
    "estado": "emitida",
    "observaciones": "Primera venta del mes",
    "detalles": [
      {
        "productoId": "'$PRODUCTO_ID'",
        "cantidad": 1,
        "precioUnitario": 2500.00,
        "descuento": 0.00,
        "subtotal": 2500.00
      }
    ]
  }'
```

**Respuesta:**
```json
{
  "id": "5a9b0c1d-2e3f-4a5b-6c7d-8e9f0a1b2c3d",
  "numeroFactura": "FAC-001",
  "serie": "F001",
  "clienteId": "8c2d3b4a-5c6d-7e8f-9a0b-1c2d3e4f5a6b",
  "usuarioId": "9d3e4b5a-6c7d-8e9f-0a1b-2c3d4e5f6a7b",
  "fechaEmision": "2026-01-04",
  "fechaVencimiento": "2026-02-04",
  "subtotal": "2500.00",
  "igv": "450.00",
  "descuento": "0.00",
  "total": "2950.00",
  "estado": "emitida",
  "observaciones": "Primera venta del mes",
  "cliente": {
    "id": "8c2d3b4a-5c6d-7e8f-9a0b-1c2d3e4f5a6b",
    "razonSocial": "Empresa ABC SAC"
  },
  "detalles": [
    {
      "id": "4a8b9c0d-1e2f-3a4b-5c6d-7e8f9a0b1c2d",
      "facturaId": "5a9b0c1d-2e3f-4a5b-6c7d-8e9f0a1b2c3d",
      "productoId": "6a0b1c2d-3e4f-5a6b-7c8d-9e0f1a2b3c4d",
      "cantidad": 1,
      "precioUnitario": "2500.00",
      "descuento": "0.00",
      "subtotal": "2500.00",
      "producto": {
        "id": "6a0b1c2d-3e4f-5a6b-7c8d-9e0f1a2b3c4d",
        "nombre": "Laptop HP Pavilion 15"
      }
    }
  ],
  "createdAt": "2026-01-04 13:00:00",
  "updatedAt": "2026-01-04 13:00:00"
}
```

### 12. Obtener Factura por ID

```bash
TOKEN="2|zyxwvutsrqponmlkjihgfedcba987654321"
FACTURA_ID="5a9b0c1d-2e3f-4a5b-6c7d-8e9f0a1b2c3d"

curl -X GET http://localhost:8000/api/v1/facturas/$FACTURA_ID \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

### 13. Actualizar Estado de Factura

```bash
TOKEN="2|zyxwvutsrqponmlkjihgfedcba987654321"
FACTURA_ID="5a9b0c1d-2e3f-4a5b-6c7d-8e9f0a1b2c3d"

curl -X PUT http://localhost:8000/api/v1/facturas/$FACTURA_ID \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "estado": "pagada"
  }'
```

### 14. Logout

```bash
TOKEN="2|zyxwvutsrqponmlkjihgfedcba987654321"

curl -X POST http://localhost:8000/api/v1/auth/logout \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

**Respuesta:**
```json
{
  "message": "Sesión cerrada exitosamente"
}
```

---

## Conceptos Clave

### JsonResource

**Definición:** Capa de transformación entre modelos Eloquent y respuestas JSON API.

**Propósito:**
- Controlar qué campos se exponen en la API
- Transformar nombres de campos (snake_case → camelCase)
- Incluir/excluir relaciones cargadas
- Formatear datos (fechas, decimales)

**Ejemplo:**
```php
class ClienteResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'tipoDocumento' => $this->tipo_documento, // Transforma nombre
            'razonSocial' => $this->razon_social,
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'), // Formatea fecha
        ];
    }
}
```

### Form Request

**Definición:** Clase que encapsula validación de peticiones HTTP.

**Propósito:**
- Validar datos de entrada
- Autorizar la petición
- Transformar datos antes de validar (camelCase → snake_case)
- Centralizar reglas de validación

**Métodos principales:**
- `authorize()`: Determina si el usuario puede hacer esta petición
- `rules()`: Define reglas de validación
- `prepareForValidation()`: Transforma datos antes de validar
- `messages()`: Mensajes de error personalizados
- `attributes()`: Nombres de campos en español

### Action (Use Case)

**Definición:** Clase que representa un caso de uso del negocio.

**Propósito:**
- Encapsular lógica de negocio
- Orquestar operaciones entre repositorios
- Un action = una responsabilidad
- Facilitar testing

**Ejemplo:**
```php
class CreateClienteAction
{
    public function __construct(
        private ClienteRepositoryInterface $repository
    ) {}

    public function execute(array $data): Cliente
    {
        // Lógica de negocio aquí
        return $this->repository->save(new Cliente(...$data));
    }
}
```

### Contract (Interface)

**Definición:** Interfaz que define un contrato entre capas.

**Propósito:**
- Inversión de dependencias (Dependency Inversion Principle)
- Permite cambiar implementación sin afectar consumidores
- Facilita testing con mocks

**Ejemplo:**
```php
interface ClienteRepositoryInterface
{
    public function findById(string $id): ?Cliente;
    public function save(Cliente $cliente): Cliente;
}
```

### Entity (Entidad de Dominio)

**Definición:** Objeto que representa un concepto del negocio.

**Características:**
- Independiente de frameworks
- Contiene lógica de dominio
- Inmutable (se modifica solo con métodos específicos)
- No conoce la base de datos

**Ejemplo:**
```php
class Cliente
{
    private string $id;
    private string $razonSocial;

    public function updateRazonSocial(string $nuevaRazon): void
    {
        // Validación de negocio
        if (empty($nuevaRazon)) {
            throw new InvalidArgumentException('Razón social no puede estar vacía');
        }

        $this->razonSocial = $nuevaRazon;
    }
}
```

### Eloquent Model

**Definición:** Clase que representa una tabla de base de datos usando Eloquent ORM.

**Características:**
- Mapea tabla → objeto PHP
- Define relaciones entre tablas
- Maneja conversión de tipos (casts)
- Proporciona query builder

**Diferencia con Entity:**
- **Modelo Eloquent**: Capa de infraestructura, conoce la BD
- **Entity**: Capa de dominio, no conoce la BD

### Eloquent Repository

**Definición:** Implementación del patrón Repository usando Eloquent.

**Propósito:**
- Abstrae acceso a datos
- Convierte Eloquent Models ↔ Domain Entities usando Mappers
- Encapsula queries complejas
- Implementa lógica de negocio relacionada con persistencia

**Ejemplo:**
```php
class EloquentClienteRepository implements ClienteRepositoryInterface
{
    public function findById(string $id): ?Cliente
    {
        $model = ClienteEloquentModel::find($id);

        return $model ? ClienteMapper::toDomain($model) : null;
    }
}
```

### Mapper

**Definición:** Clase que convierte entre Eloquent Models y Domain Entities.

**Propósito:**
- Desacoplar infraestructura de dominio
- Transformar estructuras de datos
- Mantener capas independientes

**Ejemplo:**
```php
class ClienteMapper
{
    public static function toDomain(ClienteEloquentModel $model): Cliente
    {
        return new Cliente(
            id: $model->id,
            tipoDocumento: $model->tipo_documento,
            numeroDocumento: $model->numero_documento,
            // ...
        );
    }

    public static function toArray(Cliente $entity): array
    {
        return [
            'id' => $entity->getId(),
            'tipo_documento' => $entity->getTipoDocumento(),
            // ...
        ];
    }
}
```

### Bearer Token

**Definición:** Método de autenticación donde el token se envía en el header Authorization.

**Formato:**
```
Authorization: Bearer {token}
```

**Flujo:**
1. Usuario se loguea → Recibe token
2. Cliente guarda token
3. En cada petición protegida → Envía header `Authorization: Bearer {token}`
4. Servidor valida token con Sanctum
5. Si válido → Procesa petición, si no → Retorna 401 Unauthorized

**Ventajas:**
- Stateless (sin sesiones)
- Escalable
- Compatible con SPA y mobile
- Fácil de revocar

---

## Buenas Prácticas Implementadas

1. **Separación de Responsabilidades**: Cada capa tiene una responsabilidad clara
2. **Dependency Injection**: Inyección de dependencias en constructores
3. **Interfaces para Abstracción**: Contratos desacoplan implementación
4. **Validación Centralizada**: Form Requests validan datos
5. **Transformación de Datos**: Resources formatean respuestas
6. **Mappers**: Convierten entre capas
7. **UUIDs**: Claves primarias universales
8. **Soft Deletes**: Eliminación lógica cuando es necesario
9. **Eager Loading**: Previene N+1 queries
10. **Validación de Negocio**: Reglas en repositorios (ej: no eliminar cliente con facturas)

---

## Troubleshooting

### Error: "Unauthenticated"
- Verifica que el token sea válido
- Verifica que el header sea: `Authorization: Bearer {token}`
- Verifica que el token no haya sido revocado

### Error: "The given data was invalid"
- Revisa los campos requeridos
- Verifica que los campos sean del tipo correcto
- Asegúrate de usar camelCase en JSON

### Error: "No se puede eliminar este cliente porque tiene facturas asociadas"
- El cliente tiene facturas relacionadas
- Primero elimina las facturas o usa otra estrategia de negocio

---

## Conclusión

Esta API REST implementa una arquitectura DDD robusta y escalable con:
- **Autenticación segura** mediante Laravel Sanctum
- **Separación clara de capas** (Domain, Application, Infrastructure)
- **Validación exhaustiva** en Form Requests
- **Transformación consistente** con Resources
- **Reglas de negocio** en el dominio y repositorios
- **Documentación completa** de endpoints y ejemplos

La arquitectura permite:
- Fácil mantenimiento
- Testing efectivo
- Escalabilidad
- Cambio de tecnologías sin afectar lógica de negocio
