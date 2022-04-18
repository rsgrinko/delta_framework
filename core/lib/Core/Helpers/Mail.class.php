<?php

    namespace Core\Helpers;

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
         * От кого
         */
        public function setFrom($email, $name = null)
        {
            $this->fromEmail = $email;
            $this->fromName  = $name;
        }

        /**
         * Кому
         */
        public function setTo($email, $name = null)
        {
            $this->toEmail = $email;
            $this->toName  = $name;
        }

        /**
         * Тема
         */
        public function setSubject($subject = null)
        {
            $this->subject = $subject;
        }

        /**
         * Тело письма
         */
        public function setBody($body = null)
        {
            $this->body = $body;
        }

        /**
         * Добавление файла к письму
         */
        public function addFile($filename)
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

            $subject = (empty($this->subject)) ? 'No subject' : $this->subject;
            $body = $this->body;
            $boundary = md5(uniqid(time()));
            $headers = [
                'Content-Type: multipart/mixed; boundary="' . $boundary . '"',
                'Content-Transfer-Encoding: 7bit',
                'MIME-Version: 1.0',
                'From: ' . $from,
                'Date: ' . date('r'),
            ];
            $message = [
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

            return $res;
        }
    }