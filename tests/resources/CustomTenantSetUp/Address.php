<?php

namespace Testing\CustomTenant;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Sinclair\MultiTenancy\Scopes\BelongsToTenantThrough;

/**
 * Class Address
 *
 */
class Address extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [ 'house_number', 'street', 'line_1', 'line_2', 'city', 'county', 'postcode', 'country' ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [ ];

    /**
     * The dates that are returned as Carbon objects
     *
     * @var array
     */
    protected $dates = [ 'created_at', 'updated_at', 'deleted_at' ];

    /**
     *
     */
    public static function boot()
    {
        parent::boot();

        static::addGlobalScope(new BelongsToTenantThrough([ 'drivers.user', 'users', 'locations' ]));
    }

    /*
     * Relationships
     */

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function drivers()
    {
        return $this->morphedByMany(Driver::class, 'addressable');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function locations()
    {
        return $this->morphedByMany(Location::class, 'addressable');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function users()
    {
        return $this->morphedByMany(User::class, 'addressable');
    }
}