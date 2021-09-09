<?php return '<?php

namespace App\\Modules\\Blog\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected static function newFactory()
    {
        return \\Modules\\Blog\\Database\\factories\\PostFactory::new();
    }
}
';
