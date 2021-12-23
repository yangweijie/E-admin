<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace Eadmin\middleware;

use Eadmin\Admin;
use think\Request;
class MultiApp
{
    public function handle(Request $request, \Closure $next)
    {
        $moudel = Admin::getAppName();
        $response = $next($request);
        $header = 'Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-CSRF-TOKEN, X-Requested-With, multi-app, version';
        $allow = $response->getHeader('Access-Control-Allow-Headers');
        if(!empty($allow)){
            $header .=', multi-app, version';
        }
        
        $response->header([
            'multi-app'=>$moudel,
            'Access-Control-Expose-Headers'=>'multi-app',
            'Access-Control-Allow-Headers'=>$header
        ]);
        return $response;
    }
}
