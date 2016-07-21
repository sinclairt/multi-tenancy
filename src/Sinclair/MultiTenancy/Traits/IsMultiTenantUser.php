<?php

namespace Sinclair\MultiTenancy\Traits;

use Illuminate\Database\Eloquent\Builder;

trait IsMultiTenantUser
{
    use MorphToTenant;

    /**
     * @param array $roles
     *
     * @param bool $withScopes
     *
     * @return bool
     */
    public function hasRoles( array $roles = [ ], $withScopes = true )
    {
        $query = $withScopes ? $this->newQuery() : $this->newQueryWithoutScopes();

        return $query->where('id', $this->id)
                     ->whereHas('roles', function ( Builder $query ) use ( $roles, $withScopes )
                     {
                         $query->whereIn('name', $roles, 'or');
                     })
                     ->count() > 0;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(config('multi-tenancy.role.class'));
    }
}