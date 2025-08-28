<?php

namespace Arpon\Database\ORM\Concerns;

use Arpon\Database\ORM\Collection;
use Arpon\Database\ORM\Model;
use MyFramework\Support\Facades\DB;

// For direct DB access for pivot table

trait InteractsWithPivotTable
{
    // These properties MUST be defined in the BelongsToMany class that uses this trait:
    // protected string $table;          // Pivot table name
    // protected Model $parent;         // Parent model instance
    // protected Model $related;        // Related model instance (for timestamps, etc.)
    // protected string $parentKey;      // Foreign key in pivot table referencing parent model (e.g., user_id)
    // protected string $relatedKey;     // Foreign key in pivot table referencing related model (e.g., role_id)
    // protected string $localKey;       // Key on the parent model that $parentKey references (e.g., parent's 'id')
    // protected bool $withTimestamps;   // If pivot table has created_at/updated_at
    // protected string $relationName;   // Name of the relation for touching

    /**
     * Get a new query builder for the pivot table.
     */
    protected function newPivotQuery(): \Arpon\Database\Query\Builder
    {
        return DB::table($this->table); // Uses the DB Facade
    }

    /**
     * Create a new pivot model instance.
     * This is a simple array for now, but could be a dedicated Pivot model.
     *
     * @param array $attributes
     * @param bool $exists
     * @return array
     */
    protected function newPivot(array $attributes = [], bool $exists = false): array
    {
        // For now, just return the attributes. A Pivot model would be instantiated here.
        return $attributes;
    }

    /**
     * Attach a model instance to the parent.
     *
     * @param mixed $ids ID or array of IDs of related models, or Model instances.
     * @param array $attributes Additional attributes for the pivot table record.
     * @param bool $touch Whether to touch the parent's timestamps.
     * @return void
     */
    public function attach(mixed $ids, array $attributes = [], bool $touch = true): void
    {
        $records = [];
        $now = $this->parent->freshTimestampString(); // From HasTimestamps trait on parent

        foreach ($this->parseIds($ids) as $id) {
            $record = array_merge($attributes, [
                $this->parentKey => $this->parent->getAttribute($this->localKey), // Parent's ID
                $this->relatedKey => $id, // Related model's ID
            ]);

            if ($this->withTimestamps && defined(get_class($this->parent).'::CREATED_AT') && defined(get_class($this->parent).'::UPDATED_AT')) {
                $record[Model::CREATED_AT] = $now;
                $record[Model::UPDATED_AT] = $now;
            }
            $records[] = $record;
        }

        if (!empty($records)) {
            $this->newPivotQuery()->insert($records);
        }

        if ($touch) {
            $this->touchIfTouching();
        }
    }

    /**
     * Detach model instance(s) from the parent.
     *
     * @param mixed|null $ids ID or array of IDs to detach. Null detaches all.
     * @param bool $touch Whether to touch the parent's timestamps.
     * @return int Number of records deleted.
     */
    public function detach(mixed $ids = null, bool $touch = true): int
    {
        $query = $this->newPivotQuery()
            ->where($this->parentKey, '=', $this->parent->getAttribute($this->localKey));

        if (!is_null($ids)) {
            $query->whereIn($this->relatedKey, $this->parseIds($ids));
        }

        $results = $query->delete();

        if ($touch) {
            $this->touchIfTouching();
        }
        return $results;
    }

    /**
     * Sync the intermediate tables with a list of IDs or model instances.
     *
     * @param mixed $ids Array of IDs, Collection of models, or single ID/model.
     * @param bool $detaching Whether to detach models not present in the given IDs.
     * @return array Changes made: ['attached' => [], 'detached' => [], 'updated' => []]
     */
    public function sync(mixed $ids, bool $detaching = true): array
    {
        $changes = [
            'attached' => [], 'detached' => [], 'updated' => [],
        ];

        // Get current related IDs from the pivot table for this parent
        $current = $this->newPivotQuery()
            ->where($this->parentKey, '=', $this->parent->getAttribute($this->localKey))
            ->pluck($this->relatedKey) // Assumes QueryBuilder::pluck returns a simple array of values
            ->all(); // Assuming pluck returns a Collection, then get all items

        $parsedIds = $this->parseIds($ids); // IDs to be synced

        // IDs to detach: those currently in pivot but not in the new $parsedIds list
        $detach = array_diff($current, $parsedIds);
        if ($detaching && count($detach) > 0) {
            $this->detach($detach, false); // Detach without touching yet
            $changes['detached'] = $detach;
        }

        // IDs to attach: those in $parsedIds but not currently in pivot
        // We also need to handle attributes for existing records if they are passed with $ids
        // For simplicity, this sync only handles attaching new IDs and detaching old ones.
        // A full sync implementation would handle updating pivot attributes for existing relations.
        $attach = [];
        $update = []; // For records that exist and might have attributes updated

        $idsWithAttributes = $this->formatRecordsToSync($ids);

        foreach ($idsWithAttributes as $id => $attributes) {
            if (!in_array($id, $current)) {
                $attach[$id] = $attributes; // New ID to attach
            } elseif (count($attributes) > 0) {
                // ID exists, and attributes are provided, so it's an update
                // This part requires updating existing pivot records.
                // $changes['updated'][] = $this->updateExistingPivot($id, $attributes);
                // For now, we'll just mark it for potential update if implemented
                $update[] = $id; // Placeholder
            }
        }


        if (count($attach) > 0) {
            $this->attach(array_keys($attach), reset($attach) ?: [], false); // Attach new IDs without touching yet
            $changes['attached'] = array_keys($attach);
        }

        // Handle updates (if attributes were provided for existing relations)
        if (count($update) > 0) {
            foreach ($idsWithAttributes as $id => $attributes) {
                if (in_array($id, $current) && !empty($attributes)) {
                    $this->updateExistingPivot($id, $attributes);
                    $changes['updated'][] = $id;
                }
            }
        }


        if (count($changes['attached']) > 0 || count($changes['detached']) > 0 || count($changes['updated']) > 0) {
            $this->touchIfTouching();
        }
        return $changes;
    }

