<?php

    declare(strict_types=1);

    namespace Tests\Http;

    use Delta\Http\Middleware;
    use Delta\Http\Pipeline;
    use Delta\Http\Request;
    use Delta\Http\Response;
    use PHPUnit\Framework\TestCase;

    /**
     * Тесты конвейера промежуточных обработчиков
     */
    class PipelineTest extends TestCase
    {
        /**
         * Обработчик, дописывающий метку к телу ответа
         *
         * @param string $label Метка
         *
         * @return Middleware Обработчик
         */
        private function tagging(string $label): Middleware
        {
            return new class ($label) implements Middleware {
                public function __construct(private readonly string $label)
                {
                }

                public function handle(Request $request, callable $next): Response
                {
                    $response = $next($request);

                    return new Response($response->body() . '|' . $this->label, $response->status());
                }
            };
        }

        /**
         * Обработчик, прерывающий цепочку
         *
         * @return Middleware Обработчик
         */
        private function blocking(): Middleware
        {
            return new class implements Middleware {
                public function handle(Request $request, callable $next): Response
                {
                    return new Response('прервано', 403);
                }
            };
        }

        public function testRunsWithoutMiddleware(): void
        {
            $response = (new Pipeline())->handle(
                new Request(),
                static fn(): Response => Response::html('назначение'),
            );

            $this->assertSame('назначение', $response->body());
        }

        public function testMiddlewareRunsInDeclaredOrder(): void
        {
            $pipeline = new Pipeline([$this->tagging('первый'), $this->tagging('второй')]);

            $response = $pipeline->handle(
                new Request(),
                static fn(): Response => Response::html('назначение'),
            );

            // ответ возвращается в обратном порядке: сначала внутренний обработчик
            $this->assertSame('назначение|второй|первый', $response->body());
        }

        public function testMiddlewareCanShortCircuitChain(): void
        {
            $reached  = false;
            $pipeline = new Pipeline([$this->blocking(), $this->tagging('недостижимый')]);

            $response = $pipeline->handle(
                new Request(),
                static function () use (&$reached): Response {
                    $reached = true;

                    return Response::html('назначение');
                },
            );

            $this->assertSame(403, $response->status());
            $this->assertSame('прервано', $response->body());
            $this->assertFalse($reached);
        }
    }
