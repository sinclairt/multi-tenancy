<?php

namespace Sinclair\MultiTenancy\Traits;

use Sinclair\MultiTenancy\Scopes\BelongsToTenant as BelongsToTenantScope;

/**
 * Class BelongsToTenant
 * @package App\Traits
 */
trait BelongsToTenant
{
    /**
     *
     */
    public static function bootBelongsToTenant()
    {
        static::addGlobalScope(new BelongsToTenantScope);
    }

    /*
     * Relationships
     */

    /**
     * @return mixed
     */
    public function tenant()
    {
        return $this->belongsTo(config('multi-tenancy.relationship.class'), config('multi-tenancy.relationship.foreign_key'));
    }

    /*
     * Scopes
     */

    /**
     * @param $query
     *
     * @return mixed
     */
    public function scopeHasTenant( $query )
    {
        return $query->whereHas('tenant', function ( $query )
        {
            $query->where(config('multi-tenancy.relationship.table') . '.' . config('multi-tenancy.relationship.slug_column_name'), TENANT_SLUG);
        });
    }
}