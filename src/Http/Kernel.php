<?php

    declare(strict_types=1);

    namespace Delta\Http;

    use Delta\Error\ErrorHandler;
    use Delta\Routing\Router;
    use Throwable;

    /**
     * Ядро обработки HTTP-запроса.
     *
     * Единственное место, где запрос превращается в ответ. Исключение из любого экшена
     * не уходит в рантайм, а становится ответом с кодом 500 и записью в журнал.
     */
    final class Kernel
    {
        /**
         * Конструктор
         *
         * @param Router            $router       Маршрутизатор
         * @param ErrorHandler|null $errorHandler Обработчик ошибок
         */
        public function __construct(
            private readonly Router $router,
            private readonly ?ErrorHandler $errorHandler = null,
        ) {
        }

        /**
         * Обработать запрос
         *
         * @param Request $request Запрос
         *
         * @return Response Ответ
         */
        public function handle(Request $request): Response
        {
            try {
                return $this->router->dispatch($request);
            } catch (Throwable $e) {
                if ($this->errorHandler === null) {
                    throw $e;
                }

                return $this->errorHandler->render($e);
            }
        }
    }
