-- Repara títulos seed gravados como "?" após import sem SET NAMES utf8mb4.
-- mysql --default-character-set=utf8mb4 -uroot portal_aulas_ete < backend/config/repair_utf8_titulos.sql

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

UPDATE turmas
SET titulo = 'Técnico em Desenvolvimento de Sistemas 2024'
WHERE codigo = 'TDS-2024';

UPDATE turmas
SET titulo = 'Técnico em Desenvolvimento de Sistemas 2025'
WHERE codigo = 'TDS-2025';

UPDATE disciplinas
SET titulo = 'Programação Orientada a Objetos'
WHERE codigo = 'POO';

UPDATE disciplinas
SET titulo = 'Programação Web'
WHERE codigo = 'WEB';

UPDATE disciplinas
SET titulo = 'Lógica de Programação'
WHERE codigo = 'LOG';
