-- Migration 001: initial schema
-- SQLite — all timestamps stored as ISO-8601 UTC strings

PRAGMA journal_mode = WAL;       -- better concurrency
PRAGMA foreign_keys = ON;        -- enforce FK constraints

-- ─── Files ──────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS files (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid          TEXT    NOT NULL UNIQUE,          -- UUID v4, used in URLs & paths
    original_name TEXT    NOT NULL,                 -- original filename from client
    stored_name   TEXT    NOT NULL UNIQUE,          -- uuid + ext, name on disk
    mime_type     TEXT    NOT NULL,
    size          INTEGER NOT NULL,                 -- bytes
    sha256        TEXT    NOT NULL,                 -- hex digest for integrity checks
    uploaded_at   TEXT    NOT NULL DEFAULT (datetime('now')),
    deleted_at    TEXT    DEFAULT NULL              -- soft-delete
);

CREATE INDEX IF NOT EXISTS idx_files_uuid       ON files(uuid);
CREATE INDEX IF NOT EXISTS idx_files_deleted_at ON files(deleted_at);

-- ─── Share links ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS share_links (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    file_id       INTEGER NOT NULL REFERENCES files(id) ON DELETE CASCADE,
    token         TEXT    NOT NULL UNIQUE,           -- 32-byte random hex
    label         TEXT    DEFAULT NULL,              -- optional human-readable label
    download_count INTEGER NOT NULL DEFAULT 0,
    created_at    TEXT    NOT NULL DEFAULT (datetime('now')),
    expires_at    TEXT    DEFAULT NULL               -- NULL = never expires (v1 default)
);

CREATE INDEX IF NOT EXISTS idx_share_token ON share_links(token);

-- ─── Login audit log ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS login_attempts (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    ip          TEXT    NOT NULL,
    username    TEXT    NOT NULL,
    success     INTEGER NOT NULL DEFAULT 0,          -- 0 = fail, 1 = success
    attempted_at TEXT   NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_login_ip ON login_attempts(ip, attempted_at);
