<?php

namespace Sinclair\MultiTenancy\Traits;

/**
 * Class MorphToTenant
 * @package Sinclair\MultiTenancy\Traits
 */
trait MorphToTenant
{
    /**
     * Get all of the tags for the post.
     *
     * return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function tenants()
    {
        return $this->morphToMany(config('multi-tenancy.relationship.class'), config('multi-tenancy.relationship.polymorph-term'));
    }

    /**
     * Automatically attach the object to the company in the session
     */
    public static function bootMorphToTenant()
    {
        static::addGlobalScope(new \Sinclair\MultiTenancy\Scopes\MorphToTenant());

        $tenant = getTenantBySlug();

        static::created(function ( $model ) use ( $tenant )
        {
            if ( !is_null($tenant) )
                $model->tenants()
                      ->attach($tenant->id);

        });

        static::updated(function ( $model ) use ( $tenant )
        {
            if ( !is_null($tenant) && !in_array($tenant->id, $model->tenants->pluck('id')
                                                                            ->toArray())
            )
                $model->tenants()
                      ->attach($tenant->id);
        });
    }

    /**
     * @param $query
     *
     * @return mixed
     */
    public function scopeRestrictTenant( $query )
    {
        return $query->whereHas(str_plural(config('multi-tenancy.relationship.name')), function ( $q )
        {
            $q->where(config('multi-tenancy.relationship.table') . '.' . config('multi-tenancy.relationship.slug_column_name'), TENANT_SLUG);
        });
    }

    /**
     * @param $model
     *
     * @return bool
     */
    public function belongsToTenant( $model = null )
    {
        $model = is_null($model) ? $this : $model;

        return is_null(TENANT_SLUG) ? false : $model
                                                  ->{str_plural(config('multi-tenancy.relationship.name'))}()
                                                  ->where(config('multi-tenancy.relationship.table') . '.' . config('multi-tenancy.relationship.slug_column_name'), TENANT_SLUG)
                                                  ->count() > 0;
    }
}