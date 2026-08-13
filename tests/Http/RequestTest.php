<?php

    declare(strict_types=1);

    namespace Tests\Http;

    use Delta\Http\Request;
    use PHPUnit\Framework\TestCase;

    /**
     * Тесты объекта запроса
     */
    class RequestTest extends TestCase
    {
        /**
         * Собрать запрос с заданными параметрами
         *
         * @param array $query   Параметры строки запроса
         * @param array $request Параметры тела
         * @param array $server  Параметры сервера
         *
         * @return Request Запрос
         */
        private function make(array $query = [], array $request = [], array $server = []): Request
        {
            return new Request('POST', '/users/1', $query, $request, [], [], $server);
        }

        public function testMethodAndPath(): void
        {
            $request = $this->make();

            $this->assertSame('POST', $request->method());
            $this->assertSame('/users/1', $request->path());
            $this->assertTrue($request->isPost());
            $this->assertTrue($request->isMethod('post'));
            $this->assertFalse($request->isMethod('GET'));
        }

        public function testPostTakesPrecedenceOverQueryInInput(): void
        {
            $request = $this->make(['key' => 'из-query'], ['key' => 'из-body']);

            $this->assertSame('из-body', $request->input('key'));
            $this->assertSame('из-query', $request->query('key'));
            $this->assertSame('из-body', $request->post('key'));
        }

        public function testInputFallsBackToQuery(): void
        {
            $request = $this->make(['page' => '3']);

            $this->assertSame('3', $request->input('page'));
            $this->assertSame(3, $request->integer('page'));
        }

        public function testStringTrimsValue(): void
        {
            $request = $this->make([], ['message' => "  привет  "]);

            $this->assertSame('привет', $request->string('message'));
            $this->assertSame('дефолт', $request->string('нет', 'дефолт'));
        }

        public function testIntegerReturnsDefaultForNonNumeric(): void
        {
            $request = $this->make([], ['id' => 'abc']);

            $this->assertSame(7, $request->integer('id', 7));
        }

        public function testHeaderAndAjaxDetection(): void
        {
            $request = $this->make([], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);

            $this->assertSame('XMLHttpRequest', $request->header('X-Requested-With'));
            $this->assertTrue($request->isAjax());
            $this->assertFalse($this->make()->isAjax());
        }

        public function testAttributesAreReadableAfterSet(): void
        {
            $request = $this->make();
            $request->setAttribute('route.params', ['1']);

            $this->assertSame(['1'], $request->attribute('route.params'));
            $this->assertNull($request->attribute('нет'));
        }

        public function testCaptureDropsQueryStringFromPath(): void
        {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_SERVER['REQUEST_URI']    = '/users?page=2&sort=asc';
            $_GET                      = ['page' => '2'];
            $_POST                     = [];
            $_FILES                    = [];
            $_COOKIE                   = [];

            $request = Request::capture();

            $this->assertSame('/users', $request->path());
            $this->assertSame('GET', $request->method());
            $this->assertSame('2', $request->query('page'));
        }
    }
