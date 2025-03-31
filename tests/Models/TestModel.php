<?php

namespace Shkiper\ActivityLog\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    protected $table = 'test_models';
    protected $fillable = ['id', 'name', 'text'];
    public $timestamps = false;
}
