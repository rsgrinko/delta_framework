<?php

    namespace Core;

    use Core\Helpers\SystemFunctions;
    use Core\Helpers\Thumbs;

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
    }