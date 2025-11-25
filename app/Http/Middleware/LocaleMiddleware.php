<?php

namespace App\Http\Middleware;



use App\Helpers\Settings\SettingSite;


use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;

use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LocaleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next (\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return Response
     */




    public function handle(Request $request, Closure $next): Response
    {


        $settings = (new SettingSite())->settings();

        View::composer('*', function ($view) use ($settings) {
            $view->with(['settings' => $settings]);
        });

        return $next($request);
    }


}
