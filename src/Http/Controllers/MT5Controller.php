<?php

namespace aemaddin\mt5\Http\Controllers;

use aemaddin\mt5webapi\MTWebAPI;
use App\Http\Controllers\Controller;

class MT5Controller extends Controller
{
    protected $api;

    public function __construct(MTWebAPI $api)
    {
        $api->Connect(
            config('caveo.mt5.ip'),
            config('caveo.mt5.port'),
            30,
            config('caveo.mt5.login'),
            config('caveo.mt5.password'));

        $api->SetLoggerIsWrite(true);
        $api->SetLoggerFilePath(storage_path('logs'));
        $api->SetLoggerWriteDebug(true);

        $this->api = $api;
    }
}
