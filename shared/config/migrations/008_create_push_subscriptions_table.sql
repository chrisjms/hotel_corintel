-- Migration: Create push subscriptions table for notifications (PostgreSQL)
-- Store push notification subscriptions per room

CREATE TABLE IF NOT EXISTS push_subscriptions (
    id SERIAL PRIMARY KEY,
    room_id INT NOT NULL,
    endpoint TEXT NOT NULL,
    p256dh_key VARCHAR(255) NOT NULL,
    auth_key VARCHAR(255) NOT NULL,
    user_agent VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_used_at TIMESTAMP DEFAULT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,

    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_push_subscriptions_room_id ON push_subscriptions (room_id);
CREATE INDEX IF NOT EXISTS idx_push_subscriptions_is_active ON push_subscriptions (is_active);

-- Add room_id column to room_service_orders if not exists (for order history)
ALTER TABLE room_service_orders ADD COLUMN IF NOT EXISTS room_id INT DEFAULT NULL;
CREATE INDEX IF NOT EXISTS idx_orders_room_id ON room_service_orders (room_id);
