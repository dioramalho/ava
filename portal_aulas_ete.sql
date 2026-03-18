-- Portal de Aulas — TDS
-- Script de criação e carga inicial para MySQL / MariaDB.
-- Compatível com importação manual via phpMyAdmin.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS portal_aulas_ete
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE portal_aulas_ete;

DROP TABLE IF EXISTS documentos;
DROP TABLE IF EXISTS recados;
DROP TABLE IF EXISTS materias;
DROP TABLE IF EXISTS alunos;
DROP TABLE IF EXISTS turmas;
DROP TABLE IF EXISTS usuarios;

CREATE TABLE usuarios (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(120) NOT NULL,
  login VARCHAR(60) NOT NULL,
  senha VARCHAR(255) NOT NULL,
  perfil ENUM('professor', 'aluno', 'admin') NOT NULL DEFAULT 'professor',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_usuarios_login (login)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE turmas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(80) NOT NULL,
  ano_letivo YEAR NOT NULL,
  turno VARCHAR(30) NOT NULL,
  descricao VARCHAR(150) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE alunos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  turma_id INT UNSIGNED NOT NULL,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(120) NOT NULL,
  matricula VARCHAR(30) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_alunos_matricula (matricula),
  KEY idx_alunos_turma (turma_id),
  CONSTRAINT fk_alunos_turma FOREIGN KEY (turma_id) REFERENCES turmas (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE materias (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  turma_id INT UNSIGNED NOT NULL,
  nome VARCHAR(120) NOT NULL,
  descricao TEXT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_materias_turma (turma_id),
  CONSTRAINT fk_materias_turma FOREIGN KEY (turma_id) REFERENCES turmas (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE recados (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  turma_id INT UNSIGNED NOT NULL,
  titulo VARCHAR(150) NOT NULL,
  mensagem TEXT NOT NULL,
  data_publicacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_recados_turma (turma_id),
  CONSTRAINT fk_recados_turma FOREIGN KEY (turma_id) REFERENCES turmas (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE documentos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  turma_id INT UNSIGNED NOT NULL,
  materia_id INT UNSIGNED NOT NULL,
  titulo VARCHAR(180) NOT NULL,
  nome_arquivo VARCHAR(180) NOT NULL,
  caminho_arquivo VARCHAR(255) NOT NULL,
  descricao VARCHAR(255) DEFAULT NULL,
  data_publicacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_documentos_turma (turma_id),
  KEY idx_documentos_materia (materia_id),
  CONSTRAINT fk_documentos_turma FOREIGN KEY (turma_id) REFERENCES turmas (id),
  CONSTRAINT fk_documentos_materia FOREIGN KEY (materia_id) REFERENCES materias (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO usuarios (id, nome, login, senha, perfil) VALUES
  (1, 'Professor Responsável', 'professor', '1234', 'professor');

INSERT INTO turmas (id, nome, ano_letivo, turno, descricao) VALUES
  (1, 'TDS 2024', 2024, 'Noite', 'Turma do curso Técnico em Desenvolvimento de Sistemas 2024'),
  (2, 'TDS 2025', 2025, 'Tarde', 'Turma do curso Técnico em Desenvolvimento de Sistemas 2025');

INSERT INTO alunos (id, turma_id, nome, email, matricula) VALUES
  (1, 1, 'Ana Lima', 'ana.lima@ete.com', 'TDS2024-001'),
  (2, 1, 'Carlos Souza', 'carlos.souza@ete.com', 'TDS2024-002'),
  (3, 2, 'João Silva', 'joao.silva@ete.com', 'TDS2025-001'),
  (4, 2, 'Marina Alves', 'marina.alves@ete.com', 'TDS2025-002');

INSERT INTO materias (id, turma_id, nome, descricao) VALUES
  (1, 1, 'Algoritmos', 'Introdução à lógica de programação e Portugol.'),
  (2, 1, 'Banco de Dados', 'Modelagem, DER e SQL básico.'),
  (3, 2, 'Desenvolvimento Web', 'Conteúdos iniciais de HTML, CSS e Bootstrap.');

INSERT INTO recados (id, turma_id, titulo, mensagem, data_publicacao) VALUES
  (1, 1, 'Prova de Algoritmos', 'A prova de Algoritmos acontecerá na próxima semana.', '2026-03-18 08:00:00'),
  (2, 1, 'Atividade de Banco de Dados', 'Entregar a atividade de Banco de Dados até sexta-feira.', '2026-03-18 09:00:00'),
  (3, 2, 'Novo material de HTML', 'Novo material de HTML disponível para a turma TDS 2025.', '2026-03-18 10:00:00');

INSERT INTO documentos (id, turma_id, materia_id, titulo, nome_arquivo, caminho_arquivo, descricao, data_publicacao) VALUES
  (1, 1, 1, 'Aula 01 — Introdução', 'aula-01-introducao.pdf', 'assets/pdf/aula-01-introducao.pdf', 'Material introdutório de Algoritmos.', '2026-03-18 08:30:00'),
  (2, 1, 1, 'Aula 02 — Fluxogramas', 'aula-02-fluxogramas.pdf', 'assets/pdf/aula-02-fluxogramas.pdf', 'Conceitos básicos de fluxogramas.', '2026-03-18 08:45:00'),
  (3, 1, 1, 'Aula 03 — Portugol', 'aula-03-portugol.pdf', 'assets/pdf/aula-03-portugol.pdf', 'Primeiros passos com Portugol.', '2026-03-18 09:00:00'),
  (4, 1, 2, 'Aula 01 — Modelagem', 'aula-01-modelagem.pdf', 'assets/pdf/aula-01-modelagem.pdf', 'Fundamentos de modelagem de dados.', '2026-03-18 09:15:00'),
  (5, 1, 2, 'Aula 02 — DER', 'aula-02-der.pdf', 'assets/pdf/aula-02-der.pdf', 'Relacionamentos e DER.', '2026-03-18 09:30:00'),
  (6, 1, 2, 'Aula 03 — SQL Básico', 'aula-03-sql-basico.pdf', 'assets/pdf/aula-03-sql-basico.pdf', 'Comandos iniciais em SQL.', '2026-03-18 09:45:00'),
  (7, 2, 3, 'Aula 01 — HTML', 'aula-01-html.pdf', 'assets/pdf/aula-01-html.pdf', 'Estrutura básica de páginas HTML.', '2026-03-18 10:00:00'),
  (8, 2, 3, 'Aula 02 — CSS', 'aula-02-css.pdf', 'assets/pdf/aula-02-css.pdf', 'Estilização inicial com CSS.', '2026-03-18 10:15:00'),
  (9, 2, 3, 'Aula 03 — Bootstrap', 'aula-03-bootstrap.pdf', 'assets/pdf/aula-03-bootstrap.pdf', 'Uso de componentes Bootstrap 5.', '2026-03-18 10:30:00');
