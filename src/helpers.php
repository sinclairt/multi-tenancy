<?php

use Dotenv\Dotenv;

if ( !function_exists('getTenantBySlug') )
{
    /**
     * @return mixed
     */
    function getTenantBySlug()
    {
        return app(config('multi-tenancy.relationship.class'))
            ->where(config('multi-tenancy.relationship.slug_column_name'), TENANT_SLUG)
            ->first();
    }
}

if ( !function_exists('bootstrapMultiTenancy') )
{
    function bootstrapMultiTenancy( $app )
    {
        ( new Dotenv($app->environmentPath(), $app->environmentFile()) )->load();

        if ( php_sapi_name() == "cli" )
        {
            if ( !defined('TENANT_SLUG') )
                define('TENANT_SLUG', env('TENANT_SLUG'));
        }
        elseif ( isset( $_SERVER[ 'HTTP_HOST' ] ) )
        {
            if ( !defined('TENANT_SLUG') )
            {
                $subDomain = str_replace('.', '', str_replace($_SERVER[ 'SERVER_NAME' ], '', $_SERVER[ 'HTTP_HOST' ]));

                $tenant = strlen($subDomain) > 0 ? $subDomain : 'public';

                define('TENANT_SLUG', $tenant);
            }
        }
        else
        {
            if ( !defined('TENANT_SLUG') )
                define('TENANT_SLUG', env('TENANT_SLUG'));
        }
    }
}