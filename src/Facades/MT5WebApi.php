<?php

namespace aemaddin\MT5WebApi\Facades;

use Illuminate\Support\Facades\Facade;

class MT5WebApi extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'mt5webapi';
    }
}
