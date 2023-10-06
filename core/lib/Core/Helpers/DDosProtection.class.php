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


    namespace Core\Helpers;

    use Core\CoreException;
    use Core\DataBases\DB;

    /**
     * Класс защиты от DDOS атак
     */
    class DDosProtection
    {
        const TABLE = DB_TABLE_PREFIX . 'ddos_protection';

        private ?string $place = null;

        private ?int $userId = null;

        private ?string $observedKey = null;

        private int $maxAttempts = 10;

        private int $timeInterval = 10;


        public function __construct(?string $place = null)
        {
            $this->place = $place;
        }

        public function setUserId(int $userId): self
        {
            $this->userId = $userId;
            return $this;
        }

        public function setObservedKey(string $key): self
        {
            $this->observedKey = $key;
            return $this;
        }

        private function checkAndSetDefaultParams(): void
        {
            if (empty($this->observedKey)) {
                $this->observedKey = SystemFunctions::getIP();
                if ($this->observedKey === 'undefined') {
                    $this->observedKey = md5($_SERVER['HTTP_USER_AGENT']);
                }
                if ($this->userId !== null) {
                    $this->observedKey .= '-' . $this->userId;
                }
            }

            if (empty($this->timeInterval)) {
                $this->timeInterval = 60;
            }
        }

        private function clearData(): void
        {
            /** @var DB $db */
            $DB = DB::getInstance();
            $DB->query('DELETE FROM ' . self::TABLE . ' WHERE last_active < ' . (time() - $this->timeInterval));
        }

        public function checkDDos(): void
        {
            $this->clearData();
            $this->checkAndSetDefaultParams();

            /** @var DB $db */
            $DB = DB::getInstance();
            $item = $DB->getItem(self::TABLE, ['observed_key' => $this->observedKey]);
            if ($item) {
                if ($item['attempts'] >= $item['attempts_limit'] && (time() - $item['last_active']) <= $item['time_interval']) {
                    echo '<div style="color: #b30000;background: #ffe2e2;padding: 10px;border: 1px solid #ffa0a0;margin: 10px;display: inline-block;">';
                    echo '<span style="font-weight:bold; font-size: 1.1em;">You are temporarily blocked (DDoS Guard))</span><br>';
                    echo 'Due to a large number of requests, you are temporarily blocked.<br>If this happened by mistake, contact the resource administrator.<br>';
                    echo '</div>';
                    die();
                }

                $DB->update(self::TABLE, ['id' => $item['id']], ['attempts' => ++$item['attempts'], 'last_active' => time()]);
            } else {
                $DB->addItem(self::TABLE,
                             [
                                 'user_id'        => $this->userId,
                                 'place'          => $this->place,
                                 'observed_key'   => $this->observedKey,
                                 'attempts'       => 1,
                                 'attempts_limit' => $this->maxAttempts,
                                 'time_interval'  => $this->timeInterval,
                                 'last_active'    => time(),
                             ]
                );
            }
        }
    }