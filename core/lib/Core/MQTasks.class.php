<?php

    namespace Core;

    use Core\Helpers\SystemFunctions;

    class MQTasks
    {
        public static function testSendTelegram()
        {
            SystemFunctions::sendTelegram('test task was completed');
        }
    }