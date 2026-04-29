
PRAGMA journal_mode = WAL;       
PRAGMA foreign_keys = ON;        

CREATE TABLE IF NOT EXISTS files (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid          TEXT    NOT NULL UNIQUE,          
    original_name TEXT    NOT NULL,                 
    stored_name   TEXT    NOT NULL UNIQUE,          
    mime_type     TEXT    NOT NULL,
    size          INTEGER NOT NULL,                
    sha256        TEXT    NOT NULL,                
    uploaded_at   TEXT    NOT NULL DEFAULT (datetime('now')),
    deleted_at    TEXT    DEFAULT NULL             
);

CREATE INDEX IF NOT EXISTS idx_files_uuid       ON files(uuid);
CREATE INDEX IF NOT EXISTS idx_files_deleted_at ON files(deleted_at);

CREATE TABLE IF NOT EXISTS share_links (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    file_id       INTEGER NOT NULL REFERENCES files(id) ON DELETE CASCADE,
    token         TEXT    NOT NULL UNIQUE,           
    label         TEXT    DEFAULT NULL,              
    download_count INTEGER NOT NULL DEFAULT 0,
    created_at    TEXT    NOT NULL DEFAULT (datetime('now')),
    expires_at    TEXT    DEFAULT NULL               
);

CREATE INDEX IF NOT EXISTS idx_share_token ON share_links(token);

CREATE TABLE IF NOT EXISTS login_attempts (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    ip          TEXT    NOT NULL,
    username    TEXT    NOT NULL,
    success     INTEGER NOT NULL DEFAULT 0,         
    attempted_at TEXT   NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_login_ip ON login_attempts(ip, attempted_at);
