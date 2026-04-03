-- Migration 010: Add schema_name column to hotels table
-- Stores the PostgreSQL schema name for per-hotel data isolation

ALTER TABLE public.hotels ADD COLUMN IF NOT EXISTS schema_name VARCHAR(100) NULL;
CREATE INDEX IF NOT EXISTS idx_hotels_schema_name ON public.hotels (schema_name);
