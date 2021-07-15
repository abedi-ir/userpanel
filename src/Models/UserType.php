<?php

namespace Jalno\Userpanel\Models;

use Jalno\Translator\Models\Translate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Jalno\API\{Contracts\ISearchableModel, Concerns\HasSearchAttributeTrait};

/**
 * @property int $id
 * @property Collection|null $names
 * @property Collection|null $permissions
 */

class UserType extends Model implements ISearchableModel
{
    use HasSearchAttributeTrait;

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
     * @var string[]
     */
    protected $fillable = [];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    protected $hidden = [];

    /**
     * The relations to eager load on every query.
     *
     * @var string[]
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

    public function priorities(): BelongsToMany
    {
        return $this->belongsToMany(static::class, "userpanel_usertypes_priorities", "parent_id", "child_id");
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(static::class, "userpanel_usertypes_priorities", "child_id", "parent_id");
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(UserType\Permission::class, "usertype_id");
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, "usertype_id");
    }

    public function names(): MorphMany
    {
        return $this->morphMany(Translate::class, 'parentable', 'table', 'pk');
    }

    public function name(string $lang): ?Translate
    {
        return $this->names->first(fn($name) => $name->lang == $lang);
    }

    public function can(string $ability, ?array $arguments = []): bool
    {
        return $this->permissions->contains(fn($permission) => $permission->name == $ability);
    }

    /**
     * @return int[]
     */
    public function childrenTypes(): array
    {
        return $this->priorities->pluck('id')->all();
    }

    /**
     * @return int[]
     */
    public function parentTypes(): array
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
