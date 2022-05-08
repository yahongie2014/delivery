<?php

namespace App\Http\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\AuthenticationException;
use Closure;

use App\Admin;

class IsAdmin
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
        $user_id = Auth::id();

        if(Admin::where('user_id', '=', $user_id)->first())
            return $next($request);
        else
            return redirect('notadmin');

    }
}
