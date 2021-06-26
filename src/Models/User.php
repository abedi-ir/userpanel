<?php

namespace Jalno\Userpanel\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
use Laravel\Passport\HasApiTokens;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use HasApiTokens, Authenticatable, Authorizable, HasFactory;

    const ACTIVE = 1;
    const DEACTIVE = 2;
    const SUSPEND = 3;

    /**
     * The User's default values for attributes.
     * 
     * @var array
     */
    public static array $withAttributes = [];

    public static function byUsername(string $username): ?User
    {
        $userName = User\UserName::where("username", $username)->first();

        return $userName ? User::find($userName->user_id) : null;
    }

    /**
     * The table associated with the model.
     *
     * @property string
     */
    protected $table = 'userpanel_users';

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->with = array_merge($this->with, self::$withAttributes);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @property array
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
     * @property array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The model's default values for attributes.
     *
     * @property array
     */
    protected $attributes = [
        'has_custom_permissions' => false,
    ];

    /**
     * The relations to eager load on every query.
     *
     * @property array
     */
    protected $with = ["usernames"];

    /**
     * Add a mutator to ensure hashed passwords
     */
    public function setPasswordAttribute($password)
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

    public function canAny(array $abilities): bool
    {
        foreach ($abilities as $ability) {
            if ($this->canAbility($ability)) {
                return true;
            }
        }

        return false;
    }

    public function childrenTypes()
    {
        return $this->usertype->childrenTypes();
    }

    public function parentTypes()
    {
        return $this->usertype->parentTypes();
    }

    public function usertype()
    {
        return $this->hasOne(UserType::class, "id", "usertype_id");
    }

    public function permissions()
    {
        return $this->hasMany(User\Permission::class, "user_id");
    }

    public function usernames()
    {
        return $this->hasMany(User\UserName::class, "user_id");
    }

    public function canAbility(string $ability, ?array $arguments = [])
    {
        return $this->has_custom_permissions ?
                $this->permissions->contains(fn($permission) => $permission->name == $ability) :
                $this->usertype->can($ability, $arguments);
    }

    public function abilities()
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
