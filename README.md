# 📖 Manual de Usuario y Documentación Técnica API - El Juego de la Mosca

---

## 📋 Índice

1. [Descripción General](#1-descripción-general)
2. [Arquitectura y Estructura del Código](#2-arquitectura-y-estructura-del-código)
3. [Flujo de Ejecución e Infraestructura](#3-flujo-de-ejecución-e-infraestructura)
4. [Configuración del Entorno (`.env`)](#4-configuración-del-entorno-env)
5. [Documentación de la Colección de Postman](#5-documentación-de-la-colección-de-postman)
   - [Módulo de Usuarios](#módulo-de-usuarios)
   - [Módulo del Juego de la Mosca](#módulo-del-juego-de-la-mosca)
6. [Reglas de Negocio del Juego](#6-reglas-de-negocio-del-juego)
7. [Manejo de Errores y Respuestas HTTP](#7-manejo-de-errores-y-respuestas-http)

---

## 1. Descripción General

Este proyecto consiste en una **API RESTful stateless** construida en **PHP nativo (sin frameworks)** y respaldada por una base de datos **MySQL**. El sistema combina dos módulos principales:

1. **Gestión de Usuarios (CRUD):** Registro, autenticación (`login`), recuperación de credenciales mediante correo electrónico, modificación de perfiles y eliminación de usuarios orientada a roles (`ADMIN` y `JUGADOR`).
2. **El Juego de la Mosca:** Un juego de lógica en el que los jugadores intentan golpear a una mosca oculta en un tablero lineal de $N$ casillas. La mosca reacciona cambiando de lugar si el golpe es adyacente ("casi le das").

---

## 2. Arquitectura y Estructura del Código

El sistema sigue un patrón modular en capas inspirado en **MVC (Modelo-Vista-Controlador)** junto con el patrón **DAO (Data Access Object)** para el desacoplamiento con la base de datos.

### Diagrama de Directorios y Archivos

```text
├── .env                       # Archivo de configuración global (credenciales DB y correo)
├── index.php                  # Enrutador principal y punto de entrada único (Front Controller)
├── database/
│   └── database.php           # Gestor de conexión MySQLi y carga de variables .env
├── controllers/
│   ├── auxiliar.php           # Métodos de utilidad (respuestas JSON y extracción de usuario_id)
│   ├── ControladorUsuario.php # Lógica de negocio de registro, login y gestión de usuarios
│   └── ControladorMosca.php   # Lógica del juego (crear tablero, golpear, rendirse)
└── models/
    ├── persona.php            # Entidad Modelo de Usuario
    ├── usuarioDAO.php         # Acceso a datos para usuarios (Consultas SQL preparadas)
    ├── tablero.php            # Modelo de la lógica interna del tablero y posición de la mosca
    └── moscaDAO.php           # Acceso a datos para el estado del juego/partidas en MySQL
```

---

## 3. Flujo de Ejecución e Infraestructura

### Enrutamiento (`index.php`)

Toda petición entrante es procesada por `index.php`, que actúa como **Front Controller**:

1. Configura el encabezado de respuesta `Content-Type: application/json`.
2. Lee el archivo `.env` mediante `database.php`.
3. Analiza el método HTTP (`$_SERVER['REQUEST_METHOD']`) y descompone la URL con `parse_url()` y `explode()`.
4. Decodifica el cuerpo de la petición codificado en JSON (`php://input`).
5. Redirige la ejecución al método estático correspondiente del controlador según el método HTTP y la ruta.

---

## 4. Configuración del Entorno (`.env`)

El archivo `.env` define las variables clave para la conexión MySQLi.

```ini
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=crud_usuarios_mosca
USER_DB=root
PASS_DB=
EMAIL=tu_correo@gmail.com
SMTP_PASSWORD=tu_contrasena_de_aplicacion
```

---

## 5. Documentación de la Colección de Postman

La colección de Postman **`CRUD_USUARIOS_MOSCA_SERGIO_ESCOBAR`** organiza las pruebas en dos carpetas principales.

---

### Módulo de Usuarios

#### 1. Registrar Usuario - OK

- **Método:** `POST`
- **Ruta:** `http://127.0.0.1:8080/register`
- **Body (raw JSON):**

```json
{
  "user": "jugador2",
  "email": "jugador2@gmail.com",
  "pass": "1234",
  "edad": 22
}
```

- **Respuesta esperada:** `201 Created`

---

#### 2. Registrar Admin - OK

- **Método:** `POST`
- **Ruta:** `http://127.0.0.1:8080/register-admin`
- **Body (raw JSON):**

```json
{
  "user": "admin1",
  "email": "admin1@gmail.com",
  "pass": "1234",
  "edad": 22
}
```

- **Respuesta esperada:** `201 Created`

---

#### 3. Login Usuario / Admin

- **Método:** `POST`
- **Ruta:** `http://localhost:8080/login`
- **Body (raw JSON):**

```json
{
  "user": "jugador1",
  "pass": "1234"
}
```

- **Respuesta esperada:** `200 OK` con la información del usuario autenticado.

---

#### 4. Recuperar Contraseña

- **Método:** `POST`
- **Ruta:** `http://localhost:8080/recuperar`
- **Body (raw JSON):**

```json
{
  "email": "jugador1@gmail.com"
}
```

- **Respuesta esperada:** `200 OK` informando del envío de clave temporal.

---

#### 5. Listar Usuarios (Restringido ADMIN)

- **Método:** `GET`
- **Ruta:** `http://localhost:8080/usuarios`
- **Body (raw JSON):**

```json
{
  "usuario_id": 7
}
```

- **Respuestas esperada:**
  - `200 OK` si `usuario_id` pertenece a un `ADMIN`.
  - `403 Forbidden` si `usuario_id` pertenece a un `JUGADOR`.

---

#### 6. Actualizar Usuario

- **Método:** `PUT`
- **Ruta:** `http://localhost:8080/usuarios/{id}`
- **Body (raw JSON):**

```json
{
  "user": "nuevo_nombre",
  "edad": 25
}
```

- **Respuesta esperada:** `200 OK`

---

#### 7. Eliminar Usuario (Solo ADMIN)

- **Método:** `DELETE`
- **Ruta:** `http://localhost:8080/usuarios/{id}`
- **Body (raw JSON):**

```json
{
  "usuario_id": 7
}
```

- **Respuesta esperada:** `200 OK` o `403 Forbidden`.

---

### Módulo del Juego de la Mosca

#### 1. Crear Tablero

- **Método:** `POST`
- **Ruta:** `http://localhost:8080/crear`
- **Body (raw JSON):**

```json
{
  "usuario_id": 8,
  "tamano": 10,
  "intentos": 5
}
```

- **Respuestas esperadas:**
  - `201 Created`: Tablero inicializado correctamente.
  - `403 Forbidden`: Si el `usuario_id` es `ADMIN` (los administradores no juegan).
  - `409 Conflict`: Si el usuario ya tiene una partida activa.

---

#### 2. Jugar Golpe / Manotazo

- **Método:** `POST`
- **Ruta:** `http://localhost:8080/jugar`
- **Body (raw JSON):**

```json
{
  "usuario_id": 7,
  "casilla": 4
}
```

- **Respuestas esperadas:**
  - `200 OK`: Devuelve el resultado (`cazada`, `casi le das`, o `ni se entera`) y los `intentos_restantes`.
  - `422 Unprocessable Entity`: Casilla fuera del rango $[0, \text{tamaño}-1]$.
  - `400 Bad Request`: Sin partida activa.

---

#### 3. Ver Intentos Restantes

- **Método:** `GET`
- **Ruta:** `http://localhost:8080/intentos`
- **Body (raw JSON):**

```json
{
  "usuario_id": 7
}
```

- **Respuesta esperada:** `200 OK` con los intentos restantes.

---

#### 4. Rendirse

- **Método:** `PUT`
- **Ruta:** `http://localhost:8080/rendirse`
- **Body (raw JSON):**

```json
{
  "usuario_id": 2
}
```

- **Respuesta esperada:** `200 OK` revelando la casilla exacta donde estaba la mosca (`pos_mosca`).

---

## 6. Reglas de Negocio del Juego

1. **Restricción de Roles:** Los usuarios con el rol `ADMIN` no pueden crear ni jugar partidas.
2. **Unicidad de Partida Activa:** Un jugador solo puede tener una única partida activa (`activo = 1`) simultáneamente.
3. **Mecánica del Manotazo:**
   - **`cazada`** ($| \text{casilla} - \text{pos\_mosca} | = 0$): La mosca es eliminada. La partida finaliza con victoria.
   - **`casi le das`** ($| \text{casilla} - \text{pos\_mosca} | = 1$): El golpe cae en una casilla adyacente. La mosca se asusta y revolotea a una nueva casilla aleatoria ($rand(0, tamano-1)$).
   - **`ni se entera`** ($| \text{casilla} - \text{pos\_mosca} | > 1$): El golpe cae lejos. La mosca permanece en su casilla.
4. **Finalización:** La partida se desactiva (`activo = 0`) si se caza la mosca, si los intentos llegan a $0$, o si el jugador ejecuta `/rendirse`.

---

## 7. Manejo de Errores y Respuestas HTTP

| Código HTTP | Significado | Escenario de Aplicación |
| :---: | :--- | :--- |
| **`200 OK`** | Petición exitosa | Lectura de datos, manotazo procesado, actualización correcta. |
| **`201 Created`** | Recurso creado | Registro de usuario o inicio de nueva partida. |
| **`400 Bad Request`** | Parámetros inválidos | Falta la casilla en `/jugar` o falta el email en `/recuperar`. |
| **`401 Unauthorized`** | No identificado | Falta el parámetro `usuario_id` obligatorio en el Body JSON. |
| **`403 Forbidden`** | Acceso denegado | Jugador intentando listar todos los usuarios o Admin intentando jugar. |
| **`404 Not Found`** | Ruta inexistente | Endpoint no definido en el selector `switch($metodo)`. |
| **`405 Method Not Allowed`** | Método no permitido | Uso de verbos HTTP no soportados (ej. `PATCH`). |
| **`409 Conflict`** | Conflicto de estado | Intento de crear un tablero teniendo ya una partida activa. |
| **`422 Unprocessable Entity`** | Error de rango | Casilla enviada fuera de los límites numéricos del tablero. |
| **`500 Internal Server Error`** | Error no controlado | Excepción en la base de datos o fallo crítico en el servidor. |
