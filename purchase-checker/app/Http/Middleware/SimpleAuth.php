<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SimpleAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! session('logged_in')) {
            return redirect()->route('login');
        }

        $role = session('user_role', 'super_admin');
        if ($role === 'managing_director' && ! $request->routeIs('home', 'history.month', 'logout')) {
            abort(403);
        }

        if (! in_array($role, ['super_admin', 'managing_director'], true)) {
            abort(403);
        }

        return $next($request);
    }
}
