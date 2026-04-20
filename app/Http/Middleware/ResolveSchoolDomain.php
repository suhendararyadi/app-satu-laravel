<?php

namespace App\Http\Middleware;

use App\Models\Team;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveSchoolDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $team = Team::where('custom_domain', $host)->first();

        if ($team) {
            $request->server->set(
                'REQUEST_URI',
                '/schools/'.$team->slug.$request->getPathInfo()
            );
        }

        return $next($request);
    }
}
