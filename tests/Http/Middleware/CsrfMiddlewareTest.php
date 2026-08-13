<?php

    declare(strict_types=1);

    namespace Tests\Http\Middleware;

    use Delta\Http\Middleware\CsrfMiddleware;
    use Delta\Http\Request;
    use Delta\Http\Response;
    use Delta\Security\Csrf;
    use PHPUnit\Framework\TestCase;

    /**
     * Тесты проверки CSRF-токена
     */
    class CsrfMiddlewareTest extends TestCase
    {
        protected function setUp(): void
        {
            $_SESSION = [];
        }

        protected function tearDown(): void
        {
            $_SESSION = [];
        }

        /**
         * Конечный обработчик конвейера
         *
         * @return callable Обработчик
         */
        private function destination(): callable
        {
            return static fn(): Response => Response::html('дошло до контроллера');
        }

        public function testGetRequestPassesWithoutToken(): void
        {
            $response = (new CsrfMiddleware())->handle(
                new Request('GET', '/profile'),
                $this->destination(),
            );

            $this->assertSame('дошло до контроллера', $response->body());
        }

        public function testPostWithoutTokenIsRejected(): void
        {
            $response = (new CsrfMiddleware())->handle(
                new Request('POST', '/profile/personal'),
                $this->destination(),
            );

            $this->assertSame(419, $response->status());
            $this->assertStringNotContainsString('дошло до контроллера', $response->body());
        }

        public function testPostWithValidTokenPasses(): void
        {
            $token = Csrf::token();

            $response = (new CsrfMiddleware())->handle(
                new Request('POST', '/profile/personal', [], [Csrf::FIELD => $token]),
                $this->destination(),
            );

            $this->assertSame('дошло до контроллера', $response->body());
        }

        public function testTokenIsAcceptedFromHeader(): void
        {
            $token = Csrf::token();

            $response = (new CsrfMiddleware())->handle(
                new Request('POST', '/api', [], [], [], [], ['HTTP_X_CSRF_TOKEN' => $token]),
                $this->destination(),
            );

            $this->assertSame('дошло до контроллера', $response->body());
        }

        public function testAjaxRequestGetsJsonError(): void
        {
            $response = (new CsrfMiddleware())->handle(
                new Request('POST', '/users/1/sendMessage', [], [], [], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']),
                $this->destination(),
            );

            $this->assertSame(419, $response->status());
            $this->assertSame('application/json; charset=UTF-8', $response->headers()['Content-Type']);
            $this->assertStringContainsString('"success":false', $response->body());
        }

        public function testExcludedPathSkipsCheck(): void
        {
            $response = (new CsrfMiddleware(['/webhook']))->handle(
                new Request('POST', '/webhook'),
                $this->destination(),
            );

            $this->assertSame('дошло до контроллера', $response->body());
        }
    }
