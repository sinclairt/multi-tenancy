<?php

namespace Sinclair\MultiTenancy\Providers;

use Sinclair\MultiTenancy\Auth\Tenant;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Support\ServiceProvider;

class MultiTenancyServiceProvider extends ServiceProvider
{
    /**
     * Register any application authentication / authorization services.
     *
     * @param  \Illuminate\Contracts\Auth\Access\Gate $gate
     *
     * @return void
     */
    public function boot( GateContract $gate )
    {
        \Auth::provider('tenant', function ( $app, array $config )
        {
            return new Tenant($app[ 'hash' ], $config[ 'model' ]);
        });

        $this->publishes([
            __DIR__ . '/../../../config/multi-tenancy.php' => config_path('multi-tenancy.php'),
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}