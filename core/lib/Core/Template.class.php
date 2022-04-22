<?php

    namespace Core;
    use Core\Helpers\SystemFunctions;

    class Template
    {
        private static $arTemplateVariables = [];

        public static function set(string $varName, string $varValue): void
        {
            $varName = '{{' . $varName . '}}';
            self::$arTemplateVariables[$varName] = $varValue;
        }

        public static function flush(): void
        {
            self::$arTemplateVariables = [];
        }

        public static function render($buffer): ?string
        {

            $buffer = str_replace(array_keys(self::$arTemplateVariables), array_values(self::$arTemplateVariables), $buffer);
            return $buffer;
        }
    }