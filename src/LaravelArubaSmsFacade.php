<?php

namespace OfflineAgency\LaravelArubaSms;

use Illuminate\Support\Facades\Facade;

/**
 * @see \OfflineAgency\LaravelArubaSms\Skeleton\SkeletonClass
 */
class LaravelArubaSmsFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravel-aruba-sms';
    }
}
