<?php

namespace Clients\Contracts;

interface HttpClient
{
    /**
     * @param string $url
     * @return string
     */
    public function get(string $url): string;

    /**
     * @param string $url
     * @return string
     */
    public function post(string $url): string;

    /**
     * @param string $url
     * @return string
     */
    public function put(string $url): string;

    /**
     * @param string $url
     * @return string
     */
    public function delete(string $url): string;

    /**
     * @param string $cookie
     * @return $this
     */
    public function setCookie(string $cookie): static;

    /**
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers): static;

    /**
     * @param array $headers
     * @return $this
     */
    public function setBody(array $headers): static;

    /**
     * @param array $params
     * @return $this
     */
    public function setQueryParams(array $params): static;

    /**
     * @param int $timeout
     * @return $this
     */
    public function setTimeout(int $timeout): static;
}
