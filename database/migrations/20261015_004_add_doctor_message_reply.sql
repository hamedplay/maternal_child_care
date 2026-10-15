-- Phase 7 follow-up: persist doctor/admin response without editing prior migration.

ALTER TABLE doctor_messages
    ADD COLUMN reply_text TEXT DEFAULT NULL AFTER status,
    ADD COLUMN replied_at DATETIME DEFAULT NULL AFTER reply_text;
