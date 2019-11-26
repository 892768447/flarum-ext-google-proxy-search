<?php

namespace Irony\Google\Search\Proxy\Content;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\HtmlResponse;

class GoogleSearchProxy implements RequestHandlerInterface
{
    const HOST = 'www.google.com';
    const CHARTS = ['/og/', '/_/', '/gb/', '/adsid/', '/widget/', '/verify/', '/xjs/', '/images/', '/complete/', '/gen_204'];
    
    private function query($url)
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.87 Safari/537.36');
            $headers = [
                'accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3',
                'accept-language:zh-CN,zh;q=0.9, en;q=0.8',
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
                $url = ((int)$_SERVER['SERVER_PORT'] == 443 ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/g?';
                $htmls = str_replace('www.gstatic.com', 'google.pyqt.site', $htmls);
                $htmls = str_replace('apis.google.com', 'google.pyqt.site', $htmls);
                $htmls = str_replace('ssl.gstatic.com', 'google.pyqt.site', $htmls);
                $htmls = str_replace('adservice.google.com', 'google.pyqt.site', $htmls);
                $htmls = str_replace('ogs.google.com', 'google.pyqt.site', $htmls);
                $htmls = str_replace('id.google.com', 'google.pyqt.site', $htmls);
                $htmls = str_replace(GoogleSearchProxy::HOST . '/', $_SERVER['HTTP_HOST'] . '/g?', $htmls);
                //$htmls = str_replace('www.google.com', 'google.pyqt.site', $htmls);
                // 替换字符串
                foreach (GoogleSearchProxy::CHARTS as $value) {
                    $htmls = str_replace('="' . $value, '="https://google.pyqt.site' . $value, $htmls);
                    $htmls = str_replace("='" . $value, "='https://google.pyqt.site" . $value, $htmls);
                    $htmls = str_replace('url(/', 'url(https://google.pyqt.site/', $htmls);
                    $htmls = str_replace('(g||"gen_204")', '"/google.pyqt.site/gen_204"', $htmls);
                }
                $htmls = str_replace('href="/', 'href="' . $url, $htmls);
                $htmls = str_replace('action="/', 'action="' . $url, $htmls);
                $htmls = str_replace('src="/', 'src="' . $url, $htmls);
                // $htmls = str_replace('="/', '="' . $url, $htmls);
                // $htmls = str_replace("='/", "='" . $url, $htmls);
                return $htmls;
            }
        } catch (Exception $e) {
            return '404 Not Found';
        }
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute('session');
        $user_id = $session->get('user_id') ?: '';
        if ($user_id === '' || empty($user_id)) {
            // 没有登录
            $url = ((int)$_SERVER['SERVER_PORT'] == 443 ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
            return new HtmlResponse('<!Doctype html><html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /><title>哦豁!</title><script>alert("请先登录后再使用，谢谢！");window.location.href = "' . $url . '";</script></head><body></body></html>');
        } else {
            // 谷歌搜索结果并返回
            $query = $_SERVER['QUERY_STRING'];
            if (strpos($query, '&q=') > 0 && strpos($query, 'search?') == false) {
            	$query = 'search?' . $query;
            }
            $url = 'https://' . GoogleSearchProxy::HOST . '/' . $query;
            return new HtmlResponse($this->query($url));
        }
    }
}
