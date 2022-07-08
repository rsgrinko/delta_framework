<?php

    namespace Core;

    use Core\Helpers\SystemFunctions;
    use Core\Helpers\Thumbs;
    use Core\ExternalServices\TelegramSender;
    use Core\Models\DB;

    class MQTasks
    {
        public static function testSendTelegram()
        {
            SystemFunctions::sendTelegram('test task was completed');
        }

        /**
         * @throws CoreException
         */
        public static function resizeImage(string $filename, int $width, int $height) {
            $saveImage = $_SERVER['DOCUMENT_ROOT'] . '/uploads/images/resized/' . $filename;
            $image = new Thumbs($_SERVER['DOCUMENT_ROOT'] . '/uploads/images/original/' . $filename);
            $image->thumb($width, $height);
            $image->watermark($_SERVER['DOCUMENT_ROOT'] . '/uploads/images/w.png', 'bottom-right', 70);
            $image->save($saveImage);

            return $saveImage;
        }

        public static function sendBash(...$args): ?string
        {
            /** @var DB $DB */
            $DB = DB::getInstance();

            $file = file_get_contents('http://bashorg.org/random');
            $file = preg_match_all('|<div class="quote">(.+)</div>|U', $file, $frazes);

            $frazes = $frazes[1];
            $frazes = array_map(function ($e) {
                return \iconv('windows-1251//IGNORE', 'UTF-8//IGNORE', $e);
            }, $frazes);

            $i = 0;
            foreach ($frazes as $post) {
                $text = str_replace('<br />', PHP_EOL, $post);
                $text = htmlspecialchars_decode($text);
                $text = str_replace('\'', '\\\'', $text);
                $hash = md5($text);
                $res  = $DB->query('SELECT * FROM bashorg WHERE hash="' . $hash . '"');
                if (!$res) {
                    $i++;
                    $DB->addItem('bashorg', ['hash' => $hash, 'text' => $text]);
                }
            }
            sleep(2);
            return 'Добавлено ' . $i . ' записей из ' . count($frazes);
        }
/*
        public static function sendBash2(...$args): void
        {


            $DB = DB::getInstance();

            $html = file_get_contents('http://bashorg.org/random');

            //$file = preg_match_all('|<div class="vote">(.+)</div>|U',$html,$frazes);
            $file = preg_match_all('<div class="q">(.+?)</div>',$html,$top);

print_r($top);
            $frazes = $frazes[1];
            $frazes = array_map(function($e){
                return \iconv('windows-1251//IGNORE', 'UTF-8//IGNORE', $e);
            }, $frazes);

        }*/
    }