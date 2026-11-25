CREATE DATABASE IF NOT EXISTS clinica_turnos
  CHARACTER SET utf8
  COLLATE utf8_unicode_ci;

USE clinica_turnos;

CREATE TABLE especialidades (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL,
  prefijo VARCHAR(3) NOT NULL UNIQUE,
  activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE consultorios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) NOT NULL,
  especialidad_id INT UNSIGNED NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_consultorio_especialidad
    FOREIGN KEY (especialidad_id) REFERENCES especialidades(id)
) ENGINE=InnoDB;

CREATE TABLE recepcionistas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  usuario VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  consultorio_id INT UNSIGNED NOT NULL,
  CONSTRAINT fk_recepcionista_consultorio
    FOREIGN KEY (consultorio_id) REFERENCES consultorios(id)
) ENGINE=InnoDB;

CREATE TABLE turnos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(10) NOT NULL,
  especialidad_id INT UNSIGNED NOT NULL,
  consultorio_id INT UNSIGNED NULL,
  recepcionista_id INT UNSIGNED NULL,
  estado ENUM('espera', 'atendiendo', 'finalizado', 'cancelado') NOT NULL DEFAULT 'espera',
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  llamado_en DATETIME NULL,
  finalizado_en DATETIME NULL,
  CONSTRAINT fk_turno_especialidad FOREIGN KEY (especialidad_id) REFERENCES especialidades(id),
  CONSTRAINT fk_turno_consultorio FOREIGN KEY (consultorio_id) REFERENCES consultorios(id),
  CONSTRAINT fk_turno_recepcionista FOREIGN KEY (recepcionista_id) REFERENCES recepcionistas(id)
) ENGINE=InnoDB;