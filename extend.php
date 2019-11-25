<?php

namespace Irony\Google\Search\Proxy;

use Flarum\Extend;

return [
    (new Extend\Routes('forum'))
        ->get('/g', 'irony_google_search_proxy', Content\GoogleSearchProxy::class)
];
