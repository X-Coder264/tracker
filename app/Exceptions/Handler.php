<?php

declare(strict_types=1);

namespace App\Exceptions;

use CloudCreativity\LaravelJsonApi\Exceptions\HandlesErrors;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Sentry\State\HubInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

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

    public function report(Throwable $e)
    {
        if ($this->container->bound('sentry') && $this->shouldReport($e)) {
            /** @var HubInterface $sentry */
            $sentry = $this->container->make('sentry');
            $sentry->captureException($e);
        }

        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function render($request, Throwable $e)
    {
        if ($this->isJsonApi($request, $e)) {
            return $this->renderJsonApi($request, $e);
        }

        return parent::render($request, $e);
    }
}
