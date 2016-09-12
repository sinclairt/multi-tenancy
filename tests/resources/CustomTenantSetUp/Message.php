<?php

namespace Testing\CustomTenant;

use Sinclair\MultiTenancy\Traits\MorphToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Message
 */
class Message extends Model
{
    use SoftDeletes, MorphToTenant;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [ 'text' ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The dates that are returned as Carbon objects
     *
     * @var array
     */
    protected $dates = [ 'created_at', 'updated_at', 'deleted_at' ];

    /*
     * Relationships
     */

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function users()
    {
        return $this->morphedByMany(User::class, 'messageable');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id')
                    ->withTrashed();
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