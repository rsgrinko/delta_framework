<?php

    declare(strict_types=1);

    namespace Delta\Http;

    /**
     * Конвейер промежуточных обработчиков.
     *
     * Запрос проходит через список middleware в порядке их объявления и попадает в конечный
     * обработчик; ответ возвращается обратно в обратном порядке. Любой middleware может
     * прервать цепочку, вернув ответ вместо вызова $next.
     */
    final class Pipeline
    {
        /**
         * Конструктор
         *
         * @param Middleware[] $middleware Список обработчиков
         */
        public function __construct(private readonly array $middleware = [])
        {
        }

        /**
         * Пропустить запрос через конвейер
         *
         * @param Request  $request     Запрос
         * @param callable $destination Конечный обработчик
         *
         * @return Response Ответ
         */
        public function handle(Request $request, callable $destination): Response
        {
            $next = $destination;

            foreach (array_reverse($this->middleware) as $middleware) {
                $current = $next;
                $next    = static fn(Request $request): Response => $middleware->handle($request, $current);
            }

            return $next($request);
        }
    }
