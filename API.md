# DermaMED — API Reference

> **Base URL:** `http://localhost:8080/api`
> **Auth:** JWT Bearer Token (header `Authorization: Bearer <token>`)

---

## Autenticación

| Method | Endpoint    | Auth | Descripción          |
| ------ | ----------- | ---- | -------------------- |
| `POST` | `/register` | ❌   | Registro de usuario  |
| `POST` | `/login`    | ❌   | Login (devuelve JWT) |
| `GET`  | `/me`       | ✅   | Usuario autenticado  |
| `POST` | `/logout`   | ✅   | Invalidar token      |
| `POST` | `/refresh`  | ✅   | Renovar token        |

### POST `/login`

```json
// Request
{ "email": "director@dermamed.com", "password": "password" }

// Response 200
{
  "success": true,
  "message": "Login successful",
  "user": { "id": 1, "name": "Director Médico", "email": "...", "roles": [...] },
  "token": "eyJ0eXAiOiJKV1Q..."
}
```

---

## Recursos CRUD

Todos los endpoints protegidos siguen el mismo patrón de paginación:

### Parámetros de paginación (query params)

| Param      | Tipo | Default | Descripción      |
| ---------- | ---- | ------- | ---------------- |
| `cantidad` | int  | 10      | Items por página |
| `pagina`   | int  | 1       | Número de página |

### Formato de respuesta paginada

```json
{
  "data": [...],
  "current_page": 1,
  "total_pages": 5,
  "total_registros": 48
}
```

---

## Usuarios

| Method   | Endpoint      | Descripción        |
| -------- | ------------- | ------------------ |
| `GET`    | `/users`      | Listar usuarios    |
| `GET`    | `/users/{id}` | Ver usuario        |
| `PUT`    | `/users/{id}` | Actualizar usuario |
| `DELETE` | `/users/{id}` | Eliminar usuario   |

**Filtros (GET `/users`):** `name`, `email`, `role_id`, `is_active`

---

## Pacientes

| Method   | Endpoint         | Descripción         |
| -------- | ---------------- | ------------------- |
| `GET`    | `/patients`      | Listar pacientes    |
| `POST`   | `/patients`      | Crear paciente      |
| `GET`    | `/patients/{id}` | Ver paciente        |
| `PUT`    | `/patients/{id}` | Actualizar paciente |
| `DELETE` | `/patients/{id}` | Eliminar paciente   |

**Filtros:** `first_name`, `last_name`, `cuit`

**Campos:** `first_name`_, `last_name`_, `cuit`, `email`, `phone`, `birth_date`, `address`, `insurance_provider`

---

## Servicios (Tratamientos)

| Method   | Endpoint         | Descripción         |
| -------- | ---------------- | ------------------- |
| `GET`    | `/services`      | Listar servicios    |
| `POST`   | `/services`      | Crear servicio      |
| `GET`    | `/services/{id}` | Ver servicio        |
| `PUT`    | `/services/{id}` | Actualizar servicio |
| `DELETE` | `/services/{id}` | Eliminar servicio   |

**Campos:** `name`_, `description`, `price`_, `duration_minutes`\*

---

## Turnos (Appointments)

| Method   | Endpoint             | Descripción                        |
| -------- | -------------------- | ---------------------------------- |
| `GET`    | `/appointments`      | Listar turnos                      |
| `POST`   | `/appointments`      | Crear turno                        |
| `GET`    | `/appointments/{id}` | Ver turno (incluye medical record) |
| `PUT`    | `/appointments/{id}` | Actualizar turno                   |
| `DELETE` | `/appointments/{id}` | Eliminar turno                     |

**Filtros:** `patient_id`, `doctor_id`, `service_id`, `status`, `date_from`, `date_to`

**Campos:** `patient_id`_, `doctor_id`_, `service_id`_, `start_time`_, `end_time`\*, `status`, `reserve_channel`, `notes`

**Status values:** `pending`, `confirmed`, `cancelled`, `attended`

---

## Historia Clínica

| Method   | Endpoint                | Descripción         |
| -------- | ----------------------- | ------------------- |
| `GET`    | `/medical-records`      | Listar registros    |
| `POST`   | `/medical-records`      | Crear registro      |
| `GET`    | `/medical-records/{id}` | Ver registro        |
| `PUT`    | `/medical-records/{id}` | Actualizar registro |
| `DELETE` | `/medical-records/{id}` | Eliminar registro   |

