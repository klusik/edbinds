CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(64) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','user') NOT NULL DEFAULT 'user',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    last_login_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY users_username_unique (username),
    UNIQUE KEY users_email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS binding_sets (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    owner_user_id INT UNSIGNED NOT NULL,
    title VARCHAR(190) NOT NULL,
    slug VARCHAR(80) NOT NULL,
    description TEXT NULL,
    visibility ENUM('private','public') NOT NULL DEFAULT 'private',
    active_version_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY binding_sets_slug_unique (slug),
    KEY binding_sets_owner_index (owner_user_id),
    KEY binding_sets_visibility_index (visibility),
    CONSTRAINT binding_sets_owner_fk FOREIGN KEY (owner_user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS binding_versions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    binding_set_id INT UNSIGNED NOT NULL,
    version_number INT UNSIGNED NOT NULL,
    uploaded_by_user_id INT UNSIGNED NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_hash CHAR(64) NOT NULL,
    normalized_hash CHAR(64) NOT NULL,
    xml_text MEDIUMTEXT NOT NULL,
    parsed_json LONGTEXT NOT NULL,
    stats_json TEXT NOT NULL,
    change_note TEXT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY binding_versions_set_number_unique (binding_set_id, version_number),
    KEY binding_versions_file_hash_index (file_hash),
    KEY binding_versions_normalized_hash_index (normalized_hash),
    CONSTRAINT binding_versions_set_fk FOREIGN KEY (binding_set_id) REFERENCES binding_sets (id) ON DELETE CASCADE,
    CONSTRAINT binding_versions_user_fk FOREIGN KEY (uploaded_by_user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE binding_sets
    ADD CONSTRAINT binding_sets_active_version_fk
    FOREIGN KEY (active_version_id) REFERENCES binding_versions (id) ON DELETE SET NULL;
