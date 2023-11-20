<?php

namespace App\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  Request  $request
     * @return JsonResponse|\Illuminate\Http\Response|Response
     *
     * @throws Throwable
     */
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof NotFoundHttpException) {
            $response = [
                'status' => 'error',
                'message' => 'Url Not Found!',
                'data' => [],
            ];

            return response()->json($response, 404);
        }
        if ($exception instanceof HttpException) {
            $response = [
                'status' => 'error',
                'message' => $exception->getMessage(),
                'data' => [],
            ];

            return response()->json($response, $exception->getStatusCode());
        }
        if ($exception instanceof ValidationException) {
            $response = [
                'status' => 'error',
                'message' => implode(
                    ',',
                    collect($exception->errors())
                        ->flatten()
                        ->toArray()
                ),
                'data' => [
                    'errors' => $exception->errors(),
                ],
            ];

            return response()->json($response, 422);
        }
        if ($exception instanceof ModelNotFoundException) {
            $response = [
                'status' => 'error',
                'message' => 'Resource Not Found',
                'data' => [],
            ];

            return response()->json($response, 404);
        }
        if ($exception instanceof RouteNotFoundException) {
            $response = [
                'status' => 'error',
                'message' => 'Unauthenticated Request!',
                'data' => [],
            ];

            return response()->json($response, 401);
        }
        if ($exception instanceof MethodNotAllowedHttpException) {
            $response = [
                'status' => 'error',
                'message' => 'Method Not Allowed!',
                'data' => [],
            ];

            return response()->json($response, 404);
        }
        if ($exception instanceof CustomException) {//custom exception handle if need anywhere
            $response = [
                'status' => 'error',
                'message' => $exception->getMessage(),
                'data' => [],
            ];

            return response()->json($response, $exception->getCode());
        }

        return parent::render($request, $exception);
    }
}
