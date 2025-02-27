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
        if (Auth::check() && ! is_null(Auth::user()->company_id) && Auth::user()->active_company) {
            if(Auth::user()->company->no_of_working_days_per_week){
                return $next($request);
            }
            else{
                return apiResponse(
                    data: [],
                    message: 'Please, Config The Working Days, Hours, & Holidays!',
                    status: "error",
                    statusCode: 409
                );
            }
        } else {
            return apiResponse(
                data: [],
                message: 'Please, Create Company First!',
                status: 'error',
                statusCode: 403
            );
        }
    }
}
