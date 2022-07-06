CREATE TABLE `threads` (
                           `id` int(11) NOT NULL,
                           `active` varchar(1) NOT NULL DEFAULT 'N',
                           `in_progress` varchar(1) NOT NULL DEFAULT 'N',
                           `priority` int(11) DEFAULT NULL,
                           `execution_time` varchar(255) DEFAULT NULL,
                           `attempts` int(11) NOT NULL,
                           `attempts_limit` int(11) DEFAULT '1',
                           `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                           `date_updated` datetime DEFAULT NULL,
                           `class` varchar(255) DEFAULT NULL,
                           `method` varchar(255) DEFAULT NULL,
                           `params` text,
                           `status` varchar(255) DEFAULT NULL,
                           `response` text
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
ALTER TABLE `threads` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `id` (`id`);
ALTER TABLE `threads` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78989;