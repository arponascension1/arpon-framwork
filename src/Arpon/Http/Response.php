<?php

namespace Arpon\Http;

use Arpon\View\View;

class Response
{
    protected string $content;
    protected int $statusCode;
    protected HeaderBag $headers;

    public function __construct(mixed $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->statusCode = $statusCode;
        $this->headers = new HeaderBag($headers);
        $this->setContent($content);
    }

    public function setContent(mixed $content): static
    {
        if ($content instanceof View) {
            $this->content = $content->render();
        } else {
            $this->content = (string) $content;
        }

        return $this;
    }

    public function send(): void
    {
        $this->sendHeaders();
        $this->sendContent();
    }

    protected function sendHeaders(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers->all() as $name => $values) {
            foreach ($values as $value) {
                header($this->headers->normalizeKey($name) . ': ' . $value, false);
            }
        }
    }

    protected function sendContent(): void
    {
        echo $this->content;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function __toString(): string
    {
        return $this->getContent();
    }

    public function setStatusCode(int $statusCode): static
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function header(string $name, string $value): static
    {
        $this->headers->set($name, $value);
        return $this;
    }
}