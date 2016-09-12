<?php

namespace Sinclair\MultiTenancy\Auth;

use Sinclair\MultiTenancy\Scopes\BelongsToTenant;
use Sinclair\MultiTenancy\Scopes\BelongsToTenantThrough;
use Sinclair\MultiTenancy\Scopes\MorphToTenant;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Support\Str;

/**
 * Class Tenant
 * @package App\Auth
 */
class Tenant extends EloquentUserProvider implements UserProvider
{

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array $credentials
     *
     * @return mixed
     */
    public function retrieveByCredentials( array $credentials )
    {
        if ( empty( $credentials ) )
        {
            return null;
        }

        // First we will add each credential element to the query as a where clause.
        // Then we can execute the query and, if we found a user, return it in a
        // Eloquent User "model" that will be utilized by the Guard instances.
        $query = $this->createModel()
                      ->newQuery()
                      ->withoutGlobalScopes([ MorphToTenant::class, BelongsToTenant::class, BelongsToTenantThrough::class ]);

        foreach ( $credentials as $key => $value )
            if ( !Str::contains($key, 'password') )
                $query->where($key, $value);

        return $query->first();
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  array $credentials
     *
     * @return bool
     */
    public function validateCredentials( UserContract $user, array $credentials )
    {
        $plain = $credentials[ 'password' ];

        $passwordCheck = $this->hasher->check($plain, $user->getAuthPassword());

        return $passwordCheck && ( $user->belongsToTenant() || $user->hasRoles(config('multi-tenancy.ignore-roles')) );
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed $identifier
     *
     * @return mixed
     */
    public function retrieveById( $identifier )
    {
        return $this->createModel()
                    ->newQuery()
                    ->withoutGlobalScopes([ MorphToTenant::class, BelongsToTenant::class, BelongsToTenantThrough::class ])
                    ->find($identifier);
    }
}