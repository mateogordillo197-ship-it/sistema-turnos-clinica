USE clinica_turnos;

INSERT INTO especialidades (id, nombre, prefijo) VALUES
(1, 'Medicina General', 'M'),
(2, 'Odontología', 'O'),
(3, 'Enfermería', 'E');

INSERT INTO consultorios (id, nombre, especialidad_id) VALUES
(1, 'Consultorio 1', 1),
(2, 'Consultorio 2', 1),
(3, 'Consultorio 3 - Odontología', 2),
(4, 'Tópico de Enfermería', 3);

INSERT INTO recepcionistas (id, nombre, usuario, password_hash, consultorio_id) VALUES
(1, 'Ana Torres', 'atorres', '$2y$10$ejemploHashSeguridad1234567890', 1),
(2, 'Carlos Méndez', 'cmendez', '$2y$10$ejemploHashSeguridad1234567890', 2);

INSERT INTO turnos (codigo, especialidad_id, consultorio_id, recepcionista_id, estado, creado_en, llamado_en) VALUES
('M-001', 1, 2, 2, 'atendiendo', DATE_SUB(NOW(), INTERVAL 15 MINUTE), DATE_SUB(NOW(), INTERVAL 3 MINUTE)),
('M-002', 1, NULL, NULL, 'espera', DATE_SUB(NOW(), INTERVAL 12 MINUTE), NULL),
('O-001', 2, NULL, NULL, 'espera', DATE_SUB(NOW(), INTERVAL 10 MINUTE), NULL),
('M-003', 1, NULL, NULL, 'espera', DATE_SUB(NOW(), INTERVAL 6 MINUTE), NULL);