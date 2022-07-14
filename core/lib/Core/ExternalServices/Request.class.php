<?php
    /**
     * Copyright (c) 2022 Roman Grinko <rsgrinko@gmail.com>
     * Permission is hereby granted, free of charge, to any person obtaining
     * a copy of this software and associated documentation files (the
     * "Software"), to deal in the Software without restriction, including
     * without limitation the rights to use, copy, modify, merge, publish,
     * distribute, sublicense, and/or sell copies of the Software, and to
     * permit persons to whom the Software is furnished to do so, subject to
     * the following conditions:
     * The above copyright notice and this permission notice shall be included
     * in all copies or substantial portions of the Software.
     * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
     * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
     * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
     * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
     * CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
     * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
     * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
     */

    namespace Core\ExternalServices;

    use Core\CoreException;

    class Request
    {
        private $httpStatus     = null;

        private $httpCode       = null;

        private $maxRedirects   = 2;

        private $requestHeaders = [];

        /**
         * @var false|string
         */
        private $responseBody   = null;

        private $responseHeader = null;

        private $timeout        = 5;

        /**
         * @return string|null
         */
        public function getResponseBody(): ?string
        {
            return $this->responseBody;
        }

        /**
         * @return null
         */
        public function getResponseHeader()
        {
            return $this->responseHeader;
        }

        private $usertAgent    = null;

        private $requestType   = null;

        private $url           = null;

        private $params        = [];

        private $referer       = '';

        private $usePostMethod = false;

        public function __construct()
        {
        }

        public function setUrl(?string $url): self
        {
            $this->url = $url;
            return $this;
        }

        public function post(): self
        {
            $this->usePostMethod = true;
            return $this;
        }

        public function get(): self
        {
            $this->usePostMethod = true;
            return $this;
        }

        public function setParams(?array $params = null): self
        {
            $this->params = $params ?: [];
            return $this;
        }

        public function setHeaders(?array $arHeaders = null): self
        {
            $this->requestHeaders = $arHeaders ?: [];
            return $this;
        }

        public function setTimeout(int $timeout): self
        {
            $this->timeout = $timeout;
            return $this;
        }

        public function setMaxRedirects(int $redirects): self
        {
            $this->maxRedirects = $redirects;
            return $this;
        }


        public function setReferer(?string $referer = null): self
        {
            $this->referer = $referer ?: '';
            return $this;
        }

        public function setUserAgent(?string $userAgent = null): self
        {
            $this->userAgent = $userAgent ?: '';
            return $this;
        }

        public function getCode(): ?int
        {
            return $this->httpCode;
        }

        public function send()
        {
            $res = $this->sendRequest();
            return $res;
        }

        /**
         * @throws CoreException
         */
        private function sendRequest(): ?string
        {
            if (empty($this->url)) {
                throw new CoreException('Адрес запроса не задан');
            }

            if ($this->usePostMethod === true) {
                $ch = curl_init($this->url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->params);
            } else {
                $ch = curl_init($this->url . '?' . http_build_query($this->params));
            }

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_REFERER, $this->referer);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($ch, CURLOPT_MAXREDIRS, $this->maxRedirects);
            curl_setopt($ch, CURLOPT_USERAGENT, $this->usertAgent);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->requestHeaders);
            curl_setopt($ch, CURLOPT_HEADER, 1);

            $res = curl_exec($ch);

            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

            $this->responseHeader = substr($res, 0, $headerSize);
            $this->responseBody   = substr($res, $headerSize);

            $this->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);
            return $res;
        }
    }