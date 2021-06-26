<?php

namespace Jalno\Userpanel\Models;

use Illuminate\Database\Eloquent\Model;

class UserType extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'userpanel_usertypes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The relations to eager load on every query.
     *
     * @property array
     */
    protected $with = [];

    public function priorities()
    {
        return $this->belongsToMany(static::class, "userpanel_usertypes_priorities", "parent_id", "child_id");
    }

    public function parents()
    {
        return $this->belongsToMany(static::class, "userpanel_usertypes_priorities", "child_id", "parent_id");
    }

    public function permissions()
    {
        return $this->hasMany(UserType\Permission::class, "usertype_id");
    }

    public function names()
    {
        return $this->hasMany(UserType\Name::class, "usertype_id");
    }

    public function name(string $lang)
    {
        return $this->names->first(fn($name) => $name->lang == $lang);
    }

    public function can(string $ability, ?array $arguments = [])
    {
        return $this->permissions->contains(fn($permission) => $permission->name == $ability);
    }

    public function childrenTypes()
    {
        return $this->priorities->pluck('id')->all();
    }

    public function parentTypes()
    {
        return $this->parents->pluck('id')->all();
    }
}
