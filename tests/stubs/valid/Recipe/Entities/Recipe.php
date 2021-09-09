<?php

namespace App\Modules\Recipe\Entities;

use Dimsav\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Media\Support\Traits\MediaRelation;

class Recipe extends Model
{
    use Translatable, MediaRelation;

    protected $table = 'recipe__recipes';
    public $translatedAttributes = ['name', 'content'];
    protected $fillable = ['name', 'content'];
}
