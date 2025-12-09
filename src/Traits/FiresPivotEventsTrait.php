<?php

namespace Plank\LaravelPivotEvents\Traits;

use Illuminate\Contracts\Broadcasting\Factory as BroadcastingFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

trait FiresPivotEventsTrait
{
    /**
     * Sync the intermediate tables with a list of IDs or collection of models.
     *
     * @param  mixed  $ids
     * @param  bool  $detaching
     * @return array
     */
    public function sync($ids, $detaching = true)
    {
        if ($this->parent->firePivotEvent('pivotSyncing', true, $this->getRelationName()) === false) {
            return false;
        }

        $parentResult = [];
        $this->parent->withoutEvents(function () use ($ids, $detaching, &$parentResult) {
            $parentResult = parent::sync($ids, $detaching);
        });

        $this->parent->firePivotEvent('pivotSynced', false, $this->getRelationName(), $parentResult);

        return $parentResult;
    }

    /**
     * Attach a model to the parent.
     *
     * @param  mixed  $id
     * @param  bool  $touch
     */
    public function attach($ids, array $attributes = [], $touch = true)
    {
        [$idsOnly, $idsAttributes] = $this->getIdsWithAttributes($ids, $attributes);

        $this->parent->firePivotEvent('pivotAttaching', true, $this->getRelationName(), $idsOnly, $idsAttributes);
        $parentResult = parent::attach($ids, $attributes, $touch);
        $this->parent->firePivotEvent('pivotAttached', false, $this->getRelationName(), $idsOnly, $idsAttributes);

        return $parentResult;
    }

    /**
     * Detach models from the relationship.
     *
     * @param  mixed  $ids
     * @param  bool  $touch
     * @return int
     */
    public function detach($ids = null, $touch = true)
    {
        if (is_null($ids)) {
            $ids = $this->query->pluck($this->query->qualifyColumn($this->relatedKey))->toArray();
        }

        [$idsOnly] = $this->getIdsWithAttributes($ids);

        $this->parent->firePivotEvent('pivotDetaching', true, $this->getRelationName(), $idsOnly);
        $parentResult = parent::detach($ids, $touch);
        $this->parent->firePivotEvent('pivotDetached', false, $this->getRelationName(), $idsOnly);

        return $parentResult;
    }

    /**
     * Update an existing pivot record on the table.
     *
     * @param  mixed  $id
     * @param  bool  $touch
     * @return int
     */
    public function updateExistingPivot($id, array $attributes, $touch = true)
    {
        [$idsOnly, $idsAttributes] = $this->getIdsWithAttributes($id, $attributes);

        $this->parent->firePivotEvent('pivotUpdating', true, $this->getRelationName(), $idsOnly, $idsAttributes);
        $parentResult = parent::updateExistingPivot($id, $attributes, $touch);
        $this->parent->firePivotEvent('pivotUpdated', false, $this->getRelationName(), $idsOnly, $idsAttributes);

        return $parentResult;
    }

    /**
     * Cleans the ids and ids with attributes
     * Returns an array with and array of ids and array of id => attributes.
     *
     * @param  mixed  $id
     * @param  array  $attributes
     * @return array
     */
    protected function getIdsWithAttributes($id, $attributes = [])
    {
        $ids = [];

        if ($id instanceof Model) {
            $ids[$id->getKey()] = $attributes;
        } elseif ($id instanceof Collection) {
            foreach ($id as $model) {
                $ids[$model->getKey()] = $attributes;
            }
        } elseif (is_array($id)) {
            foreach ($id as $key => $attributesArray) {
                if (is_array($attributesArray)) {
                    $ids[$key] = array_merge($attributes, $attributesArray);
                } else {
                    $ids[$attributesArray] = $attributes;
                }
            }
        } elseif (is_int($id) || is_string($id)) {
            $ids[$id] = $attributes;
        }

        $idsOnly = array_keys($ids);

        return [$idsOnly, $ids];
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
