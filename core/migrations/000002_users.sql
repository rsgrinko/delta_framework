CREATE TABLE if not exists `d_users`
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
       (2, 'demo', '7c607172d24d7237579acfdeffe373e2', 'Demo user', 'demo@it-stories.ru', 20,
        'RG17DE-1000-26FC-837F-8F9C47E643970', '1655471459', '2022-06-17 16:18:56', NULL),
       (3, 'system', '', 'Система', 'info@it-stories.ru', NULL, 'RG17DE-1000-2702-816F-97E3877DD6650', '1651673702', '2022-06-17 16:18:56', NULL);

ALTER TABLE `d_users`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `d_users`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 25;
COMMIT;