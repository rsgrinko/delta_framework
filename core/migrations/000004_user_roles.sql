CREATE TABLE if not exists `d_user_roles` (
                              `id` int(11) NOT NULL,
                              `user_id` int(11) NOT NULL,
                              `role_id` int(11) NOT NULL
) ENGINE=innodb DEFAULT CHARSET=utf8;

INSERT INTO `d_user_roles` (`id`, `user_id`, `role_id`) VALUES (1, 1, 1),
ALTER TABLE `d_user_roles` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `id` (`id`);
ALTER TABLE `d_user_roles` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;