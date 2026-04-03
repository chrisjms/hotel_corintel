-- Add establishment type column to hotels table
-- Supports: hotel (default), pizzeria, and future types
ALTER TABLE public.hotels ADD COLUMN IF NOT EXISTS type VARCHAR(50) NOT NULL DEFAULT 'hotel';
