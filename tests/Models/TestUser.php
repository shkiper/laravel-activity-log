<?php

namespace Shkiper\ActivityLog\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class TestUser extends Model
{
    protected $table = 'users';
    protected $fillable = ['id', 'name', 'email'];
    public $timestamps = false;
}
