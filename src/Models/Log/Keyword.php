<?php

namespace Jalno\Userpanel\Models\Log;

use Jalno\Userpanel\Models\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $log_id
 * @property string $name
 * @property string|int|array<string,mixed>|array<mixed> $value
 */
class Keyword extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'userpanel_logs_keywords';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        "log_id",
        "name",
        "value",
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    protected $hidden = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string,string>
     */
    protected $casts = [];

    public function user(): HasOne
    {
        return $this->hasOne(Log::class, "id", "log_id");
    }


	/**
	 * @return string|int|array<string,mixed>|array<mixed>
	 */
	public function getValueAttribute(string $value)
	{
		return (
			(preg_match("/^\{/", $value) and preg_match("/\}$/", $value)) or
			(preg_match("/^\[/", $value) and preg_match("/\]$/", $value))
		) ? json_decode($value, true) : $value;
    }

    /**
     * @param string|int|array<string,mixed>|array<mixed>|object $value
     */
    public function setValueAttribute($value): void
    {
        $this->attributes['value'] = ((is_array($value) or is_object($value)) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value);
    }
}
