<?php

namespace App\Http\Middleware;

use Closure;

class AuthKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $token = $request->header('App-Key');
        if($token != '#th3m4tr1x2021'){
            return response()->json(['message' => 'Auth Key not found'], 401);
        }
        return $next($request);
    }
}
