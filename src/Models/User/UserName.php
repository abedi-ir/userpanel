<?php

namespace Jalno\Userpanel\Models\User;

use Jalno\Userpanel\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $user_id
 * @property string $username
 * @property User|null $user
 */

class UserName extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'userpanel_users_usernames';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'user_id',
        'username',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    protected $hidden = [];

    public function user(): HasOne
    {
        return $this->hasOne(User::class, "id", "user_id");
    }
}
