<?php

namespace App\Http\Middleware;

use App\Http\Requests\Request;
use App\Utils\ClientSettings\Facade;
use Closure;

class ClientApp
{
    public function handle(Request $request, Closure $next)
    {
        $this->setClient($request);
        return $next($request);
    }

    protected function setClient(Request $request)
    {
        return Facade::setClientAppFromRequestRoute($request);
    }
}