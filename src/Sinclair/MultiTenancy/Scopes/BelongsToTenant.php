<?php

namespace Sinclair\MultiTenancy\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Class BelongsToTenant
 * @package Sinclair\MultiTenancy\Scopes
 */
class BelongsToTenant extends MultiTenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $builder
     * @param  \Illuminate\Database\Eloquent\Model $model
     *
     * @return Builder
     */
    public function apply( Builder $builder, Model $model )
    {
        if ( $this->shouldApply() == false )
            return $builder;

        return $builder->whereHas(config('multi-tenancy.relationship.name'), function ( $query )
        {
            $query->where(config('multi-tenancy.relationship.table') . '.' . config('multi-tenancy.relationship.slug_column_name'), TENANT_SLUG);
        });
    }
}