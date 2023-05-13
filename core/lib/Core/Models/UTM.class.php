<?php
    /**
     * Copyright (c) 2022 Roman Grinko <rsgrinko@gmail.com>
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

    namespace Core\Models;

    use Throwable;

    /**
     * Класс для работы с UTM метками
     */
    class UTM {
        /** @var string TABLE Таблица */
        private const TABLE = DB_TABLE_PREFIX . 'utm_history';

        /** @var int $id Идентификатор записи */
        private $id;

        /** @var string $page Страница */
        private $page;

        /** @var string $referer Реферер */
        private $referer;

        /** @var string $userAgent Браузер */
        private $userAgent;

        /** @var string $utmSource Источник перехода */
        private $utmSource;

        /** @var string $utmMedium Тип трафика */
        private $utmMedium;

        /** @var string $utmCampaign Название рекламной кампании */
        private $utmCampaign;

        /** @var string $utmContent Дополнительная информация, которая помогает различать объявления */
        private $utmContent;

        /** @var string $utmTerm Ключевая фраза */
        private $utmTerm;

        /** @var string $dateCreate Дата создания */
        private $dateCreate;

        /**
         * Конструктор
         *
         * @param bool $getCurrentData Признак получения текущих данных запроса
         */
        public function __construct(bool $getCurrentData = true)
        {
            if ($getCurrentData) {
                $this->setCurrentData();
            }
        }

        /**
         * Наполнить объект текущими значениями
         *
         * @return void
         */
        private function setCurrentData(): void
        {
            if (isset($_GET['utm_source']) && !empty($_GET['utm_source'])) {
                $this->utmSource = $this->sanitizeValue($_GET['utm_source']);
            }

            if (isset($_GET['utm_medium']) && !empty($_GET['utm_medium'])) {
                $this->utmMedium = $this->sanitizeValue($_GET['utm_medium']);
            }

            if (isset($_GET['utm_campaign']) && !empty($_GET['utm_campaign'])) {
                $this->utmCampaign = $this->sanitizeValue($_GET['utm_campaign']);
            }

            if (isset($_GET['utm_content']) && !empty($_GET['utm_content'])) {
                $this->utmContent = $this->sanitizeValue($_GET['utm_content']);
            }

            if (isset($_GET['utm_term']) && !empty($_GET['utm_term'])) {
                $this->utmTerm = $this->sanitizeValue($_GET['utm_term']);
            }

            if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
                $this->referer = $this->sanitizeValue($_SERVER['HTTP_REFERER']);
            }

            if (isset($_SERVER['HTTP_USER_AGENT']) && !empty($_SERVER['HTTP_USER_AGENT'])) {
                $this->userAgent = $this->sanitizeValue($_SERVER['HTTP_USER_AGENT']);
            }

            $this->page = $this->sanitizeValue($this->getCurrentPage());
        }

        /**
         * Получить ID
         *
         * @return int
         */
        public function getId(): int
        {
            return $this->id;
        }

        /**
         * Получить страницу
         *
         * @return string
         */
        public function getPage(): string
        {
            return $this->page;
        }

        /**
         * Получить реферер
         *
         * @return string
         */
        public function getReferer(): string
        {
            return $this->referer;
        }

        /**
         * Получить браузер
         *
         * @return string
         */
        public function getUserAgent(): string
        {
            return $this->userAgent;
        }

        /**
         * Получить источник
         *
         * @return string
         */
        public function getUtmSource(): string
        {
            return $this->utmSource;
        }

        /**
         * Получить тип трафика
         *
         * @return string
         */
        public function getUtmMedium(): string
        {
            return $this->utmMedium;
        }

        /**
         * Получить название рекламной кампании
         *
         * @return string
         */
        public function getUtmCampaign(): string
        {
            return $this->utmCampaign;
        }

        /**
         * Получить контент
         *
         * @return string
         */
        public function getUtmContent(): string
        {
            return $this->utmContent;
        }

        /**
         * Получить ключевые слова
         *
         * @return string
         */
        public function getUtmTerm(): string
        {
            return $this->utmTerm;
        }

        /**
         * Получить дату создания
         *
         * @return string
         */
        public function getDateCreate(): string
        {
            return $this->dateCreate;
        }

        /**
         * Санитизация значения
         *
         * @param string|null $value Значение
         *
         * @return string
         */
        private function sanitizeValue(?string $value): string
        {
            $value = strval($value);
            $value = stripslashes($value);
            $value = htmlspecialchars_decode($value, ENT_QUOTES);
            $value = strip_tags($value);
            $value = htmlspecialchars($value, ENT_QUOTES);

            return $value;
        }

        /**
         * Получение текущего URI без параметров
         *
         * @return string
         */
        private function getCurrentPage(): string
        {
            return explode('?', $_SERVER['REQUEST_URI'])[0];
        }

        /**
         * Сохранение данных
         *
         * @return $this
         */
        public function save(): self
        {
            if (empty($this->utmSource)) {
                return $this;
            }
            $utmData = [
                'utm_source'   => $this->utmSource,
                'utm_medium'   => $this->utmMedium,
                'utm_campaign' => $this->utmCampaign,
                'utm_content'  => $this->utmContent,
                'utm_term'     => $this->utmTerm,
                'page'         => $this->page,
                'referer'      => $this->referer,
                'user_agent'   => $this->userAgent,
            ];

            try {
                /** @var DB $db */
                $db = DB::getInstance();

                if ($this->id === null) {
                    $result = $db->addItem(self::TABLE, $utmData);
                    if ($result > 0) {
                        $this->id = $result;
                    }
                } else {
                    $db->update(self::TABLE, ['id' => $this->id], $utmData);
                }
            } catch(Throwable $e) {
                // fail
                //TODO вероятно надо записать в лог и сентри об ошибке...
            }
            return $this;
        }
    }