<?php

namespace Sinclair\MultiTenancy\Scopes;

use App\Traits\MorphToTenant as MorphToCompanyTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Sinclair\MultiTenancy\Traits\MorphToTenant;

/**
 * Class BelongsToCompanyThrough
 * @package Sinclair\MultiTenancy\Scopes
 */
class BelongsToTenantThrough extends MultiTenantScope implements Scope
{
    /**
     * @var
     */
    private $relationship;

    /**
     * BelongsToCompanyThrough constructor.
     *
     * @param $relationship
     */
    public function __construct( $relationship )
    {
        $this->relationship = $relationship;
    }

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

        if ( is_array($this->relationship) )
        {
            return $builder->where(function ( $query ) use ( $model )
            {
                foreach ( $this->relationship as $i => $relationship )
                {
                    $this->query($query, $model, $relationship, $i != 0);
                }
            });
        }

        return $this->query($builder, $model, $this->relationship);
    }

    /**
     * @param Builder $builder
     *
     * @param $model
     * @param $relationship
     * @param bool $or
     *
     * @return Builder|static
     */
    private function query( Builder &$builder, Model $model, $relationship, $or = false )
    {
        $function = $or ? 'orWhereHas' : 'whereHas';

        return $builder->{$function}($relationship, function ( $query ) use ( $model, $relationship )
        {
            $relationship = $this->isMorph($model, $relationship) ? str_plural(config('multi-tenant.relationship.name')) : config('multi-tenant.relationship.name');

            $query->whereHas($relationship, function ( $query )
            {
                $query->where(config('multi-tenancy.relationship.table') . '.' . config('multi-tenancy.relationship.slug_column_name'), TENANT_SLUG);
            });
        });
    }

    /**
     * @param Model $model
     *
     * @param $relationship
     *
     * @return mixed
     */
    private function getRelatedModel( Model $model, $relationship )
    {
        if ( sizeof(explode('.', $relationship)) == 1 )
            return $model->$relationship()
                         ->getQuery()
                         ->getModel();

        return null;
    }

    /**
     * If there are multiple relationships, we need to get the resulting model of each one via the
     * relationship method, as chaining the relationships wont work here as the models do not
     * technically exist, so we will use the related model method for the class uses call
     *
     * @param $model
     * @param $relationship
     *
     * @return mixed
     */
    private function isMorph( $model, $relationship )
    {
        $methods = $this->getRelatedModelMethods($model, $relationship);

        if ( empty( $methods ) )
        {
            foreach ( explode('.', $relationship) as $i => $part )
                $model = $this->getRelatedModel($model, $part);

            $methods = class_uses($model);
        }

        return in_array(MorphToTenant::class, $methods);
    }

    /**
     * @param $model
     * @param $relationship
     *
     * @return array
     */
    private function getRelatedModelMethods( $model, $relationship )
    {
        $related = $this->getRelatedModel($model, $relationship);

        return is_null($related) ? [ ] : class_uses($related);
    }
}