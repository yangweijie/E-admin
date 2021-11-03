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
        return Admin::plug()->getServiceProviders($name);
    }
}
