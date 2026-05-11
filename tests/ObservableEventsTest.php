<?php

use Plank\LaravelPivotEvents\Tests\Models\User;

test('observable events include pivot events', function () {
    $user = User::find(1);
    $events = $user->getObservableEvents();

    expect($events)->toContain('pivotAttaching');
});
