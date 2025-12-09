<?php

namespace Plank\LaravelPivotEvents\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Plank\LaravelPivotEvents\Traits\FiresPivotEventsTrait;

class BelongsToManyCustom extends BelongsToMany
{
    use FiresPivotEventsTrait;
}
