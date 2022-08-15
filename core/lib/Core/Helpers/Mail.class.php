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

    namespace Core\Helpers;

    use Core\Models\User;
    use Core\Helpers\Log;

    /**
     * Класс для отправки E-mail
     */
    class Mail
    {
        /**
         * От кого
         */
        public $fromEmail = '';

        public $fromName  = '';

        /**
         * Кому
         */
        public $toEmail = '';

        public $toName  = '';

        /**
         * Тема
         */
        public $subject = '';

        /**
         * Текст
         */
        public $body = '';

        /**
         * Массив заголовков файлов
         */
        private $_files = [];

        /**
         * @var User|null $user Объект пользователя
         */
        private $user = null;

        /**
         * Конструктор
         */
        public function __construct(User $user = null) {
            if($user !== null) {
                $this->user = $user;
                $this->setFrom(SERVER_EMAIL, SERVER_EMAIL_NAME)->setTo($user->getEmail(), $user->getName());
            }
        }
        /**
         * От кого
         *
         * @return $this
         */
        public function setFrom($email, $name = null): self
        {
            $this->fromEmail = $email;
            $this->fromName  = $name;
            return $this;
        }

        /**
         * Кому
         *
         * @return $this
         */
        public function setTo($email, $name = null): self
        {
            $this->toEmail = $email;
            $this->toName  = $name;
            return $this;
        }

        /**
         * Тема
         *
         * @return $this
         */
        public function setSubject($subject = null): self
        {
            $this->subject = $subject;
            return $this;
        }

        /**
         * Тело письма
         *
         * @return $this
         */
        public function setBody($body = null): self
        {
            $this->body = $body;
            return $this;
        }

        /**
         * Добавление файла к письму
         *
         * @return $this
         */
        public function addFile($filename): self
        {
            if (is_file($filename)) {
                $name = basename($filename);
                $fp   = fopen($filename, 'rb');
                $file = fread($fp, filesize($filename));
                fclose($fp);
                $this->_files[] = [
                    'Content-Type: application/octet-stream; name="' . $name . '"',
                    'Content-Transfer-Encoding: base64',
                    'Content-Disposition: attachment; filename="' . $name . '"',
                    '',
                    chunk_split(base64_encode($file)),
                ];
            }
            return $this;
        }

        /**
         * Отправка
         */
        public function send()
        {
            if (empty($this->toEmail)) {
                return false;
            }

            $from = (empty($this->fromName)) ? $this->fromEmail : '=?UTF-8?B?' . base64_encode($this->fromName) . '?= <' . $this->fromEmail . '>';

            $array_to = [];
            foreach (explode(',', $this->toEmail) as $row) {
                $row = trim($row);
                if (!empty($row)) {
                    $array_to[] = (empty($this->toName)) ? $row : '=?UTF-8?B?' . base64_encode($this->toName) . '?= <' . $row . '>';
                }
            }

            $subject  = (empty($this->subject)) ? 'No subject' : $this->subject;
            $body     = $this->body;
            $boundary = md5(uniqid(time(), true));
            $headers  = [
                'Content-Type: multipart/mixed; boundary="' . $boundary . '"',
                'Content-Transfer-Encoding: 7bit',
                'MIME-Version: 1.0',
                'From: ' . $from,
                'Date: ' . date('r'),
            ];
            $message  = [
                '--' . $boundary,
                'Content-Type: text/html; charset=UTF-8',
                'Content-Transfer-Encoding: base64',
                '',
                chunk_split(base64_encode($body)),
            ];

            if (!empty($this->_files)) {
                foreach ($this->_files as $row) {
                    $message = array_merge($message, ['', '--' . $boundary], $row);
                }
            }

            $message[] = '';
            $message[] = '--' . $boundary . '--';
            $res       = [];

            foreach ($array_to as $to) {
                $res[] = mb_send_mail($to, $subject, implode("\r\n", $message), implode("\r\n", $headers));
            }

            Log::logToFile('Отправлено письмо', 'Mail.log', ['to' => $this->toEmail, 'subject' => $subject, 'body' => implode(' ', $message)]);
            return $res;
        }
    }