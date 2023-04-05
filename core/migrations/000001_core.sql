CREATE TABLE `d_users`
(
    `id`           int(11)      NOT NULL,
    `login`        varchar(100) NOT NULL,
    `password`     varchar(100) NOT NULL,
    `name`         varchar(150) NOT NULL,
    `email`        text,
    `image_id`     int(11)               DEFAULT NULL,
    `token`        text,
    `last_active`  varchar(100) NOT NULL,
    `date_created` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_updated` datetime              DEFAULT NULL
) ENGINE = innodb
  DEFAULT CHARSET = utf8;
INSERT INTO `d_users` (`id`, `login`, `password`, `name`, `email`, `image_id`, `token`, `last_active`, `date_created`, `date_updated`)
VALUES (1, 'admin', '7c607172d24d7237579acfdeffe373e2', 'Роман Гринько', 'rsgrinko@yandex.ru', 18, 'RG1819-1000-77EB-8F7A-9B112FB69D3A0',
        '1657051585', '2022-06-17 16:18:56', NULL),
       (2, 'demo', '7c607172d24d7237579acfdeffe373e2', 'Demo', 'demo@it-stories.ru', 20,
        'RG17DE-1000-26FC-837F-8F9C47E643970', '1655471459', '2022-06-17 16:18:56', NULL),
       (3, 'system', '', 'Система', 'info@it-stories.ru', NULL, 'RG17DE-1000-2702-816F-97E3877DD6650', '1651673702', '2022-06-17 16:18:56', NULL);
ALTER TABLE `d_users` ADD PRIMARY KEY (`id`);
ALTER TABLE `d_users` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT = 25;


CREATE TABLE `roles` (
                         `id` int(11) NOT NULL,
                         `name` text,
                         `description` text
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


INSERT INTO `roles` (`id`, `name`, `description`) VALUES
                                                      (1, 'Админисистратор', 'Группа, имеющая доступ ко всему'),
                                                      (2, 'Пользователи', 'Базовая группа для простых пользователей');

ALTER TABLE `roles` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `id` (`id`);


CREATE TABLE `user_roles` (
                              `id` int(11) NOT NULL,
                              `user_id` int(11) NOT NULL,
                              `role_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
INSERT INTO `user_roles` (`id`, `user_id`, `role_id`) VALUES (1, 1, 1);
ALTER TABLE `user_roles` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `id` (`id`);
ALTER TABLE `user_roles` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;


CREATE TABLE `threads` (
                           `id` int(11) NOT NULL,
                           `active` varchar(1) NOT NULL DEFAULT 'N',
                           `in_progress` varchar(1) NOT NULL DEFAULT 'N',
                           `executed` varchar(1) NOT NULL DEFAULT 'N',
                           `execution_time` varchar(255) DEFAULT NULL,
                           `attempts` int(11) NOT NULL,
                           `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                           `date_updated` datetime DEFAULT NULL,
                           `class` varchar(255) DEFAULT NULL,
                           `method` varchar(255) DEFAULT NULL,
                           `params` text,
                           `status` varchar(255) DEFAULT NULL,
                           `response` text
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
ALTER TABLE `threads` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `id` (`id`);
ALTER TABLE `threads` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17923;


CREATE TABLE `threads_history` (
                                   `id` int(11) NOT NULL,
                                   `task_id` int(11) DEFAULT NULL,
                                   `execution_time` varchar(255) DEFAULT NULL,
                                   `attempts` int(11) NOT NULL,
                                   `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                   `date_updated` datetime DEFAULT NULL,
                                   `class` varchar(255) DEFAULT NULL,
                                   `method` varchar(255) DEFAULT NULL,
                                   `params` text,
                                   `status` varchar(255) DEFAULT NULL,
                                   `response` text
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
ALTER TABLE `threads_history` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `id` (`id`);
ALTER TABLE `threads_history` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7946;


CREATE TABLE `files` (
                         `id` int(11) NOT NULL,
                         `name` varchar(255) DEFAULT NULL,
                         `path` text
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
ALTER TABLE `files` ADD PRIMARY KEY (`id`);
ALTER TABLE `files` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

COMMIT;



