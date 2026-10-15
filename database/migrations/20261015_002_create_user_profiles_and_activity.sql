-- Phase 6: richer user profile + authenticated activity logging.
-- MariaDB 10.4 compatible.

CREATE TABLE IF NOT EXISTS user_profiles (
    user_id INT(10) UNSIGNED NOT NULL,
    stage ENUM('planning','pregnant','postpartum','child_care') NOT NULL DEFAULT 'planning',
    birth_date DATE DEFAULT NULL,
    pregnancy_due_date DATE DEFAULT NULL,
    child_name VARCHAR(100) DEFAULT NULL,
    child_birth_date DATE DEFAULT NULL,
    child_gender ENUM('boy','girl','unknown') NOT NULL DEFAULT 'unknown',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    CONSTRAINT fk_user_profiles_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_activity_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT(10) UNSIGNED NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    request_method VARCHAR(10) DEFAULT NULL,
    request_path VARCHAR(255) DEFAULT NULL,
    entity_type VARCHAR(50) DEFAULT NULL,
    entity_id VARCHAR(100) DEFAULT NULL,
    metadata_json LONGTEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_user_activity_user_created (user_id, created_at),
    KEY idx_user_activity_action_created (action_type, created_at),
    CONSTRAINT fk_user_activity_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
