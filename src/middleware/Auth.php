<?php

namespace Eadmin\middleware;

use app\model\User;
use Eadmin\support\Token;


class Auth
{
    public function handle($request, \Closure $next)
    {
        if(Token::auth()){
            return $next($request);
        }
    }
}
