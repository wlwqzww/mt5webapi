<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use aemaddin\mt5webapi\MTConSymbol;
use aemaddin\mt5webapi\MTWebAPI;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Route;

Route::prefix('mt5')->group(function () {

    Route::get('/history', function () {
        $api = new MTWebAPI;
        $symbol = new MTConSymbol;

        $api->Connect(
            config('caveo.mt5.ip'),
            config('caveo.mt5.port'),
            30,
            config('caveo.mt5.login'),
            config('caveo.mt5.password'));

        $api->SetLoggerIsWrite(true);
        $api->SetLoggerFilePath(storage_path('logs'));
        $api->SetLoggerWriteDebug(true);

        if ($api->IsConnected() ? 'Connected' : "Disconnected") {
            $now = CarbonImmutable::now();
            $now->setYear(2018);

            $api->SymbolGet('EURUSD', $symbol);
        }

        dd($symbol);
    });
});