<?php

namespace Mostafaznv\SimpleSDP;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Mostafaznv\SimpleSDP\SdpResolver
 */
class SimpleSDP extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'SimpleSDP';
    }
}