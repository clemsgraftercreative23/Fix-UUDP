<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Fideloper\Proxy\TrustProxies as Middleware;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * Trusting '*' only in local makes asset()/url() automatically pick up
     * the X-Forwarded-Proto/Host headers a tunnel (ngrok, etc.) sends -- so
     * the app serves correct https:// asset URLs under a tunnel's
     * ever-changing subdomain without ever touching APP_URL in .env.
     * Never trust '*' outside local: that would let ANY client spoof these
     * headers (e.g. fake an https scheme to bypass secure-cookie checks).
     *
     * @var array|string|null
     */
    protected $proxies;

    public function __construct(\Illuminate\Contracts\Config\Repository $config)
    {
        parent::__construct($config);
        if (config('app.env') === 'local') {
            $this->proxies = '*';
        }
    }

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers = Request::HEADER_X_FORWARDED_ALL;
}
