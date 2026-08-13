<?php

    declare(strict_types=1);

    namespace Delta\Error;

    use Delta\Http\Response;
    use ErrorException;
    use Throwable;

    /**
     * Центральный обработчик ошибок.
     *
     * До его появления необработанное исключение из любого экшена всплывало в PHP-рантайм
     * мимо логов приложения, а то, что увидит пользователь, определялось значением
     * display_errors в php.ini, а не логикой фреймворка.
     */
    final class ErrorHandler
    {
        /** @var (callable(string, array): void)|null Журналирование */
        private $logger = null;

        /**
         * Конструктор
         *
         * @param bool          $debug  Режим отладки: показывать трассировку
         * @param callable|null $logger Функция журналирования (сообщение, контекст)
         */
        public function __construct(
            private readonly bool $debug = false,
            ?callable $logger = null,
        ) {
            $this->logger = $logger;
        }

        /**
         * Зарегистрировать обработчики PHP
         *
         * @return $this Обработчик
         */
        public function register(): self
        {
            set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
                if ((error_reporting() & $severity) === 0) {
                    return false;
                }

                throw new ErrorException($message, 0, $severity, $file, $line);
            });

            set_exception_handler(function (Throwable $e): void {
                $this->render($e)->send();
            });

            register_shutdown_function(function (): void {
                $error = error_get_last();
                if ($error === null || in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true) === false) {
                    return;
                }

                $this->log('Фатальная ошибка: ' . $error['message'], [
                    'file' => $error['file'],
                    'line' => $error['line'],
                ]);

                if (headers_sent() === false) {
                    $this->render(new ErrorException(
                        $error['message'],
                        0,
                        $error['type'],
                        $error['file'],
                        $error['line'],
                    ))->send();
                }
            });

            return $this;
        }

        /**
         * Построить ответ по исключению
         *
         * @param Throwable $e Исключение
         *
         * @return Response Ответ
         */
        public function render(Throwable $e): Response
        {
            $this->log($e->getMessage(), [
                'exception' => $e::class,
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'trace'     => $e->getTraceAsString(),
            ]);

            if ($this->debug) {
                $body = '<h1>Ошибка приложения</h1>'
                    . '<p><b>' . htmlspecialchars($e::class, ENT_QUOTES, 'UTF-8') . ':</b> '
                    . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>'
                    . '<p>' . htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8') . ':' . $e->getLine() . '</p>'
                    . '<pre>' . htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8') . '</pre>';
            } else {
                $body = '<h1>Внутренняя ошибка сервера</h1><p>Попробуйте повторить запрос позже.</p>';
            }

            return new Response($body, 500, ['Content-Type' => 'text/html; charset=UTF-8']);
        }

        /**
         * Записать сообщение в журнал
         *
         * @param string $message Сообщение
         * @param array  $context Контекст
         *
         * @return void
         */
        private function log(string $message, array $context = []): void
        {
            if ($this->logger === null) {
                return;
            }

            ($this->logger)($message, $context);
        }
    }
