<?php

namespace Jalno\Userpanel\Models;

use Carbon\Carbon;
use Jalno\Translator\Models\Translate;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
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
     * @var array
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
     * @var array
     */
    protected $hidden = [];

    /**
     * The relations to eager load on every query.
     *
     * @property array
     */
    protected $with = ["user"];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        "parameters" => "array",
    ];

    public function user()
    {
        return $this->hasOne(User::class, "id", "user_id");
    }

    public function keywords()
    {
        return $this->hasMany(Log\Keyword::class, "log_id");
    }

    public function keyword(string $name)
    {
        return $this->keywords->first(fn($keyword) => $keyword->name == $name);
    }

    public function addTag(string $type)
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