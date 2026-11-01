PRAGMA foreign_keys = OFF;

DROP TABLE IF EXISTS trabalho_eventos;
DROP TABLE IF EXISTS trabalho_alunos;
DROP TABLE IF EXISTS trabalhos;
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

PRAGMA foreign_keys = ON;

CREATE TABLE turmas (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  codigo TEXT NOT NULL UNIQUE,
  titulo TEXT NOT NULL,
  arquivada INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE disciplinas (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  codigo TEXT NOT NULL UNIQUE,
  titulo TEXT NOT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE usuarios (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  nome TEXT NOT NULL,
  login TEXT NOT NULL UNIQUE,
  senha TEXT NOT NULL,
  perfil TEXT NOT NULL DEFAULT 'aluno',
  email TEXT DEFAULT NULL UNIQUE,
  celular TEXT DEFAULT NULL,
  status TEXT NOT NULL DEFAULT 'aprovado',
  turma_id INTEGER DEFAULT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (turma_id) REFERENCES turmas (id)
);

CREATE TABLE aulas (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  titulo TEXT NOT NULL,
  turma_id INTEGER NOT NULL,
  disciplina_id INTEGER NOT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (turma_id) REFERENCES turmas (id),
  FOREIGN KEY (disciplina_id) REFERENCES disciplinas (id)
);

CREATE TABLE aula_videos (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  aula_id INTEGER NOT NULL,
  url TEXT NOT NULL,
  ordem INTEGER NOT NULL DEFAULT 0,
  FOREIGN KEY (aula_id) REFERENCES aulas (id) ON DELETE CASCADE
);

CREATE TABLE aula_arquivos (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  aula_id INTEGER NOT NULL,
  nome_original TEXT NOT NULL,
  caminho_arquivo TEXT NOT NULL,
  extensao TEXT NOT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (aula_id) REFERENCES aulas (id) ON DELETE CASCADE
);

CREATE TABLE trabalhos (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  titulo TEXT NOT NULL,
  descricao TEXT NOT NULL,
  data_entrega TEXT NOT NULL,
  turma_id INTEGER NOT NULL,
  aula_id INTEGER NOT NULL,
  arquivo_nome TEXT DEFAULT NULL,
  arquivo_caminho TEXT DEFAULT NULL,
  arquivo_extensao TEXT DEFAULT NULL,
  video_url TEXT DEFAULT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (turma_id) REFERENCES turmas (id),
  FOREIGN KEY (aula_id) REFERENCES aulas (id)
);

CREATE TABLE trabalho_alunos (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  trabalho_id INTEGER NOT NULL,
  aluno_id INTEGER NOT NULL,
  status TEXT NOT NULL DEFAULT 'pendente',
  data_entrega_vigente TEXT NOT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (trabalho_id, aluno_id),
  FOREIGN KEY (trabalho_id) REFERENCES trabalhos (id) ON DELETE CASCADE,
  FOREIGN KEY (aluno_id) REFERENCES usuarios (id)
);

CREATE TABLE trabalho_eventos (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  trabalho_aluno_id INTEGER NOT NULL,
  tipo TEXT NOT NULL,
  autor_id INTEGER DEFAULT NULL,
  autor_perfil TEXT NOT NULL,
  texto TEXT DEFAULT NULL,
  arquivo_nome TEXT DEFAULT NULL,
  arquivo_caminho TEXT DEFAULT NULL,
  arquivo_extensao TEXT DEFAULT NULL,
  data_entrega TEXT DEFAULT NULL,
  status TEXT DEFAULT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (trabalho_aluno_id) REFERENCES trabalho_alunos (id) ON DELETE CASCADE
);

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
