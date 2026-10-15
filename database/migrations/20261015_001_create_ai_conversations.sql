-- MCC-CHANGE-006
-- ذخیره گفتگوهای AI طبق درخواست اولیه کاربر.
-- MariaDB 10.4 compatible.

CREATE TABLE IF NOT EXISTS `ai_conversations` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `session_key` varchar(128) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ai_conversations_session` (`session_key`),
  KEY `idx_ai_conversations_user` (`user_id`),
  CONSTRAINT `fk_ai_conversations_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ai_messages` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint(20) UNSIGNED NOT NULL,
  `role` enum('user','assistant') NOT NULL,
  `content` longtext NOT NULL,
  `model` varchar(150) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ai_messages_conversation_created` (`conversation_id`, `created_at`),
  CONSTRAINT `fk_ai_messages_conversation`
    FOREIGN KEY (`conversation_id`) REFERENCES `ai_conversations` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
