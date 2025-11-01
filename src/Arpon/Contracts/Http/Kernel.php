<?php

namespace Arpon\Contracts\Http;

use Arpon\Http\Request;
use Arpon\Http\Response;

interface Kernel
{
    /**
     * Handle an incoming HTTP request.
     *
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request): Response;

    /**
     * Perform any final actions for the request lifecycle.
     *
     * @param Request $request
     * @param  Response  $response
     * @return void
     */
    public function terminate(Request $request, Response $response): void;
}
