<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckSellers
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Recibes el objeto usuario entero y lo metes en una variable
        $user = $request->user;

        // Sólo admite profesionales o particulares
        if($user->role == "Profesional" || $user->role == "Particular"){
            return $next($request);
        }else{
            $answer['msg'] = "Sólo los profesionales o particulares pueden realizar ventas.";
        }        

        return response()->json($answer);
    }
}