    /**
     * Update an existing pivot record with fresh attributes.
     */
    public function updateExistingPivot(mixed $id, array $attributes): int
    {
        if ($this->withTimestamps && defined(get_class($this->parent).'::UPDATED_AT')) {
            $attributes[Model::UPDATED_AT] = $this->parent->freshTimestampString();
        }

        return $this->newPivotQuery()
            ->where($this->parentKey, $this->parent->getAttribute($this->localKey))
            ->where($this->relatedKey, $id)
            ->update($attributes);
    }


    /**
     * Format the records to sync with attributes.
     * Input can be [1, 2, 3] or [1 => ['attr' => 'val'], 2, 3 => []]
     */
    protected function formatRecordsToSync(mixed $ids): array
    {
        $records = [];
        if ($ids instanceof Collection) {
            $ids = $ids->all(); // Assuming Collection::all() returns the array of models or IDs
        }
        if (!is_array($ids)) { // Single ID or Model
            $ids = [$ids];
        }

        foreach ($ids as $key => $value) {
            if (is_numeric($key)) { // Simple array of IDs: [1, 2, 3] or array of Models
                $id = $this->parseId($value);
                $records[$id] = [];
            } else { // Associative array with attributes: [1 => ['attr' => 'val']]
                $id = $this->parseId($key);
                $records[$id] = (array) $value;
            }
        }
        return $records;
    }


    /**
     * Parse a list of IDs into a flat array of primary keys.
     * Input can be an ID, array of IDs, Model instance, or Collection of Models.
     */
    protected function parseIds(mixed $value): array
    {
        if ($value instanceof Model) {
            return [$value->getKey()];
        }
        if ($value instanceof Collection) {
            // Assuming Collection::modelKeys() returns an array of primary keys
            return $value->modelKeys() ?: [];
        }
        if (!is_array($value)) {
            return [(string)$value]; // Cast to string in case of numeric ID
        }
        // If it's an array, it might be [1, 2, 3] or [1 => ['attr' => 'val'], new Model, ...]
        // We need to extract just the IDs for whereIn clauses or attach/detach keys.
        $ids = [];
        foreach ($value as $key => $val) {
            if (is_numeric($key) && !is_array($val)) { // Value is an ID or Model
                $ids[] = $this->parseId($val);
            } elseif(!is_numeric($key)) { // Key is an ID, value is attributes
                $ids[] = $this->parseId($key);
            }
            // If $val is an array here, it's attributes for an ID already processed or an error in input format
        }
        return array_unique(array_filter($ids));
    }

    /**
     * Parse a single ID from a Model instance or a scalar value.
     */
    protected function parseId(mixed $value): mixed
    {
        return $value instanceof Model ? $value->getKey() : $value;
    }


    /**
     * Touch the parent model's timestamps if the relationship is set to touch.
     * This is a placeholder for more advanced "touch" configuration on relationships.
     */
    protected function touchIfTouching(): void
    {
        // In Eloquent, you can define ->touch('relationName') on the parent model
        // or $touches = ['relationName'] on the related model.
        // For now, we assume if $this->parent has a method like `touches($this->relationName)`
        // or if the relation itself has a "touches" flag.
        // This is a simplified version.
        if (method_exists($this->parent, 'touchOwners') && $this->parent->touchOwners) { // Example check
            $this->parent->touch();
        } elseif (property_exists($this, 'touches') && $this->touches) { // If relation itself has a touch flag
            $this->parent->touch();
        }
    }
}
