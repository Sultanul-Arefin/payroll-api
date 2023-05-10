<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckIfCompanyCreated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(Auth::check() && !is_null(Auth::user()->company_id)){
            return $next($request);
        } else{
            return apiResponse(
                data: [],
                message: 'Please, Create Company First!',
                status: 'error',
                statusCode: 401
            );
        }
    }
}
