<?php

namespace Jalno\Userpanel\Models;

use Laravel\Passport\HasApiTokens;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

/**
 * @property int $id
 * @property string $password
 * @property int $usertype_id
 * @property bool $has_custom_permissions
 * @property int $status
 * @property string $remember_token
 * @property Collection|null $permissions
 * @property Collection|null $usernames
 * @property UserType $usertype
 */

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use HasApiTokens, Authenticatable, Authorizable, HasFactory;

    const ACTIVE = 1;
    const DEACTIVE = 2;
    const SUSPEND = 3;

    /**
     * @return User|null
     */
    public static function byUsername(string $username)
    {
        $userName = User\UserName::where("username", $username)->first();

        return $userName ? User::find($userName->user_id) : null;
    }

    public static function addEagerLoadRelation(string $name): void
    {
        self::$withRelations[] = $name;
    }

    /**
     * @param string[] $with
     */
    public static function setEagerLoadRelations(array $with): void
    {
        self::$withRelations = $with;
    }

    /**
     * @return string[]
     */
    public static function getEagerLoadRelations(): array
    {
        return self::$withRelations;
    }

    /**
     * The relations to eager load on every query.
     *
     * @var string[]
     */
    protected static array $withRelations = [];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'userpanel_users';

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array<string,mixed>  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->with = array_merge($this->with, self::$withRelations);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'password',
        'usertype_id',
        'has_custom_permissions',
        'status',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string,mixed>
     */
    protected $attributes = [
        'has_custom_permissions' => false,
    ];

    /**
     * The relations to eager load on every query.
     *
     * @var string[]
     */
    protected $with = ["usernames"];

    /**
     * Add a mutator to ensure hashed passwords
     */
    public function setPasswordAttribute(string $password): void
    {
        $this->attributes['password'] = password_hash($password, PASSWORD_DEFAULT);
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    public function findAndValidateForPassport(string $username, string $password): ?User
    {
        if (empty($username) or empty($password)) {
            return null;
        }

        $user = static::byUsername($username);
        if (!$user or !$user->verifyPassword($password)) {
            return null;
        }

        return $user;
    }

    /**
     * @param string[] $abilities
     */
    public function canAny(array $abilities): bool
    {
        foreach ($abilities as $ability) {
            if ($this->canAbility($ability)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return int[]
     */
    public function childrenTypes(): array
    {
        return $this->usertype->childrenTypes();
    }

    /**
     * @return int[]
     */
    public function parentTypes(): array
    {
        return $this->usertype->parentTypes();
    }

    public function usertype(): HasOne
    {
        return $this->hasOne(UserType::class, "id", "usertype_id");
    }

    /**
     * @return HasMany<User\Permission>
     */
    public function permissions()
    {
        return $this->hasMany(User\Permission::class, "user_id");
    }

    /**
     * @return hasMany<User\UserName>
     */
    public function usernames()
    {
        return $this->hasMany(User\UserName::class, "user_id");
    }

    /**
     * @param array<mixed> $arguments
     */
    public function canAbility(string $ability, ?array $arguments = []): bool
    {
        return $this->has_custom_permissions ?
                $this->permissions->contains(fn($permission) => $permission->name == $ability) :
                $this->usertype->can($ability, $arguments);
    }

    public function abilities(): ?Collection
    {
        return $this->has_custom_permissions ?
                $this->permissions :
                $this->usertype->permissions;
    }

    public function isAdmin(): bool
    {
        $parents = $this->parentTypes();
        if (empty($parents)) {
            return true;
        }
        return count($parents) == 1 and $parents[0] == $this->usertype_id;
    }
}
