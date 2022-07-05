CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` text,
  `description` text
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'Админисистратор', 'Группа, имеющая доступ ко всему'),
(2, 'Пользователи', 'Базовая группа для простых пользователей'),

ALTER TABLE `roles` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `id` (`id`);
COMMIT;