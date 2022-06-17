CREATE TABLE `users` (
                         `id` int(11) NOT NULL,
                         `login` varchar(100) NOT NULL,
                         `password` varchar(100) NOT NULL,
                         `name` varchar(150) NOT NULL,
                         `email` text,
                         `image` varchar(255) NOT NULL,
                         `token` text,
                         `last_active` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `users` (`id`, `login`, `password`, `name`, `email`, `image`, `token`, `last_active`)
VALUES
    (1, 'admin', '5321ef98bd96cccf03f50979fdd8c893', 'Роман Гринько', 'rsgrinko@yandex.ru', 'https://avatars.githubusercontent.com/u/38320330?s=150', 'RG17DE-1000-26FC-837F-8F9C47E643960', '1649970260'),
    (2, 'a.kirilcev', 'c7e82c3d2bed998ea1f4c1aca09c282b',  'Александр Кирильцев', 'a.kirilcev@it-tula.ru', 'https://i.pinimg.com/736x/36/3a/65/363a65ba6f997b11904654d0531a8166.jpg', 'RG17DE-1000-26FC-837F-8F9C47E643970', '1649505403'),
    (3, 'system', '', 'Система', 'info@it-stories.ru', '/uploads/users/system.jpg', 'RG17DE-1000-2702-816F-97E3877DD6650', '1');

ALTER TABLE `users`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `users`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;
COMMIT;