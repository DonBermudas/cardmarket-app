<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class CheckToken
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
        // Este primer middleware es un filtro para comprobar que el usuario que se esta logeando existe, para ello recibimos un api token y buscamos el usuario al que está asignado
        // También sirve para pasar el api token al registro de cartas y colecciones
        if($request->has('api_token')){

            // Compruebo que existe el usuario
            $apiToken = $request->input('api_token');
            $user = User::where('api_token', $apiToken)->first();
            // Si el user no existe notificamos un error
            if(!$user){
                $answer['msg'] = "El user no existe";
            }else{
                $request->user = $user;
                return $next($request);
            }
            
        }else{
            $answer['msg'] = "No hay api token";
        }

        return response()->json($answer);
    }
}
