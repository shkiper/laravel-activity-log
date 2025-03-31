<?php

namespace Shkiper\ActivityLog\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Shkiper\ActivityLog\Traits\LogsActivity;

class User extends Authenticatable
{
    protected $fillable = [
        'name',
        'email',
    ];

    public function articles()
    {
        return $this->hasMany(Article::class);
    }
}

class Article extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'content',
        'status',
        'published_at',
        'user_id',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    protected static $logAttributes = [
        'title',
        'content',
        'status',
        'published_at'
    ];

    protected static $logName = 'articles';
    protected static $logOnlyDirty = true;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
