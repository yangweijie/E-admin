<?php

namespace Eadmin\middleware;

use app\common\facade\Token;
use app\model\User;


class Auth
{
    public function handle($request, \Closure $next)
    {
        if(Token::auth()){
            return $next($request);
        }
    }
}
