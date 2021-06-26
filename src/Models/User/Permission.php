<?php

namespace Jalno\Userpanel\Models\User;

use Jalno\Userpanel\Models\User;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
	/**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'userpanel_users_permissions';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		"user_id",
		"name",
	];

	/**
	 * The attributes excluded from the model's JSON form.
	 *
	 * @var array
	 */
	protected $hidden = [];

	public function user()
	{
		return $this->hasOne(User::class, "id", "user_id");
	}
}
