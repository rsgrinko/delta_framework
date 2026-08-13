-- Этап 0 архитектурного рефакторинга: чистка данных.
--
-- Выполнять на базе приложения (та, что задана в config.local.php как DB_NAME).
-- Phinx-миграцией не оформлено сознательно: окружения в phinx.php указывают на другие базы,
-- чем использует само приложение, и до унификации конфигурации на Этапе 2 запуск миграции
-- ушёл бы не в ту базу.
--
-- Запуск:
--   mysql -u <DB_USER> -p <DB_NAME> < db/cleanup/2026-08-14_stage0.sql

-- 1. Сниппеты кода, сохранённые удалённой веб-консолью admin/phpcmd.php.
--    Перед удалением можно посмотреть, что именно удалится:
--    SELECT id, user_id, LEFT(value, 120) FROM d_user_meta WHERE name = 'code';
DELETE FROM `d_user_meta` WHERE `name` = 'code';

-- 2. Пустые таблицы-остатки экспериментов на MyISAM.
DROP TABLE IF EXISTS `test__table`;
DROP TABLE IF EXISTS `test__table2`;
