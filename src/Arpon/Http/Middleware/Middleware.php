<?php

namespace Arpon\Http\Middleware;

use Closure;
use Arpon\Http\Request;
use Arpon\Http\Response;

interface Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param  Closure  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response;
}
