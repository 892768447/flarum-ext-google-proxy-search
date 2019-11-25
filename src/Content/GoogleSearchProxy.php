<?php

namespace Irony\Google\Search\Proxy\Content;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\HtmlResponse;

class GoogleSearchProxy implements RequestHandlerInterface
{
    const HOST = 'www.google.com';
    
    private function query($q, $num, $hl)
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://' . GoogleSearchProxy::HOST . '/search?newwindow=1&safe=active&num=' . $num . '&hl=' . $hl . '&gws_rd=ssl&q=' . $q);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.87 Safari/537.36');
            $headers = [
                'accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3',
                'accept-language:' . $hl . ',zh;q=0.9, en;q=0.8',
                'dnt:1',
                'referer:https://' . GoogleSearchProxy::HOST . '/',
                'sec-ch-ua:Google Chrome 78',
                'sec-fetch-dest:document',
                'sec-fetch-mode:navigate',
                'sec-fetch-site:same-origin',
                'sec-origin-policy:0',
                'upgrade-insecure-requests:1'
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $htmls = curl_exec($ch);
            curl_close($ch);
            if ($htmls === false) {
                return '404 Not Found';
            } else {
                return str_replace(GoogleSearchProxy::HOST, $_SERVER['HTTP_HOST'], $htmls);
            }
        } catch (Exception $e) {
            return '404 Not Found';
        }
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute('session');
        $user_id = $session->get('user_id') ?: '';
        $q = urlencode(trim(array_get($request->getQueryParams(), 'q')));
        if ($user_id === '' || empty($user_id)) {
            // 没有登录
            $url = ((int)$_SERVER['SERVER_PORT'] == 443 ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
            return new HtmlResponse('<!Doctype html><html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /><title>哦豁!</title><script>alert("请先登录后再使用，谢谢！");window.location.href = "' . $url . '";</script></head><body></body></html>');
        } else {
            // 谷歌搜索结果并返回
            $num = trim(array_get($request->getQueryParams(), 'num')) ?: '50';
            $hl = trim(array_get($request->getQueryParams(), 'hl')) ?: 'zh-CN';
            return new HtmlResponse($this->query($q, $num, $hl));
        }
    }
}
