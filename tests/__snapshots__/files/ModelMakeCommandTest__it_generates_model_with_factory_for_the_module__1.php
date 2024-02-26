<?php

namespace Modules\Author\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Author\Exceptions\AuthorEmailFactory;

class AuthorEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        // TODO: add fields here
    ];

    protected $hidden = [
        // TODO: add fields here
    ];

    protected $casts = [
        // TODO: add fields here
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return AuthorEmailFactory::new();
    }
}
