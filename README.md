# Sistema de Gestión de Turnos — Clínica Universitaria

Aplicación web para la gestión de colas de atención médica, odontológica y de enfermería en un centro universitario, desarrollada aplicando secuencialmente **Design Thinking**, **Aprendizaje Basado en Proyectos (ABP)** y **Aprendizaje Basado en Problemas (ABPr)**.

---

## 1. Metodologías Aplicadas

### Fase 1: Design Thinking (UX/UI)
* **Perfiles analizados:** Adulto mayor (necesidad de alto contraste y lectura clara), estudiante universitario (visibilidad de turnos previos en fila) y recepcionista en hora pico (asignación en un solo clic).
* **Entregables:** Prototipo navegable de 3 pantallas en Figma (Tótem, Sala de espera y Recepción) y análisis de puntos de dolor en `docs/design-thinking/entregables-fase1.md`.

### Fase 2: Aprendizaje Basado en Proyectos (ABP — MVP)
* **Frontend:** HTML5, CSS3 (accesibilidad de alto contraste) y JavaScript (`Fetch API`) en `frontend/`.
* **Backend:** API REST en PHP (PDO) con manejo estructurado de errores HTTP/JSON en `backend/`.
* **Base de datos:** Modelo relacional normalizado en MySQL (`InnoDB`) con scripts de creación y datos semilla en `database/` y diagrama ER en `docs/arquitectura/DIAGRAMA ER.png`.

### Fase 3: Aprendizaje Basado en Problemas (ABPr — Concurrencia y Rendimiento)
* **Condición de carrera resuelta:** Encapsulamiento en transacciones ACID (`beginTransaction` / `commit`) y bloqueo pesimista a nivel de fila (`SELECT ... FOR UPDATE`) al llamar turnos, evitando asignaciones duplicadas en hora pico.
* **Optimización SQL:** Creación de índices compuestos (`idx_turnos_estado_esp_fecha` e `idx_turnos_estado_llamado`) y filtros por rango de fecha indexables en `database/optimizacion_abpr.sql`.
* **Informe técnico:** Documentación de diagnóstico y solución en `docs/abpr/informe-tecnico-abpr.md`.

---

## 2. Estructura del Proyecto

```text
sistema-turnos-clinica/
├── backend/
│   ├── api/turnos.php             # Endpoints REST (GET estado, POST crear/llamar/finalizar)
│   └── config/db.php              # Conexión PDO a MySQL
├── database/
│   ├── schema.sql                 # Estructura de tablas y llaves foráneas
│   ├── seed.sql                   # Datos iniciales de prueba
│   └── optimizacion_abpr.sql      # Índices compuestos de rendimiento
├── docs/
│   ├── abpr/informe-tecnico-abpr.md
│   ├── arquitectura/DIAGRAMA ER.png
│   └── design-thinking/entregables-fase1.md
├── frontend/
│   ├── assets/css/styles.css
│   ├── assets/js/api.js
│   ├── totem.html                 # Pantalla 1: Toma de turno
│   ├── sala.html                  # Pantalla 2: Monitor de espera
│   └── recepcion.html             # Pantalla 3: Panel del recepcionista
└── README.md
```

---

## 3. Instalación y Ejecución Local (EasyPHP / XAMPP)

1. Clonar el repositorio dentro de la carpeta raíz del servidor web (`www/` o `htdocs/`):
   ```bash
   git clone [https://github.com/mateogordillo197-ship-it/sistema-turnos-clinica.git](https://github.com/mateogordillo197-ship-it/sistema-turnos-clinica.git)
   ```
2. Iniciar **Apache** y **MySQL**.
3. Abrir **phpMyAdmin** e importar en orden:
   * `database/schema.sql`
   * `database/seed.sql`
   * `database/optimizacion_abpr.sql`
4. Verificar credenciales de conexión en `backend/config/db.php` (por defecto usuario `root` sin contraseña).
5. Abrir en el navegador:
   * **Tótem:** `http://127.0.0.1/sistema-turnos-clinica/frontend/totem.html`
   * **Sala de espera:** `http://127.0.0.1/sistema-turnos-clinica/frontend/sala.html`
   * **Recepción:** `http://127.0.0.1/sistema-turnos-clinica/frontend/recepcion.html`