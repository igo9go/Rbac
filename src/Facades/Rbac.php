<?php

namespace Gundy\Rbac\Facades;

use Illuminate\Support\Facades\Facade;

class Rbac extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'rbac';
    }
}