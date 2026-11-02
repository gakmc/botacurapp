<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Reingreso desde la ventana de sesión expirada (AJAX): responder JSON
     * con el nuevo token en vez de redirigir, para no recargar la página.
     */
    protected function authenticated(Request $request, $user)
    {
        if ($request->expectsJson()) {
            return response()->json(['token' => csrf_token()]);
        }
    }

    /**
     * Después de cerrar sesión, volver al login en vez del welcome.
     */
    protected function loggedOut(Request $request)
    {
        return redirect()->route('login');
    }
}
