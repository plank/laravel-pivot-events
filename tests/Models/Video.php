<?php

namespace Plank\LaravelPivotEvents\Tests\Models;

use Plank\LaravelPivotEvents\Traits\PivotEventTrait;

class Video extends BaseModel
{
    use PivotEventTrait;

    protected $table = 'videos';

    protected $fillable = ['name'];

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}
