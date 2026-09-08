-- Portal de Aulas — ETE (jornada do professor v1)
-- MySQL / MariaDB. Reimportar no phpMyAdmin ou via mysql CLI.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS portal_aulas_ete
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE portal_aulas_ete;

DROP TABLE IF EXISTS aula_arquivos;
DROP TABLE IF EXISTS aula_videos;
DROP TABLE IF EXISTS aulas;
DROP TABLE IF EXISTS documentos;
DROP TABLE IF EXISTS recados;
DROP TABLE IF EXISTS materias;
DROP TABLE IF EXISTS alunos;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS disciplinas;
DROP TABLE IF EXISTS turmas;

CREATE TABLE turmas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo VARCHAR(40) NOT NULL,
  titulo VARCHAR(150) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_turmas_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE disciplinas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo VARCHAR(40) NOT NULL,
  titulo VARCHAR(150) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_disciplinas_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE usuarios (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(120) NOT NULL,
  login VARCHAR(120) NOT NULL,
  senha VARCHAR(255) NOT NULL,
  perfil ENUM('professor', 'aluno') NOT NULL DEFAULT 'aluno',
  email VARCHAR(120) DEFAULT NULL,
  celular VARCHAR(20) DEFAULT NULL,
  status ENUM('pendente', 'aprovado', 'recusado') NOT NULL DEFAULT 'aprovado',
  turma_id INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_usuarios_login (login),
  UNIQUE KEY uk_usuarios_email (email),
  KEY idx_usuarios_turma (turma_id),
  KEY idx_usuarios_status (perfil, status),
  CONSTRAINT fk_usuarios_turma FOREIGN KEY (turma_id) REFERENCES turmas (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE aulas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  titulo VARCHAR(180) NOT NULL,
  turma_id INT UNSIGNED NOT NULL,
  disciplina_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_aulas_turma (turma_id),
  KEY idx_aulas_disciplina (disciplina_id),
  CONSTRAINT fk_aulas_turma FOREIGN KEY (turma_id) REFERENCES turmas (id),
  CONSTRAINT fk_aulas_disciplina FOREIGN KEY (disciplina_id) REFERENCES disciplinas (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE aula_videos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  aula_id INT UNSIGNED NOT NULL,
  url VARCHAR(500) NOT NULL,
  ordem SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_aula_videos_aula (aula_id),
  CONSTRAINT fk_aula_videos_aula FOREIGN KEY (aula_id) REFERENCES aulas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE aula_arquivos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  aula_id INT UNSIGNED NOT NULL,
  nome_original VARCHAR(180) NOT NULL,
  caminho_arquivo VARCHAR(255) NOT NULL,
  extensao VARCHAR(10) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_aula_arquivos_aula (aula_id),
  CONSTRAINT fk_aula_arquivos_aula FOREIGN KEY (aula_id) REFERENCES aulas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO turmas (id, codigo, titulo) VALUES
  (1, 'TDS-2024', 'Técnico em Desenvolvimento de Sistemas 2024'),
  (2, 'TDS-2025', 'Técnico em Desenvolvimento de Sistemas 2025');

INSERT INTO disciplinas (id, codigo, titulo) VALUES
  (1, 'POO', 'Programação Orientada a Objetos'),
  (2, 'WEB', 'Programação Web'),
  (3, 'BD', 'Banco de Dados'),
  (4, 'LOG', 'Lógica de Programação');

INSERT INTO usuarios (id, nome, login, senha, perfil, status) VALUES
  (1, 'Professor Responsável', 'professor', '7110eda4d09e062aa5e4a390b0a572ac0d2c0220', 'professor', 'aprovado');
