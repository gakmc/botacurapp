<?php
namespace App\Exceptions;

use App\Providers\EventServiceProvider;
use Exception;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;

class Handler extends ExceptionHandler
{

    protected $dontReport = [
        //
    ];


    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];


    public function report(Exception $exception)
    {
        parent::report($exception);
    }


    public function render($request, Exception $exception)
    {
        // Sesión expirada al enviar un formulario (CSRF 419)
        if ($exception instanceof TokenMismatchException && ! $request->expectsJson()) {
            return $this->sesionExpirada(419);
        }

        return parent::render($request, $exception);
    }

    protected function unauthenticated($request, \Illuminate\Auth\AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'No autenticado.'], 401);
        }

        // Tuvo sesión antes (cookie marcadora) → expiró; si no, es un visitante sin sesión
        if ($request->hasCookie(EventServiceProvider::COOKIE_SESION)) {
            if ($request->isMethod('GET')) {
                // Tras volver a iniciar sesión, regresa a la página que intentaba abrir
                $request->session()->put('url.intended', $request->fullUrl());
            }

            return $this->sesionExpirada(401);
        }

        return redirect()->guest(route('login'));
    }

    private function sesionExpirada($status)
    {
        return response()
            ->view('errors.419', [], $status)
            ->withCookie(Cookie::forget(EventServiceProvider::COOKIE_SESION));
    }

}
