CREATE TABLE if not exists `user_roles` (
                              `id` int(11) NOT NULL,
                              `user_id` int(11) NOT NULL,
                              `role_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `user_roles` (`id`, `user_id`, `role_id`) VALUES (1, 1, 1),
ALTER TABLE `user_roles` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `id` (`id`);
ALTER TABLE `user_roles` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;