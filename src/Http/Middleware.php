<?php

    declare(strict_types=1);

    namespace Delta\Http;

    /**
     * Промежуточный обработчик запроса.
     *
     * Позволяет вынести сквозное поведение (авторизацию, защиту от CSRF, трекинг, лимиты)
     * из контроллеров в одно место и включать его для всех маршрутов сразу.
     */
    interface Middleware
    {
        /**
         * Обработать запрос
         *
         * @param Request  $request Запрос
         * @param callable $next    Следующий обработчик конвейера
         *
         * @return Response Ответ
         */
        public function handle(Request $request, callable $next): Response;
    }
