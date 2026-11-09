-- Elegance Locks - database schema and initial data
-- Import through phpMyAdmin (Import tab) or: mysql -u root < database/elegance_locks.sql

CREATE DATABASE IF NOT EXISTS elegance_locks
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE elegance_locks;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS email_verifications;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- Users: one table for all three roles. The role column decides access.
CREATE TABLE users (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name      VARCHAR(120) NOT NULL,
    email          VARCHAR(190) NOT NULL,
    phone          VARCHAR(20)  NOT NULL,
    password_hash  VARCHAR(255) NOT NULL,
    role           ENUM('customer', 'salonist', 'admin') NOT NULL DEFAULT 'customer',
    specialty      VARCHAR(120) NULL,
    is_active      TINYINT(1)   NOT NULL DEFAULT 1,
    email_verified_at DATETIME  NULL,
    last_login_at  DATETIME     NULL,
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role),
    KEY idx_users_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Services offered by the salon.
CREATE TABLE services (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name             VARCHAR(120)  NOT NULL,
    description      VARCHAR(255)  NULL,
    price            DECIMAL(10,2) NOT NULL,
    duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 60,
    is_active        TINYINT(1)    NOT NULL DEFAULT 1,
    created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_services_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One-time passwords for email verification. Only a hash of the OTP is stored.
CREATE TABLE email_verifications (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id       INT UNSIGNED NOT NULL,
    otp_hash      VARCHAR(255) NOT NULL,
    expires_at    DATETIME     NOT NULL,
    attempts      TINYINT UNSIGNED NOT NULL DEFAULT 0,
    last_sent_at  DATETIME     NOT NULL,
    used_at       DATETIME     NULL,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_verif_user (user_id, used_at),
    CONSTRAINT fk_verif_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Appointments. salonist_id stays NULL until an administrator assigns someone.
CREATE TABLE appointments (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_id      INT UNSIGNED NOT NULL,
    service_id       INT UNSIGNED NOT NULL,
    salonist_id      INT UNSIGNED NULL,
    appointment_date DATE NOT NULL,
    start_time       TIME NOT NULL,
    end_time         TIME NOT NULL,
    notes            VARCHAR(500) NULL,
    status           ENUM('pending', 'confirmed', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_appt_customer (customer_id, appointment_date),
    KEY idx_appt_salonist (salonist_id, appointment_date, start_time),
    KEY idx_appt_status (status),
    KEY idx_appt_date (appointment_date, start_time),
    CONSTRAINT fk_appt_customer FOREIGN KEY (customer_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_appt_service  FOREIGN KEY (service_id)  REFERENCES services (id),
    CONSTRAINT fk_appt_salonist FOREIGN KEY (salonist_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Failed login attempts, used to slow down password guessing.
CREATE TABLE login_attempts (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email        VARCHAR(190) NOT NULL,
    ip_address   VARCHAR(45)  NOT NULL,
    attempted_at DATETIME     NOT NULL,
    PRIMARY KEY (id),
    KEY idx_login_email (email, attempted_at),
    KEY idx_login_ip (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial salon services (prices in KES)
INSERT INTO services (name, description, price, duration_minutes) VALUES
('Wash and Blow-dry',     'Shampoo, conditioning treatment and a smooth blow-dry finish.',      1200.00,  45),
('Haircut and Styling',   'Consultation, precision cut and styling.',                            1500.00,  60),
('Box Braids',            'Neat box braids in your preferred length and size.',                  4500.00, 240),
('Cornrows',              'Classic or patterned cornrows, neat and long lasting.',               2000.00, 120),
('Silk Press',            'Heat-styled straight finish with protective care.',                   3000.00,  90),
('Hair Coloring',         'Professional color application with after-care advice.',              4000.00, 120),
('Deep Conditioning',     'Intensive moisture treatment for dry or damaged hair.',               1800.00,  45),
('Dreadlock Maintenance', 'Retwisting, tidying and scalp care for locs.',                        2500.00,  90),
('Wig Installation',      'Secure, natural-looking wig fitting and styling.',                    3500.00,  90),
('Bridal Hair Styling',   'Elegant bridal styling with a trial consultation.',                   8000.00, 150);
