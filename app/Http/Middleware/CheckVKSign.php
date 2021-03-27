<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckVKSign
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $launchParams = $request->header('x-launch-params');

        $query_params = [];
        parse_str(parse_url($launchParams, PHP_URL_QUERY), $query_params);

        $sign_params = [];
        foreach ($query_params as $name => $value)
        {
            if (strpos($name, 'vk_') !== 0)
            {
              continue;
            }

          $sign_params[$name] = $value;
        }

        ksort($sign_params);
        $sign_params_query = http_build_query($sign_params);
        $sign = rtrim(strtr(base64_encode(hash_hmac('sha256', $sign_params_query, env('VK_SIGN'), true)), '+/', '-_'), '=');

        $status = array_key_exists('sign', $query_params) && $sign === $query_params['sign'];

        if ($status)
        {
            $request->launchParams = $query_params;
            return $next($request);
        }

        return response()->json(['errors' => ['invalid sign']], 401);
    }
}
