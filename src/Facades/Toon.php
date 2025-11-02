<?php
declare(strict_types=1);

namespace Sbsaga\Toon\Facades;

use Illuminate\Support\Facades\Facade;

class Toon extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'toon';
    }
}
