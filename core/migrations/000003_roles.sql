CREATE TABLE if not exists `d_roles` (
                         `id` int(11) NOT NULL,
                         `name` text,
                         `description` text
) ENGINE=innodb DEFAULT CHARSET=utf8;


INSERT INTO `d_roles` (`id`, `name`, `description`) VALUES
                                                      (1, 'Админисистратор', 'Группа, имеющая доступ ко всему'),
                                                      (2, 'Пользователи', 'Базовая группа для простых пользователей'),

ALTER TABLE `d_roles` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `id` (`id`);