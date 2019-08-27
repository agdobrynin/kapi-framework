<?php

namespace Kaspi;

class Response
{
    protected $body;
    protected $headers;
    protected $statusCode;
    protected $responsePhrase;

    public function __construct()
    {
        $this->body = new ResponseBody();
        $this->headers = new ResponseHeaders();
        $this->statusCode = ResponseCode::OK;
        $this->responsePhrase = ResponseCode::PHRASES[$this->statusCode] ?? '';
    }

    public function setResponsePhrase(string $phrase): void
    {
        $this->responsePhrase = $phrase;
    }

    public function setStatusCode(int $statusCode): Response
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    public function setHeader(string $header, string $value): Response
    {
        $clone = clone $this;
        $clone->headers->set($header, $value);

        return $clone;
    }

    public function getHeader(string $header): ?string
    {
        return $this->headers->get()[$header] ?? null;
    }

    public function setBody(string $body = ''): Response
    {
        $this->body->set($body);

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body->get();
    }

    public function setJson($data, int $options = 0, int $depth = 512): void
    {
        $this->setHeader('Content-Type', 'application/json')
            ->setBody(json_encode($data, $options, $depth));
    }

    public function redirect(string $url): void
    {
        $this->setHeader('location', $url)
            ->setStatusCode(ResponseCode::MOVED_PERMANENTLY)
            ->setResponsePhrase(ResponseCode::PHRASES[ResponseCode::MOVED_PERMANENTLY]);
    }

    public function errorHeader(int $responseCode): Response
    {
        $this->setStatusCode($responseCode)->setResponsePhrase(ResponseCode::PHRASES[$responseCode]);

        return $this;
    }

    public function emit(): ?string
    {
        header('HTTP/1.1 '.$this->statusCode.' '.$this->responsePhrase);
        foreach ($this->headers->get() as $header => $value) {
            header($header.': '.$value);
        }

        return $this->body->get();
    }
}
