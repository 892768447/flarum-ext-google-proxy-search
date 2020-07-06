<?php

namespace Irony\Google\Search\Proxy\Content;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\RedirectResponse;

class GoogleSearchProxyJump implements RequestHandlerInterface
{

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // 重定向
        $query = $_SERVER['QUERY_STRING'];
        if (strpos($query, 'url=') > 0) {
        	return new RedirectResponse(explode('&usg=', urldecode(explode('url=', $query)[1]))[0]);
        }
    }
}
