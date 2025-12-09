<?php

namespace Plank\LaravelPivotEvents\Tests\Models;

use Plank\LaravelPivotEvents\Traits\PivotEventTrait;

class Tag extends BaseModel
{
    use PivotEventTrait;

    protected $table = 'videos';

    protected $fillable = ['name'];

    public function posts()
    {
        return $this->morphedByMany(Post::class, 'taggable');
    }

    public function videos()
    {
        return $this->morphedByMany(Video::class, 'taggable');
    }
}