**Campos:** `patient_id`_, `doctor_id`_, `appointment_id`, `date`_, `content`_

### Adjuntos de Evolución (Fotos)

| Method   | Endpoint                                                  | Auth           | Descripción                     |
| -------- | --------------------------------------------------------- | -------------- | ------------------------------- |
| `POST`   | `/medical-records/{id}/attachments`                       | `role:doctor`  | Subir imágenes (`multipart`)    |
| `GET`    | `/medical-records/{id}/attachments/{att_id}`              | `auth:api`     | Ver imagen inline (no descarga) |
| `DELETE` | `/medical-records/{id}/attachments/{att_id}`              | `role:doctor`  | Eliminar adjunto                |

> ⚠️ `POST` espera `multipart/form-data` con campo `attachments[]` (array de imágenes). No setear `Content-Type` manualmente.
> ⚠️ `GET` responde con `Content-Disposition: inline` — el navegador renderiza la imagen directamente con el token JWT en el header.

---

## Disponibilidad de Doctores

| Method   | Endpoint                      | Descripción             |
| -------- | ----------------------------- | ----------------------- |
| `GET`    | `/doctor-availabilities`      | Listar disponibilidades |
| `POST`   | `/doctor-availabilities`      | Crear disponibilidad    |
| `GET`    | `/doctor-availabilities/{id}` | Ver disponibilidad      |
| `PUT`    | `/doctor-availabilities/{id}` | Actualizar              |
| `DELETE` | `/doctor-availabilities/{id}` | Eliminar                |

**Campos:** `doctor_id`_, `day_of_week`_ (0-6), `start_time`_, `end_time`_

---

## Productos

| Method   | Endpoint         | Descripción         |
| -------- | ---------------- | ------------------- |
| `GET`    | `/products`      | Listar productos    |
| `POST`   | `/products`      | Crear producto      |
| `GET`    | `/products/{id}` | Ver producto        |
| `PUT`    | `/products/{id}` | Actualizar producto |
| `DELETE` | `/products/{id}` | Eliminar producto   |

**Campos:** `name`_, `description`, `price`_, `stock`_, `min_stock`_

---

## Movimientos de Stock

| Method | Endpoint                | Descripción        |
| ------ | ----------------------- | ------------------ |
| `GET`  | `/stock-movements`      | Listar movimientos |
| `POST` | `/stock-movements`      | Crear movimiento   |
| `GET`  | `/stock-movements/{id}` | Ver movimiento     |

> ⚠️ Inmutables: no se pueden editar ni eliminar.

**Campos:** `product_id`_, `user_id`_, `type`_ (`in`/`out`/`adjustment`), `quantity`_, `reason`

---

## Facturación

### Facturas

| Method   | Endpoint         | Descripción        |
| -------- | ---------------- | ------------------ |
| `GET`    | `/invoices`      | Listar facturas    |
| `POST`   | `/invoices`      | Crear factura      |
| `GET`    | `/invoices/{id}` | Ver factura        |
| `PUT`    | `/invoices/{id}` | Actualizar factura |
| `DELETE` | `/invoices/{id}` | Eliminar factura   |

### Items de Factura (nested)

| Method   | Endpoint                           | Descripción     |
| -------- | ---------------------------------- | --------------- |
| `POST`   | `/invoices/{invoice}/items`        | Agregar item    |
| `GET`    | `/invoices/{invoice}/items/{item}` | Ver item        |
| `PUT`    | `/invoices/{invoice}/items/{item}` | Actualizar item |
| `DELETE` | `/invoices/{invoice}/items/{item}` | Eliminar item   |

### Pagos de Factura (nested)

| Method   | Endpoint                                 | Descripción    |
| -------- | ---------------------------------------- | -------------- |
| `POST`   | `/invoices/{invoice}/payments`           | Registrar pago |
| `GET`    | `/invoices/{invoice}/payments/{payment}` | Ver pago       |
| `DELETE` | `/invoices/{invoice}/payments/{payment}` | Anular pago    |

---

## Códigos de Error

| Código | Significado                              |
| ------ | ---------------------------------------- |
| `200`  | Éxito                                    |
| `201`  | Creado exitosamente                      |
| `401`  | No autenticado (token inválido/expirado) |
| `403`  | No autorizado (sin permisos)             |
| `404`  | Recurso no encontrado                    |
| `422`  | Error de validación                      |
| `500`  | Error interno del servidor               |
