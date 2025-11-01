<?php

// app/Exceptions/Handler.php

namespace Arpon\Foundation\Exceptions;

use Exception;
use Arpon\Http\Exceptions\NotFoundHttpException;
use Arpon\Http\Request;
use Arpon\Http\Response;
use Arpon\View\Exceptions\ViewNotFoundException;
use Arpon\Validation\ValidationException;
use Arpon\Http\RedirectResponse;

class Handler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected array $dontReport = [
        //
    ];

    /**
     * Report or log an exception.
     *
     * @param Exception $e
     * @return void
     */
    public function report(Exception $e)
    {
        // In a real application, you would send this to a logging service
        // like Sentry, Flare, or just the local log files.
    }

    /**
     * @throws Exception
     */
    public function render(Request $request, Exception $e): Response|RedirectResponse
    {
        if ($e instanceof ValidationException) {
            return back()->withInput($request->all())->withErrors($e->errors()->toArray());
        }

        if ($e instanceof NotFoundHttpException) {
            return new Response(view('errors.minimal', ['code' => 404, 'message' => 'Not Found', 'title' => 'Page Not Found']), 404);
        }

        return new Response(view('errors.minimal', ['code' => 500, 'message' => 'Server Error', 'title' => 'Server Error', 'exception' => $e]), 500);
    }
}
