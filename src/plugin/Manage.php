<?php

namespace Eadmin\plugin;
use Eadmin\Admin;
use plugin\IDE;

/**
 * @mixin  IDE
 */
class Manage
{
    public function __get($name)
    {
        $plug = Admin::plug()->getServiceProviders($name);
        if(is_null($plug)){
            return $this;
        }
        return $plug;
    }
    public function __call($name, $arguments)
    {
       return null;
    }
}
