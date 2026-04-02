-- Migration: Add persistent authentication tokens table
-- This enables indefinite session persistence (no automatic logout)
-- Run this on existing installations to enable the feature (PostgreSQL)

CREATE TABLE IF NOT EXISTS persistent_tokens (
    id SERIAL PRIMARY KEY,
    admin_id INT NOT NULL,
    token_hash VARCHAR(64) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_persistent_tokens_admin ON persistent_tokens (admin_id);
CREATE INDEX IF NOT EXISTS idx_persistent_tokens_token ON persistent_tokens (token_hash);
