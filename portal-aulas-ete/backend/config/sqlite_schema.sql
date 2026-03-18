PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS usuarios (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  nome TEXT NOT NULL,
  login TEXT NOT NULL UNIQUE,
  senha TEXT NOT NULL,
  perfil TEXT NOT NULL DEFAULT 'professor',
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS turmas (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  nome TEXT NOT NULL,
  ano_letivo INTEGER NOT NULL,
  turno TEXT NOT NULL,
  descricao TEXT DEFAULT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS alunos (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  turma_id INTEGER NOT NULL,
  nome TEXT NOT NULL,
  email TEXT NOT NULL,
  matricula TEXT NOT NULL UNIQUE,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (turma_id) REFERENCES turmas (id)
);

CREATE TABLE IF NOT EXISTS materias (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  turma_id INTEGER NOT NULL,
  nome TEXT NOT NULL,
  descricao TEXT DEFAULT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (turma_id) REFERENCES turmas (id)
);

CREATE TABLE IF NOT EXISTS recados (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  turma_id INTEGER NOT NULL,
  titulo TEXT NOT NULL,
  mensagem TEXT NOT NULL,
  data_publicacao TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (turma_id) REFERENCES turmas (id)
);

CREATE TABLE IF NOT EXISTS documentos (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  turma_id INTEGER NOT NULL,
  materia_id INTEGER NOT NULL,
  titulo TEXT NOT NULL,
  nome_arquivo TEXT NOT NULL,
  caminho_arquivo TEXT NOT NULL,
  descricao TEXT DEFAULT NULL,
  data_publicacao TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (turma_id) REFERENCES turmas (id),
  FOREIGN KEY (materia_id) REFERENCES materias (id)
);

INSERT INTO usuarios (id, nome, login, senha, perfil, created_at)
SELECT 1, 'Professor Responsável', 'professor', '7110eda4d09e062aa5e4a390b0a572ac0d2c0220', 'professor', CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE id = 1);

INSERT INTO turmas (id, nome, ano_letivo, turno, descricao, created_at)
SELECT 1, 'TDS 2024', 2024, 'Noite', 'Turma do curso Técnico em Desenvolvimento de Sistemas 2024', CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM turmas WHERE id = 1);

INSERT INTO turmas (id, nome, ano_letivo, turno, descricao, created_at)
SELECT 2, 'TDS 2025', 2025, 'Tarde', 'Turma do curso Técnico em Desenvolvimento de Sistemas 2025', CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM turmas WHERE id = 2);

INSERT INTO alunos (id, turma_id, nome, email, matricula, created_at)
SELECT 1, 1, 'Ana Lima', 'ana.lima@ete.com', 'TDS2024-001', CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM alunos WHERE id = 1);

INSERT INTO alunos (id, turma_id, nome, email, matricula, created_at)
SELECT 2, 1, 'Carlos Souza', 'carlos.souza@ete.com', 'TDS2024-002', CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM alunos WHERE id = 2);

INSERT INTO alunos (id, turma_id, nome, email, matricula, created_at)
SELECT 3, 2, 'João Silva', 'joao.silva@ete.com', 'TDS2025-001', CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM alunos WHERE id = 3);

INSERT INTO alunos (id, turma_id, nome, email, matricula, created_at)
SELECT 4, 2, 'Marina Alves', 'marina.alves@ete.com', 'TDS2025-002', CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM alunos WHERE id = 4);

INSERT INTO materias (id, turma_id, nome, descricao, created_at)
SELECT 1, 1, 'Algoritmos', 'Introdução à lógica de programação e Portugol.', CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM materias WHERE id = 1);

INSERT INTO materias (id, turma_id, nome, descricao, created_at)
SELECT 2, 1, 'Banco de Dados', 'Modelagem, DER e SQL básico.', CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM materias WHERE id = 2);

INSERT INTO materias (id, turma_id, nome, descricao, created_at)
SELECT 3, 2, 'Desenvolvimento Web', 'Conteúdos iniciais de HTML, CSS e Bootstrap.', CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM materias WHERE id = 3);

INSERT INTO recados (id, turma_id, titulo, mensagem, data_publicacao)
SELECT 1, 1, 'Prova de Algoritmos', 'A prova de Algoritmos acontecerá na próxima semana.', '2026-03-18 08:00:00'
WHERE NOT EXISTS (SELECT 1 FROM recados WHERE id = 1);

INSERT INTO recados (id, turma_id, titulo, mensagem, data_publicacao)
SELECT 2, 1, 'Atividade de Banco de Dados', 'Entregar a atividade de Banco de Dados até sexta-feira.', '2026-03-18 09:00:00'
WHERE NOT EXISTS (SELECT 1 FROM recados WHERE id = 2);

