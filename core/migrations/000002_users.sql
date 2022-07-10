CREATE TABLE if not exists `users`
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
) ENGINE = MyISAM
  DEFAULT CHARSET = utf8;

INSERT INTO `users` (`id`, `login`, `password`, `name`, `email`, `image_id`, `token`, `last_active`, `date_created`, `date_updated`)
VALUES (1, 'admin', 'c7e82c3d2bed998ea1f4c1aca09c282b', 'Роман Гринько', 'rsgrinko@yandex.ru', 18, 'RG1819-1000-77EB-8F7A-9B112FB69D3A0',
        '1657051585', '2022-06-17 16:18:56', NULL),
       (2, 'a.kirilcev', 'c7e82c3d2bed998ea1f4c1aca09c282b', 'Александр Кирильцев', 'a.kirilcev@it-tula.ru', 20,
        'RG17DE-1000-26FC-837F-8F9C47E643970', '1655471459', '2022-06-17 16:18:56', NULL),
       (3, 'system', '', 'Система', 'info@it-stories.ru', NULL, 'RG17DE-1000-2702-816F-97E3877DD6650', '1651673702', '2022-06-17 16:18:56', NULL);

ALTER TABLE `users`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `users`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 25;
COMMIT;
