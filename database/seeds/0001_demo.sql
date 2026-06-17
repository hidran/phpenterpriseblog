INSERT INTO users (username, email, password, roletype)
VALUES ('demo', 'demo@example.com', '$2y$10$ll0E9Q4uH71m3UJj3DTzC.4zvhh1V0wKmuKtFnhMOYZAxhPVF5/Hi', 'admin')
ON DUPLICATE KEY UPDATE id = id;

INSERT INTO posts (title, message, datecreated, user_id)
VALUES ('Hello world', 'First post', NOW(), 1)
ON DUPLICATE KEY UPDATE id = id;
