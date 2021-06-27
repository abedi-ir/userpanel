<?php

namespace Jalno\Userpanel\Models;

use Jalno\Translator\Models\Translate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class UserType extends Model
{
    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::deleted(function (UserType $usertype) {
            Translate::where("table", "userpanel_usertypes.title")
                ->where("pk", $usertype->id)
                ->delete();
        });
    }

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
    protected $with = ['names'];

    /**
     * @param array<string,mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        Relation::morphMap([
            'userpanel_usertypes.title' => __NAMESPACE__ . "\UserType",
        ]);
        parent::__construct($attributes);
    }

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

    public function users()
    {
        return $this->hasMany(User::class, "usertype_id");
    }

    public function names()
    {
        return $this->morphMany(Translate::class, 'parentable', 'table', 'pk');
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

    public function addTitle(string $lang, string $title): Translate
    {
        $translate = Translate::where("table", $this->table . ".title")
                            ->where("pk", $this->id)
                            ->where("lang", $lang)
                            ->first();

        if (!$translate) {
            $translate = new Translate();
            $translate->table = $this->table . ".title";
            $translate->pk = $this->id;
            $translate->lang = $lang;
        }
        $translate->text = $title;
        $translate->saveOrFail();

        return $translate;
    }
}
