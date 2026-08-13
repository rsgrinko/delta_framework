<?php

    /**
     * Строгая типизация в этом файле намеренно не включена.
     *
     * Параметры маршрута извлекаются регулярным выражением и всегда являются строками, тогда
     * как обработчики объявляют их как int (например App::userProfile(int $id)). Режим
     * strict_types действует на месте вызова, поэтому при его включении здесь любой такой
     * обработчик падал бы с TypeError вместо привычного приведения "1" к 1.
     */

    namespace Delta\Routing;

    use Delta\Http\Request;
    use Delta\Http\Response;

    /**
     * Маршрутизатор.
     *
     * Сопоставляет путь запроса с зарегистрированными маршрутами и возвращает объект ответа.
     *
     * Обработчик маршрута может быть двух видов:
     *   - новый стиль: возвращает Delta\Http\Response;
     *   - старый стиль: печатает в вывод и сам зовёт header() — такой ответ перехватывается
     *     буферизацией и оборачивается в Response.
     * Второй вариант оставлен намеренно: он позволяет переводить маршруты на новый стиль
     * по одному, не останавливая сайт.
     */
    final class Router
    {
        /** @var string Маршрут страницы 404 */
        private const ERROR_PAGE = '/^\/404$/';

        /** @var array<string, callable|string> Таблица маршрутизации */
        private array $routes = [];

        /** @var (callable(callable|string): void)|null Уведомление о сработавшем маршруте */
        private $onMatch = null;

        /**
         * Зарегистрировать маршрут
         *
         * @param string          $pattern       Шаблон пути, например /users/(\d+)
         * @param callable|string $handler       Обработчик
         * @param bool            $usePagination Дополнительно матчить /pattern/page/(\d+)
         *
         * @return $this Маршрутизатор
         */
        public function route(string $pattern, callable|string $handler, bool $usePagination = false): self
        {
            $pattern = str_replace('/', '\/', $pattern);
            $pattern = $usePagination
                ? '/(^' . $pattern . '$)|(^' . $pattern . '\/page\/(\d+))$/'
                : '/^' . $pattern . '$/';

            $this->routes[$pattern] = $handler;

            return $this;
        }

        /**
         * Назначить обработчик события «маршрут найден»
         *
         * Нужен, чтобы прикладной код мог узнать текущий маршрут, не создавая обратной
         * зависимости ядра от приложения.
         *
         * @param callable $callback Обработчик, принимающий сработавший обработчик маршрута
         *
         * @return $this Маршрутизатор
         */
        public function onMatch(callable $callback): self
        {
            $this->onMatch = $callback;

            return $this;
        }

        /**
         * Обработать запрос
         *
         * @param Request $request Запрос
         *
         * @return Response Ответ
         */
        public function dispatch(Request $request): Response
        {
            foreach ($this->routes as $pattern => $handler) {
                if (preg_match($pattern, $request->path(), $params) !== 1) {
                    continue;
                }

                array_shift($params);
                $request->setAttribute('route.handler', $handler);
                $request->setAttribute('route.params', array_values($params));

                if ($this->onMatch !== null) {
                    ($this->onMatch)($handler);
                }

                return $this->invoke($handler, array_values($params), $request);
            }

            if (isset($this->routes[self::ERROR_PAGE])) {
                if ($this->onMatch !== null) {
                    ($this->onMatch)($this->routes[self::ERROR_PAGE]);
                }

                return $this->invoke($this->routes[self::ERROR_PAGE], [], $request);
            }

            return Response::notFound();
        }

        /**
         * Вызвать обработчик маршрута
         *
         * @param callable|string $handler Обработчик
         * @param array           $params  Позиционные параметры из шаблона
         * @param Request         $request Запрос
         *
         * @return Response Ответ
         */
        private function invoke(callable|string $handler, array $params, Request $request): Response
        {
            $callable = is_string($handler) ? $this->resolve($handler) : $handler;

            ob_start();
            try {
                $result = $callable(...$params);
            } catch (\Throwable $e) {
                ob_end_clean();
                throw $e;
            }
            $output = (string)ob_get_clean();

            // Новый стиль: обработчик сам вернул ответ
            if ($result instanceof Response) {
                return $result;
            }

            // Старый стиль: обработчик напечатал вывод и, возможно, выставил заголовки сам
            return new Response($output, http_response_code() ?: 200);
        }

        /**
         * Превратить строковый обработчик в вызываемое значение
         *
         * @param string $handler Обработчик вида '\Core\App::index'
         *
         * @return callable Вызываемое значение
         */
        private function resolve(string $handler): callable
        {
            if (str_contains($handler, '::')) {
                [$class, $method] = explode('::', ltrim($handler, '\\'), 2);

                return [$class, $method];
            }

            return $handler;
        }
    }
