<?php

namespace Kaspi;

class ResponseCode
{
    public const OK = 200;
    public const MOVED_PERMANENTLY = 301;
    public const BAD_REQUEST = 400;
    public const UNAUTHORIZED = 401;
    public const FORBIDDEN = 403;
    public const NOT_FOUND = 404;
    public const METHOD_NOT_ALLOWED = 405;
    public const INTERNAL_SERVER_ERROR = 500;

    public const PHRASES = [
        self::OK => 'OK',
        self::MOVED_PERMANENTLY => 'Moved Permanently',
        self::BAD_REQUEST => 'Bad Request',
        self::UNAUTHORIZED => 'Unauthorized',
        self::FORBIDDEN => 'Forbidden',
        self::NOT_FOUND => 'Not Found',
        self::METHOD_NOT_ALLOWED => 'Method Not Allowed',
        self::INTERNAL_SERVER_ERROR => 'Internal Server Error',
    ];
}
