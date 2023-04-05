CREATE TABLE if not exists `d_files` (
                         `id` int(11) NOT NULL,
                         `name` varchar(255) DEFAULT NULL,
                         `path` text
) ENGINE=innodb DEFAULT CHARSET=utf8;
ALTER TABLE `d_files` ADD PRIMARY KEY (`id`);
ALTER TABLE `d_files` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;