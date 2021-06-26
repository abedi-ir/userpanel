<?php

namespace Jalno\Userpanel\Models\UserType;

use Jalno\Userpanel\Models\UserType;
use Illuminate\Database\Eloquent\Model;

class Name extends Model
{
	/**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'userpanel_usertypes_names';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		"usertype_id",
		"lang",
		"name",
	];

	/**
	 * The attributes excluded from the model's JSON form.
	 *
	 * @var array
	 */
	protected $hidden = [];

	public function usertype()
	{
		return $this->hasOne(UserType::class, "id", "usertype_id");
	}
}
