SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username  VARCHAR(64) NOT NULL,
    email     VARCHAR(128) NOT NULL,
    password  VARCHAR(255) NOT NULL,
    roletype  ENUM('admin','editor','user') NOT NULL DEFAULT 'user',
    PRIMARY KEY (id),
    UNIQUE KEY uniq_users_email (email),
    KEY idx_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS posts (
    id           INT NOT NULL AUTO_INCREMENT,
    title        VARCHAR(255) NOT NULL,
    message      TEXT NOT NULL,
    datecreated  DATETIME NOT NULL,
    user_id      INT NOT NULL,
    PRIMARY KEY (id),
    KEY idx_posts_title (title),
    CONSTRAINT fk_posts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS post_comments (
    id           INT NOT NULL AUTO_INCREMENT,
    post_id      INT NOT NULL,
    user_id      INT DEFAULT NULL,
    comment      TEXT NOT NULL,
    email        VARCHAR(128) NOT NULL,
    datecreated  DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_comments_post (post_id),
    CONSTRAINT fk_comments_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
