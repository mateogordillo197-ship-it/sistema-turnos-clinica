# Informe Técnico - Fase 3 (ABPr): Resolución de Concurrencia y Rendimiento

## 1. Problema 1: Duplicidad de asignación de turnos (Condición de carrera)
- **Síntoma:** En hora pico (8:00 AM), dos recepcionistas presionan «Llamar siguiente» simultáneamente y el sistema asigna el mismo paciente a dos consultorios distintos.
- **Diagnóstico de causa raíz:** En la versión inicial, la lectura del siguiente turno disponible (`SELECT ... LIMIT 1`) y su actualización (`UPDATE turnos SET estado = 'atendiendo'`) se ejecutaban como sentencias independientes sin aislamiento transaccional. En concurrencia alta, ambas peticiones leen el mismo registro en estado `'espera'` antes de que la primera confirme la escritura.
- **Solución implementada:**
  1. Encapsulamiento de la operación dentro de una transacción ACID (`$pdo->beginTransaction()` y `$pdo->commit()`).
  2. Uso de bloqueo pesimista a nivel de fila mediante la cláusula **`SELECT ... FOR UPDATE`** sobre el motor InnoDB. Cuando el Recepcionista A lee el turno `M-002`, InnoDB bloquea esa fila exacta; el Recepcionista B queda en espera unos milisegundos hasta que A hace `COMMIT` y pasa directamente al siguiente turno (`M-003`), garantizando exclusión mutua sin duplicados.

## 2. Problema 2: Lentitud en pantalla de sala con más de 500 registros (8 segundos)
- **Síntoma:** La pantalla de espera tarda hasta 8 segundos en refrescarse cuando la tabla `turnos` supera los 500 registros acumulados.
- **Diagnóstico de causa raíz:**
  1. Ausencia de índices en las columnas de filtrado (`estado`, `especialidad_id`) y ordenamiento (`creado_en`, `llamado_en`), lo que obligaba a MySQL a ejecutar un recorrido completo de tabla (*Full Table Scan*) y ordenamiento temporal (*Filesort*) en cada petición `GET`.
  2. Uso de la función `DATE(creado_en) = CURDATE()` al calcular el correlativo diario, lo que invalidaba el uso de índices sobre la columna temporal.
- **Solución implementada:**
  1. Creación de los índices compuestos `idx_turnos_estado_esp_fecha (estado, especialidad_id, creado_en)` e `idx_turnos_estado_llamado (estado, llamado_en)`.
  2. Refactorización del filtro diario a un rango indexable (`creado_en >= CURDATE() AND creado_en < DATE_ADD(CURDATE(), INTERVAL 1 DAY)`).
  3. **Resultado verificable:** El plan de ejecución (`EXPLAIN`) pasa de `type: ALL` (*Full Table Scan*) a `type: ref` usando el índice compuesto, reduciendo el tiempo de consulta de ~8 s a menos de 15 ms.