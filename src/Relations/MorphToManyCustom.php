<?php

namespace Plank\LaravelPivotEvents\Relations;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Plank\LaravelPivotEvents\Traits\FiresPivotEventsTrait;

class MorphToManyCustom extends MorphToMany
{
    use FiresPivotEventsTrait;
}
