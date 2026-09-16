PRAGMA foreign_keys = ON;
CREATE TABLE IF NOT EXISTS usuarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL CHECK(length(nome) BETWEEN 2 AND 100),
    login TEXT NOT NULL UNIQUE COLLATE NOCASE,
    senha TEXT NOT NULL,
    perfil TEXT NOT NULL CHECK(perfil IN ('professor', 'aluno')),
    nota REAL CHECK(nota BETWEEN 0 AND 10)
);
