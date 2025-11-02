<?php

namespace Sbsagar\Toon\Facades;

use Illuminate\Support\Facades\Facade;

class Toon extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'toon';
    }
}
