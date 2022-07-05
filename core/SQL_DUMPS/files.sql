CREATE TABLE `files` (
                         `id` int(11) NOT NULL,
                         `name` varchar(255) DEFAULT NULL,
                         `path` text
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

ALTER TABLE `files` ADD PRIMARY KEY (`id`);

ALTER TABLE `files` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;
COMMIT;