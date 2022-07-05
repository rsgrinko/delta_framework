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
COMMIT;