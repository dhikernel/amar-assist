-- Banco isolado para a suíte PHPUnit, criado no primeiro boot do container MySQL.
-- Evita que `php artisan test` derrube os dados de desenvolvimento.
CREATE DATABASE IF NOT EXISTS `amar_assist_test`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

GRANT ALL PRIVILEGES ON `amar_assist_test`.* TO 'amar'@'%';
FLUSH PRIVILEGES;
