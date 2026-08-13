<?php

    declare(strict_types=1);

    namespace Delta\Http\Middleware;

    use Delta\Http\Middleware;
    use Delta\Http\Request;
    use Delta\Http\Response;
    use Delta\Security\Csrf;

    /**
     * Проверка CSRF-токена на изменяющих состояние запросах.
     *
     * Токен принимается из поля формы либо из заголовка — второе нужно для запросов,
     * отправляемых из JavaScript без формы.
     */
    final class CsrfMiddleware implements Middleware
    {
        /** @var string[] Методы, не изменяющие состояние */
        private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

        /**
         * Конструктор
         *
         * @param string[] $exceptPaths Пути, для которых проверка не выполняется
         */
        public function __construct(private readonly array $exceptPaths = [])
        {
        }

        /**
         * Обработать запрос
         *
         * @param Request  $request Запрос
         * @param callable $next    Следующий обработчик
         *
         * @return Response Ответ
         */
        public function handle(Request $request, callable $next): Response
        {
            if ($this->shouldSkip($request)) {
                return $next($request);
            }

            $token = $request->post(Csrf::FIELD) ?? $request->header(Csrf::HEADER);

            if (Csrf::isValid(is_string($token) ? $token : null)) {
                return $next($request);
            }

            if ($request->isAjax()) {
                return Response::json(
                    ['success' => false, 'error' => 'Сессия устарела, обновите страницу.'],
                    419,
                );
            }

            return new Response(
                'Проверка безопасности не пройдена. Вернитесь назад, обновите страницу и повторите отправку.',
                419,
                ['Content-Type' => 'text/html; charset=UTF-8'],
            );
        }

        /**
         * Нужно ли пропустить проверку
         *
         * @param Request $request Запрос
         *
         * @return bool Признак пропуска
         */
        private function shouldSkip(Request $request): bool
        {
            if (in_array($request->method(), self::SAFE_METHODS, true)) {
                return true;
            }

            return in_array($request->path(), $this->exceptPaths, true);
        }
    }
