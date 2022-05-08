<?php

namespace App\Http\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Session ;
use Closure;

use App\Provider;

class IsProviderApi
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
        //$user_id = auth('api')->user()->id;

        $user_id = auth('api')->user()->id;
        Session::put('login_type',\PROVIDER);

        if(Provider::where('user_id', '=', $user_id)->first())
            return $next($request);
        else
            return response()->json(['status' => false , 'error' => 'you don\'t have provider account'] , 401);
    }
}
