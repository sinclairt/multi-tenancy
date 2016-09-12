<?php

namespace Sinclair\MultiTenancy\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Class IsMultiTenantUser
 * @package Sinclair\MultiTenancy\Traits
 */
trait IsMultiTenantUser
{
    use MorphToTenant;

    /**
     * @param array $roles
     *
     * @return bool
     */
    public function hasRoles( array $roles = [ ])
    {
        return sizeof(array_intersect($this->roles->pluck('name')->toArray(), $roles)) > 0;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(config('multi-tenancy.role.class'));
    }
}