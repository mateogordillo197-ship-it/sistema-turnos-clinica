USE clinica_turnos;

-- 1. Índice compuesto para acelerar la cola de espera FIFO y el conteo por especialidad
CREATE INDEX idx_turnos_estado_esp_fecha
  ON turnos (estado, especialidad_id, creado_en);

-- 2. Índice compuesto para acelerar la consulta de turnos en atención en pantalla de sala
CREATE INDEX idx_turnos_estado_llamado
  ON turnos (estado, llamado_en);