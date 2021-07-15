<?php

namespace Jalno\Userpanel\Models;

use Carbon\Carbon;
use Jalno\Translator\Models\Translate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Jalno\API\{Contracts\ISearchableModel, Concerns\HasSearchAttributeTrait};

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $ip
 * @property string $type
 * @property array<string,mixed> $parameters
 * @property string $created_at
 * @property string|null $updated_at
 */

class Log extends Model implements ISearchableModel
{
    use HasSearchAttributeTrait;

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::saving(function (Log $log) {
            $log->ip = request()->ip();
        });
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'userpanel_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        "user_id",
        "ip",
        "type",
        "parameters",
    ];

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
    protected $with = ["user"];

    /**
     * The attributes that should be cast.
     *
     * @var array<string,string>
     */
    protected $casts = [
        "parameters" => "array",
    ];

	/**
	 * @var string[]
	 */
	protected array $searchAttributes = [
		"id",
		"user",
		"user_id",
		"ip",
		"type",
		"keywords",
	];

    public function user(): HasOne
    {
        return $this->hasOne(User::class, "id", "user_id");
    }

    public function keywords(): HasMany
    {
        return $this->hasMany(Log\Keyword::class, "log_id");
    }

    public function keyword(string $name): ?Log\Keyword
    {
        return $this->keywords->first(fn($keyword) => $keyword->name == $name);
    }

    public function addTag(string $type): int
    {
        return \DB::table("userpanel_logs_tags")->insertOrIgnore([
            "log_id" => $this->id,
            "tag" => $type,
        ]);
    }

    /**
     * @param string|int|array<string,mixed>|array<mixed> $value
     */
    public function addKeyword(string $name, $value): Log\Keyword
    {
        
        $keyword = new Log\Keyword();
        $keyword->log_id = $this->id;
        $keyword->name = $name;
        $keyword->value = $value;
        $keyword->saveOrFail();

        return $keyword;
    }
}
