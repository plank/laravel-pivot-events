<?php

namespace Plank\LaravelPivotEvents\Tests;

use Plank\LaravelPivotEvents\Tests\Models\User;

class ObservableEventsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_events()
    {
        $user = User::find(1);
        $events = $user->getObservableEvents();

        $this->assertTrue(in_array('pivotAttaching', $events));
    }
}
