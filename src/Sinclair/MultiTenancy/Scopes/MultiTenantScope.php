<?php

namespace Sinclair\MultiTenancy\Scopes;

/**
 * Class MultiTenantScope
 * @package Sinclair\MultiTenancy\Scopes
 */
abstract class MultiTenantScope
{
    /**
     * @return bool
     */
    public function shouldApply()
    {
        list( $user, $roles, $callback ) = $this->setUp();

        if ( !is_null($callback) )
            return call_user_func_array($callback, compact('user', 'roles'));

        if ( !is_null($user) && !empty( $roles ) )
            return !$user->hasRoles($roles);

        return config('multi-tenancy.should-apply-default', true);
    }

    /**
     * @return array
     */
    private function setUp()
    {
        return [
            $this->getAuthUser(),
            $this->getRolesToIgnore(),
            $this->getSuppliedCallback()
        ];
    }

    /**
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    private function getAuthUser()
    {
        return auth()->user();
    }

    /**
     * @return mixed
     */
    private function getRolesToIgnore()
    {
        return config('multi-tenancy.ignore-roles', []);
    }

    /**
     * @return mixed
     */
    private function getSuppliedCallback()
    {
        return config('multi-tenancy.should-apply-callback', null);
    }
}