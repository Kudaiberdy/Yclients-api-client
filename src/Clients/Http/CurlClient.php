<?php

declare(strict_types=1);

namespace Clients\Http;

use Clients\Contracts\HttpClient;
use CurlHandle;

class CurlClient implements HttpClient
{
    /**
     * @var CurlHandle|bool
     */
    private readonly CurlHandle|bool $ch;

    /**
     * @var array
     */
    private array $curlOptParams = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FAILONERROR => false,
        CURLOPT_HEADER => false,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0
    ];

    /**
     * @param array|null $curlOptParams
     */
    public function __construct(array $curlOptParams = null)
    {
        $this->ch = curl_init();
        $this->setCurlOptParams($curlOptParams ?? []);
        curl_setopt_array($this->ch, $this->curlOptParams);
    }

    public function __destruct()
    {
        curl_close($this->ch);
    }

    /**
     * @param array $params
     * @return $this
     */
    public function setCurlOptParams(array $params): static
    {
        foreach ($params as $key => $value) {
            $this->curlOptParams[$key] = $value;
        }

        return $this;
    }

    /**
     * @param string $url
     * @param ?array $queryParams
     * @return string
     */
    public function get(string $url, array $queryParams = null): string
    {
        return $this->request('GET', $url);
    }

    /**
     * @param string $url
     * @param array|null $data
     * @param bool $asJson
     * @return string
     */
    public function post(string $url, array $data = null, bool $asJson = true): string
    {
        $req = $data ? $this->setBody($data, $asJson) : $this;

        return $req->request('POST', $url);
    }

    /**
     * @param string $url
     * @param string|array|null $body
     * @return string
     */
    public function put(string $url, string|array $body = null): string
    {
        $req = $body ? $this->setBody($body) : $this;

        return $req->request('PUT', $url);
    }

    /**
     * @param string $url
     * @return string
     */
    public function delete(string $url): string
    {
        return $this->request('DELETE', $url);
    }

    /**
     * @param string $cookie
     * @return $this
     */
    public function setCookie(string $cookie): static
    {
        // TODO: Implement setCookie() method.
        return $this;
    }

    /**
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers): static
    {
        $convertedHeaders = array_map(
            fn (string $header, string $value) => "$header: $value",
            array_keys($headers),
            array_values($headers)
        );
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $convertedHeaders);

        return $this;
    }

    /**
     * @param array $data
     * @param bool $asJson
     * @return $this
     */
    public function setBody(array $data, bool $asJson = false): static
    {
        $parsedData = $asJson ? json_encode($data, JSON_UNESCAPED_UNICODE) : http_build_query($data);

        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $parsedData);

        return $this;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function setQueryParams(array $params): static
    {
        // TODO: Implement setCookie() method.
        return $this;
    }

    /**
     * @param int $timeout
     * @return $this
     */
    public function setTimeout(int $timeout): static
    {
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $timeout);

        return $this;
    }

    /**
     * @return array
     */
    public function getError(): array
    {
        return [curl_errno($this->ch) => curl_error($this->ch)];
    }

    /**
     * @param string $url
     * @return void
     */
    private function setUrl(string $url): void
    {
        curl_setopt($this->ch, CURLOPT_URL, $url);
    }

    /**
     * @param string $method
     * @param string $url
     * @return bool|string
     */
    private function request(string $method, string $url): bool|string
    {
        if ($method === 'GET') {
            curl_setopt($this->ch, CURLOPT_HTTPGET, true);
        }
        if ($method === 'POST') {
            curl_setopt($this->ch, CURLOPT_POST, true);
        }
        if ($method === 'PUT' || $method === 'DELETE') {
            curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $method);
        }
        $this->setUrl($url);
        $res = curl_exec($this->ch);
        curl_reset($this->ch);

        return $res;
    }
}
