<?php

namespace Testing\CustomTenant;

use Illuminate\Database\Eloquent\SoftDeletes;
use Sinclair\MultiTenancy\Traits\IsMultiTenantUser;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use SoftDeletes, IsMultiTenantUser;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [ 'first_name', 'last_name', 'email', 'password' ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [ 'password', 'remember_token' ];

    /**
     * The dates that are returned as Carbon objects
     *
     * @var array
     */
    protected $dates = [ 'created_at', 'updated_at', 'deleted_at' ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function driver()
    {
        return $this->hasOne(Driver::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function addresses()
    {
        return $this->morphToMany(Address::class, 'addressable');
    }

    /**
     * Get all of the tags for the post.
     *
     * return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function companies()
    {
        return $this->morphToMany(config('multi-tenancy.relationship.class'), config('multi-tenancy.relationship.polymorph-term'));
    }
}