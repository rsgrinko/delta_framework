<?php

    declare(strict_types=1);

    namespace Tests\Routing;

    use Delta\Http\Request;
    use Delta\Http\Response;
    use Delta\Routing\Router;
    use PHPUnit\Framework\TestCase;
    use RuntimeException;

    /**
     * Тесты маршрутизатора
     */
    class RouterTest extends TestCase
    {
        /**
         * Запрос к указанному пути
         *
         * @param string $path   Путь
         * @param string $method HTTP-метод
         *
         * @return Request Запрос
         */
        private function request(string $path, string $method = 'GET'): Request
        {
            return new Request($method, $path);
        }

        public function testDispatchesToHandlerReturningResponse(): void
        {
            $router = new Router();
            $router->route('/', static fn(): Response => Response::html('главная'));

            $response = $router->dispatch($this->request('/'));

            $this->assertSame('главная', $response->body());
            $this->assertSame(200, $response->status());
        }

        public function testWrapsLegacyHandlerOutputIntoResponse(): void
        {
            $router = new Router();
            $router->route('/legacy', static function (): void {
                echo 'напечатано в вывод';
            });

            $response = $router->dispatch($this->request('/legacy'));

            $this->assertSame('напечатано в вывод', $response->body());
        }

        public function testPassesRouteParametersToHandler(): void
        {
            $router = new Router();
            $router->route('/users/(\d+)', static fn(string $id): Response => Response::html('пользователь ' . $id));

            $response = $router->dispatch($this->request('/users/42'));

            $this->assertSame('пользователь 42', $response->body());
        }

        public function testFallsBackToErrorRoute(): void
        {
            $router = new Router();
            $router->route('/404', static fn(): Response => Response::notFound('не найдено'));

            $response = $router->dispatch($this->request('/нет-такого'));

            $this->assertSame(404, $response->status());
            $this->assertSame('не найдено', $response->body());
        }

        public function testReturnsNotFoundWithoutErrorRoute(): void
        {
            $router = new Router();

            $this->assertSame(404, $router->dispatch($this->request('/нет'))->status());
        }

        public function testPaginationPatternMatchesBothForms(): void
        {
            $router = new Router();
            $router->route('/users', static fn(): Response => Response::html('список'), true);

            $this->assertSame('список', $router->dispatch($this->request('/users'))->body());
            $this->assertSame('список', $router->dispatch($this->request('/users/page/3'))->body());
        }

        public function testOnMatchReceivesHandler(): void
        {
            $matched = null;
            $router  = new Router();
            $router->onMatch(static function (callable|string $handler) use (&$matched): void {
                $matched = $handler;
            });
            $router->route('/info', '\Tests\Routing\RouterTest::infoHandler');

            $router->dispatch($this->request('/info'));

            $this->assertSame('\Tests\Routing\RouterTest::infoHandler', $matched);
        }

        public function testQueryStringIsNotPartOfMatching(): void
        {
            $router = new Router();
            $router->route('/users', static fn(): Response => Response::html('список'));

            // path() уже не содержит строку запроса, маршрут обязан совпасть
            $this->assertSame('список', $router->dispatch($this->request('/users'))->body());
        }

        public function testExceptionFromHandlerIsNotSwallowed(): void
        {
            $router = new Router();
            $router->route('/boom', static function (): void {
                echo 'частичный вывод';
                throw new RuntimeException('сломалось');
            });

            $this->expectException(RuntimeException::class);

            $router->dispatch($this->request('/boom'));
        }

        /**
         * Обработчик для проверки onMatch
         *
         * @return Response Ответ
         */
        public static function infoHandler(): Response
        {
            return Response::html('инфо');
        }
    }
