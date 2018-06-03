<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use CloudCreativity\LaravelJsonApi\Exceptions\HandlesErrors;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    use HandlesErrors;

    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        JsonApiException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param Exception $exception
     *
     * @return mixed|void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param Request   $request
     * @param Exception $exception
     *
     * @return Response
     */
    public function render($request, Exception $exception)
    {
        if ($this->isJsonApi()) {
            return $this->renderJsonApi($request, $exception);
        }

        return parent::render($request, $exception);
    }
}
