<?php

    declare(strict_types=1);

    namespace Tests\Http;

    use Delta\Http\Response;
    use PHPUnit\Framework\TestCase;

    /**
     * Тесты объекта ответа
     */
    class ResponseTest extends TestCase
    {
        public function testHtmlResponse(): void
        {
            $response = Response::html('<b>тело</b>');

            $this->assertSame('<b>тело</b>', $response->body());
            $this->assertSame(200, $response->status());
            $this->assertSame('text/html; charset=UTF-8', $response->headers()['Content-Type']);
        }

        public function testJsonResponseKeepsCyrillicReadable(): void
        {
            $response = Response::json(['error' => 'Сообщение пустое'], 422);

            $this->assertSame('{"error":"Сообщение пустое"}', $response->body());
            $this->assertSame(422, $response->status());
            $this->assertSame('application/json; charset=UTF-8', $response->headers()['Content-Type']);
        }

        public function testRedirectResponse(): void
        {
            $response = Response::redirect('/profile');

            $this->assertSame(302, $response->status());
            $this->assertSame('/profile', $response->headers()['Location']);
            $this->assertSame('', $response->body());
        }

        public function testNotFoundResponse(): void
        {
            $this->assertSame(404, Response::notFound()->status());
        }

        public function testHeadersAndStatusAreChainable(): void
        {
            $response = (new Response('тело'))
                ->withHeader('X-Test', 'значение')
                ->withStatus(201);

            $this->assertSame(201, $response->status());
            $this->assertSame('значение', $response->headers()['X-Test']);
        }
    }
