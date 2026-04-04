-- Migration 012: Create establishment_features table for feature toggles
-- Run via Supabase SQL Editor

CREATE TABLE IF NOT EXISTS public.establishment_features (
    id SERIAL PRIMARY KEY,
    hotel_id INT NOT NULL,
    feature_key VARCHAR(50) NOT NULL,
    is_enabled BOOLEAN DEFAULT TRUE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (hotel_id, feature_key),
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_establishment_features_hotel ON public.establishment_features (hotel_id);
CREATE INDEX IF NOT EXISTS idx_establishment_features_key ON public.establishment_features (feature_key);
