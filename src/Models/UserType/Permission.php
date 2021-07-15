<?php

namespace Jalno\Userpanel\Models\UserType;

use Jalno\Userpanel\Models\UserType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Jalno\API\{Contracts\ISearchableModel, Concerns\HasSearchAttributeTrait};

/**
 * @property int $id
 * @property int $usertype_id
 * @property string $name
 * @property UserType|null $usertype
 */

class Permission extends Model implements ISearchableModel
{
	use HasSearchAttributeTrait;

	/**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'userpanel_usertypes_permissions';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var string[]
	 */
	protected $fillable = [
		"usertype_id",
		"name",
	];

	/**
	 * The attributes excluded from the model's JSON form.
	 *
	 * @var string[]
	 */
	protected $hidden = [];

	/**
	 * @var string[]
	 */
	protected array $searchAttributes = [
		"id",
		"usertype",
		"usertype_id",
		"name",
	];

	public function usertype(): HasOne
	{
		return $this->hasOne(UserType::class, "id", "usertype_id");
	}
}
