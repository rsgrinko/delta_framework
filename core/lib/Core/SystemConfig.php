<?php

    /**
     * Copyright (c) 2023 Roman Grinko <rsgrinko@gmail.com>
     * Permission is hereby granted, free of charge, to any person obtaining
     * a copy of this software and associated documentation files (the
     * "Software"), to deal in the Software without restriction, including
     * without limitation the rights to use, copy, modify, merge, publish,
     * distribute, sublicense, and/or sell copies of the Software, and to
     * permit persons to whom the Software is furnished to do so, subject to
     * the following conditions:
     * The above copyright notice and this permission notice shall be included
     * in all copies or substantial portions of the Software.
     * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
     * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
     * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
     * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
     * CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
     * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
     * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
     */
     
     namespace Core;

     use Delta\Config\Config;

     /**
      * Класс системных параметров.
      *
      * Слой совместимости: старый код обращается к настройкам по имени legacy-константы,
      * а источником истины уже является Delta\Config\Config. Класс переводит имя константы
      * в точечный ключ конфигурации и лишь при отсутствии соответствия падает обратно
      * на constant(). Новый код должен работать с Config напрямую.
      */
     class SystemConfig {

         /** @var array<string, string> Соответствие legacy-констант ключам конфигурации */
         private const KEY_MAP = [
             'APP_ENV'                      => 'app.env',
             'DEBUG'                        => 'app.debug',
             'CORE_VERSION'                 => 'app.version',
             'SITE_URL'                     => 'app.url',
             'PAGINATION_LIMIT'             => 'app.pagination_limit',
             'USER_ONLINE_TIME'             => 'app.user_online_time',
             'DATETIME_FORMAT'              => 'app.datetime_format',
             'USE_LOG'                      => 'app.use_log',
             'ROOT_PATH'                    => 'app.paths.root',
             'CORE_PATH'                    => 'app.paths.core',
             'UPLOADS_PATH'                 => 'app.paths.uploads',
             'PATH_TO_TEMPLATES'            => 'app.paths.templates',
             'LOG_PATH'                     => 'app.paths.log',
             'DB_HOST'                      => 'database.host',
             'DB_NAME'                      => 'database.name',
             'DB_USER'                      => 'database.user',
             'DB_PASSWORD'                  => 'database.password',
             'DB_TABLE_PREFIX'              => 'database.table_prefix',
             'USE_CACHE'                    => 'cache.enabled',
             'CACHE_DIR'                    => 'cache.dir',
             'CACHE_TTL'                    => 'cache.ttl',
             'SERVER_EMAIL'                 => 'mail.from_email',
             'SERVER_EMAIL_NAME'            => 'mail.from_name',
             'MAIL_TEMPLATES_PATH'          => 'mail.templates_path',
             'MAIL_TEMPLATE_DEFAULT'        => 'mail.template_default',
             'TELEGRAM_BOT_USERNAME'        => 'telegram.bot_username',
             'TELEGRAM_BOT_TOKEN'           => 'telegram.bot_token',
             'TELEGRAM_NOTIFICATION_CHANNEL' => 'telegram.notification_channel',
             'TELEGRAM_ADMIN_CHAT_ID'       => 'telegram.admin_chat_id',
             'CRYPTO_KEY'                   => 'security.crypto_key',
             'USE_CAPTCHA'                  => 'security.use_captcha',
             'USE_DDOS_PROTECTION'          => 'security.use_ddos_protection',
         ];

         /**
          * Получить значение системного параметра
          *
          * @param string $param Имя параметра (legacy-константа либо точечный ключ конфигурации)
          *
          * @return mixed Значение параметра
          */
         public static function getValue(string $param): mixed
         {
             $config = Config::getInstance();

             if (isset(self::KEY_MAP[$param])) {
                 return $config->get(self::KEY_MAP[$param]);
             }

             if ($config->has($param)) {
                 return $config->get($param);
             }

             return defined($param) ? constant($param) : null;
         }
     }