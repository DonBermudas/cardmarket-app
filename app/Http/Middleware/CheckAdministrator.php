<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAdministrator
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

        // Si dentro del objeto user, su role coincide con Administrador recibirá permiso
        if($user->role == "Administrador"){
            return $next($request);
        }else{
            $answer['msg'] = "No cuenta con los permisos necesarios.";
        }        

        return response()->json($answer);
        
    }
}
