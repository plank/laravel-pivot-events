<?php

namespace Plank\LaravelPivotEvents\Traits;

trait PivotEventTrait
{
    use ExtendRelationsTrait;

    /**
     * Get the observable event names.
     *
     * @return array
     */
    public function getObservableEvents()
    {
        return array_merge(
            parent::getObservableEvents(),
            [
                'pivotSyncing', 'pivotSynced',
                'pivotAttaching', 'pivotAttached',
                'pivotDetaching', 'pivotDetached',
                'pivotUpdating', 'pivotUpdated',
            ],
            $this->observables
        );
    }

    public static function pivotSyncing($callback, $priority = 0)
    {
        static::registerModelEvent('pivotSyncing', $callback, $priority);
    }

    public static function pivotSynced($callback, $priority = 0)
    {
        static::registerModelEvent('pivotSynced', $callback, $priority);
    }

    public static function pivotAttaching($callback, $priority = 0)
    {
        static::registerModelEvent('pivotAttaching', $callback, $priority);
    }

    public static function pivotAttached($callback, $priority = 0)
    {
        static::registerModelEvent('pivotAttached', $callback, $priority);
    }

    public static function pivotDetaching($callback, $priority = 0)
    {
        static::registerModelEvent('pivotDetaching', $callback, $priority);
    }

    public static function pivotDetached($callback, $priority = 0)
    {
        static::registerModelEvent('pivotDetached', $callback, $priority);
    }

    public static function pivotUpdating($callback, $priority = 0)
    {
        static::registerModelEvent('pivotUpdating', $callback, $priority);
    }

    public static function pivotUpdated($callback, $priority = 0)
    {
        static::registerModelEvent('pivotUpdated', $callback, $priority);
    }

    /**
     * Fire the given event for the model.
     *
     * @param  string  $event
     * @param  bool  $halt
     * @return mixed
     */
    public function firePivotEvent(
        $event,
        $halt = true,
        $relationName = null,
        $ids = [],
        $idsAttributes = []
    ) {
        if (! isset(static::$dispatcher)) {
            return true;
        }

        $method = $halt
            ? 'until'
            : 'dispatch';

        $result = $this->filterModelEventResults(
            $this->fireCustomModelEvent($event, $method)
        );

        if ($result === false) {
            return false;
        }

        $payload = [
            'model' => $this,
            'relation' => $relationName,
            'pivotIds' => $ids,
            'pivotIdsAttributes' => $idsAttributes,
            0 => $this,
        ];
        $result = $result
            ?: static::$dispatcher
                ->{$method}("eloquent.{$event}: ".static::class, $payload);
        $this->broadcastPivotEvent($event, $payload);

        return $result;
    }

    protected function broadcastPivotEvent(string $event, array $payload): void
    {
        $events = [
            'pivotAttached',
            'pivotDetached',
            'pivotSynced',
            'pivotUpdated',
        ];

        if (! in_array($event, $events)) {
            return;
        }

        $className = explode('\\', get_class($this));
        $name = method_exists($this, 'broadcastAs')
                ? $this->broadcastAs()
                : array_pop($className).ucwords($event);
        $channels = method_exists($this, 'broadcastOn')
            ? Arr::wrap($this->broadcastOn($event))
            : [];

        if (empty($channels)) {
            return;
        }

        $connections = method_exists($this, 'broadcastConnections')
            ? $this->broadcastConnections()
            : [null];
        $manager = app(BroadcastingFactory::class);

        foreach ($connections as $connection) {
            $manager->connection($connection)
                ->broadcast($channels, $name, $payload);
        }
    }
}
