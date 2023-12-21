<?php

namespace Kaspi;

class Response
{
    protected $body;
    protected $headers;
    protected $statusCode;
    protected $responsePhrase;

    public function __construct(ResponseBody $responseBody, ResponseHeaders $responseHeaders)
    {
        $this->body = $responseBody;
        $this->headers = $responseHeaders;
        $this->statusCode = ResponseCode::OK;
        $this->responsePhrase = ResponseCode::PHRASES[$this->statusCode] ?? '';
    }

    public function setResponsePhrase(string $phrase): self
    {
        $this->responsePhrase = $phrase;

        return $this;
    }

    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    public function setHeader(string $header, string $value): self
    {
        $this->headers = clone $this->headers;
        $this->headers->set($header, $value);

        return $this;
    }

    public function getHeader(string $header): ?string
    {
        return $this->headers->get()[$header] ?? null;
    }

    public function resetHeaders(): void
    {
        $this->headers->reset();
    }

    public function setBody(string $body = ''): self
    {
        $this->body = clone $this->body;
        $this->body->write($body);

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body->getContents();
    }

    public function resetBody(): void
    {
        $this->body->close();
    }

    public function setJson($data, int $options = 0, int $depth = 512): self
    {
        $this->setBody(json_encode($data, $options, $depth))
            ->setHeader('Content-Type', 'application/json');

        return $this;
    }

    public function redirect(string $url): void
    {
        $this->setHeader('location', $url)->setStatusCode(ResponseCode::MOVED_TEMPORARILY)
            ->setResponsePhrase(ResponseCode::PHRASES[ResponseCode::MOVED_TEMPORARILY]);
    }

    public function errorHeader(int $responseCode): self
    {
        $this->setStatusCode($responseCode)->setResponsePhrase(ResponseCode::PHRASES[$responseCode]);

        return $this;
    }

    public function emit(): ?string
    {
        if ($ContentLength = $this->body->getSize()) {
            $this->headers->set('Content-Length', $ContentLength);
        }
        $header = sprintf('HTTP/1.1 %s %s', $this->statusCode, $this->responsePhrase);
        header($header);
        foreach ($this->headers->get() as $name => $value) {
            $header = sprintf('%s: %s', $name, $value);
            header($header);
        }

        return $this->body;
    }
}