INSERT INTO recados (id, turma_id, titulo, mensagem, data_publicacao)
SELECT 3, 2, 'Novo material de HTML', 'Novo material de HTML disponível para a turma TDS 2025.', '2026-03-18 10:00:00'
WHERE NOT EXISTS (SELECT 1 FROM recados WHERE id = 3);

INSERT INTO documentos (id, turma_id, materia_id, titulo, nome_arquivo, caminho_arquivo, descricao, data_publicacao)
SELECT 1, 1, 1, 'Aula 01 — Introdução', 'aula-01-introducao.pdf', 'assets/pdf/aula-01-introducao.pdf', 'Material introdutório de Algoritmos.', '2026-03-18 08:30:00'
WHERE NOT EXISTS (SELECT 1 FROM documentos WHERE id = 1);

INSERT INTO documentos (id, turma_id, materia_id, titulo, nome_arquivo, caminho_arquivo, descricao, data_publicacao)
SELECT 2, 1, 1, 'Aula 02 — Fluxogramas', 'aula-02-fluxogramas.pdf', 'assets/pdf/aula-02-fluxogramas.pdf', 'Conceitos básicos de fluxogramas.', '2026-03-18 08:45:00'
WHERE NOT EXISTS (SELECT 1 FROM documentos WHERE id = 2);

INSERT INTO documentos (id, turma_id, materia_id, titulo, nome_arquivo, caminho_arquivo, descricao, data_publicacao)
SELECT 3, 1, 1, 'Aula 03 — Portugol', 'aula-03-portugol.pdf', 'assets/pdf/aula-03-portugol.pdf', 'Primeiros passos com Portugol.', '2026-03-18 09:00:00'
WHERE NOT EXISTS (SELECT 1 FROM documentos WHERE id = 3);

INSERT INTO documentos (id, turma_id, materia_id, titulo, nome_arquivo, caminho_arquivo, descricao, data_publicacao)
SELECT 4, 1, 2, 'Aula 01 — Modelagem', 'aula-01-modelagem.pdf', 'assets/pdf/aula-01-modelagem.pdf', 'Fundamentos de modelagem de dados.', '2026-03-18 09:15:00'
WHERE NOT EXISTS (SELECT 1 FROM documentos WHERE id = 4);

INSERT INTO documentos (id, turma_id, materia_id, titulo, nome_arquivo, caminho_arquivo, descricao, data_publicacao)
SELECT 5, 1, 2, 'Aula 02 — DER', 'aula-02-der.pdf', 'assets/pdf/aula-02-der.pdf', 'Relacionamentos e DER.', '2026-03-18 09:30:00'
WHERE NOT EXISTS (SELECT 1 FROM documentos WHERE id = 5);

INSERT INTO documentos (id, turma_id, materia_id, titulo, nome_arquivo, caminho_arquivo, descricao, data_publicacao)
SELECT 6, 1, 2, 'Aula 03 — SQL Básico', 'aula-03-sql-basico.pdf', 'assets/pdf/aula-03-sql-basico.pdf', 'Comandos iniciais em SQL.', '2026-03-18 09:45:00'
WHERE NOT EXISTS (SELECT 1 FROM documentos WHERE id = 6);

INSERT INTO documentos (id, turma_id, materia_id, titulo, nome_arquivo, caminho_arquivo, descricao, data_publicacao)
SELECT 7, 2, 3, 'Aula 01 — HTML', 'aula-01-html.pdf', 'assets/pdf/aula-01-html.pdf', 'Estrutura básica de páginas HTML.', '2026-03-18 10:00:00'
WHERE NOT EXISTS (SELECT 1 FROM documentos WHERE id = 7);

INSERT INTO documentos (id, turma_id, materia_id, titulo, nome_arquivo, caminho_arquivo, descricao, data_publicacao)
SELECT 8, 2, 3, 'Aula 02 — CSS', 'aula-02-css.pdf', 'assets/pdf/aula-02-css.pdf', 'Estilização inicial com CSS.', '2026-03-18 10:15:00'
WHERE NOT EXISTS (SELECT 1 FROM documentos WHERE id = 8);

INSERT INTO documentos (id, turma_id, materia_id, titulo, nome_arquivo, caminho_arquivo, descricao, data_publicacao)
SELECT 9, 2, 3, 'Aula 03 — Bootstrap', 'aula-03-bootstrap.pdf', 'assets/pdf/aula-03-bootstrap.pdf', 'Uso de componentes Bootstrap 5.', '2026-03-18 10:30:00'
WHERE NOT EXISTS (SELECT 1 FROM documentos WHERE id = 9);
