<?php

namespace Arpon\Database\ORM\Relations;

use Arpon\Database\ORM\Collection;
use Arpon\Database\ORM\Concerns\InteractsWithPivotTable;
use Arpon\Database\ORM\Model;
use Arpon\Database\Query\Builder as QueryBuilder;

class BelongsToMany extends Relation
{
    use InteractsWithPivotTable; // Trait for attach, detach, sync

    /**
     * The intermediate table for the relationship.
     * @var string
     */
    protected string $table; // Pivot table name

    /**
     * The foreign key column name for the parent model on the pivot table.
     * @var string
     */
    protected string $parentKey; // e.g., 'user_id' in role_user table

    /**
     * The foreign key column name for the related model on the pivot table.
     * @var string
     */
    protected string $relatedKey; // e.g., 'role_id' in role_user table

    /**
     * The local key column name on the parent model.
     * (This is $this->localKey from the parent Relation class, corresponds to $parentLocalKey)
     * @var string
     */
    // protected string $localKey; // Inherited, e.g., 'id' on users table

    /**
     * The local key column name on the related model.
     * @var string
     */
    protected string $relatedLocalKey; // e.g., 'id' on roles table

    /**
     * The name of the relationship.
     * @var string
     */
    protected string $relationName;

    /**
     * The pivot columns to select.
     * @var string[]
     */
    protected array $pivotColumns = [];

    /**
     * Indicates if the pivot table has timestamps.
     * @var bool
     */
    protected bool $withTimestamps = false;

    /**
     * The "through" parent model for "has many through" like scenarios (not fully implemented here).
     * For BelongsToMany, this is the same as $this->parent.
     * @return void
     */
    // protected Model $throughParent;


    public function __construct(
        QueryBuilder $query,    // Query builder for the related model (e.g., Role)
        Model $parent,          // The parent model instance (e.g., User)
        string $table,          // Pivot table name (e.g., "role_user")
        string $parentKey,      // Foreign key on pivot for parent (e.g., "user_id")
        string $relatedKey,     // Foreign key on pivot for related (e.g., "role_id")
        string $parentLocalKey, // Local key on parent (e.g., users.id)
        string $relatedLocalKey,// Local key on related (e.g., roles.id)
        string $relationName    // Name of the relationship method (e.g., "roles")
    ) {
        $this->table = $table;
        $this->parentKey = $parentKey;
        $this->relatedKey = $relatedKey;
        $this->relatedLocalKey = $relatedLocalKey;
        $this->relationName = $relationName;

        // For Relation constructor:
        // $parent is the User model.
        // $query is for the Role model.
        // $this->localKey (from Relation) will be $parentLocalKey (e.g., users.id)
        // $this->foreignKey (from Relation) will be $parentKey (e.g., role_user.user_id) - this is a bit of a conceptual stretch
        // but it's how the base Relation might expect it for some generic operations.
        // More accurately, the pivot table links $parentLocalKey to $parentKey and $relatedLocalKey to $relatedKey.

        parent::__construct($query, $parent);
        $this->localKey = $parentLocalKey; // Explicitly set localKey for parent
        $this->foreignKey = $parentKey; // foreignKey on pivot table related to parent


        $this->performJoin();
        $this->addConstraints();
    }

    /**
     * Join the pivot table to the related model's table.
     */
    protected function performJoin(): void
    {
        // Select all columns from the related table by default.
        $this->query->select($this->related->getTable() . '.*');

        // Join pivot table: related_table.related_local_key = pivot_table.related_foreign_key
        $this->query->join(
            $this->table, // Pivot table (e.g., role_user)
            $this->related->getTable() . '.' . $this->relatedLocalKey, // e.g., roles.id
            '=',
            $this->table . '.' . $this->relatedKey // e.g., role_user.role_id
        );
    }

    /**
     * Set the select clause for the query to include pivot columns.
     */
    protected function selectPivotColumns(): void
    {
        $columnsToSelect = [$this->related->getTable() . '.*']; // Start with all from related

        // Add the foreign key from the pivot table that points to the parent model.
        // This is crucial for matching during eager loading.
        $columnsToSelect[] = $this->table . '.' . $this->parentKey . ' as pivot_' . $this->parentKey;


        foreach ($this->pivotColumns as $column) {
            $columnsToSelect[] = $this->table . '.' . $column . ' as pivot_' . $column;
        }

        if ($this->withTimestamps) {
            if (defined(get_class($this->parent).'::CREATED_AT')) {
                $columnsToSelect[] = $this->table . '.' . Model::CREATED_AT . ' as pivot_' . Model::CREATED_AT;
            }
            if (defined(get_class($this->parent).'::UPDATED_AT')) {
                $columnsToSelect[] = $this->table . '.' . Model::UPDATED_AT . ' as pivot_' . Model::UPDATED_AT;
            }
        }
        $this->query->select($columnsToSelect); // This will overwrite previous select
    }


    /**
     * Set the base constraints on the relation query.
     * Filters by the parent model's key on the pivot table.
     */
    public function addConstraints(): void
    {
        // Where pivot_table.parent_foreign_key = parent_model.parent_local_key
        $this->query->where($this->table . '.' . $this->parentKey, '=', $this->parent->getAttribute($this->localKey));
    }

    /**
     * Set the constraints for an eager load of the relation.
     * @param array<Model> $models Array of parent models.
     */
    public function addEagerConstraints(array $models): void
    {
        // Get all unique, non-null local key values from the parent models.
        $parentIds = $this->getEagerModelKeys($models, $this->localKey);

        if (empty($parentIds)) {
            $this->query->whereRaw('1 = 0');
            return;
        }
        $this->query->whereIn($this->table . '.' . $this->parentKey, $parentIds);
        $this->selectPivotColumns(); // Ensure pivot columns are selected for eager loading
    }

    /**
     * Initialize the relation on a set of models.
     * Sets the relation to an empty collection initially for each model.
     * @param array<Model> $models Array of parent models.
     * @param string $relation Name of the relation.
     * @return array<Model>
     */
    public function initRelation(array $models, string $relation): array
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->newCollection());
        }
        return $models;
    }

    /**
     * Match the eagerly loaded results to their parents.
     * @param array<Model> $models Array of parent models.
     * @param Collection $results Collection of related models (with pivot data).
     * @param string $relation Name of the relation.
     * @return array<Model>
     */
    public function match(array $models, Collection $results, string $relation): array
    {
        $dictionary = $this->buildDictionary($results);

        foreach ($models as $model) {
            // Key from the parent model (e.g., user.id)
            $key = $model->getAttribute($this->localKey);
            if (isset($dictionary[$key])) {
                $model->setRelation($relation, $this->newCollection($dictionary[$key]));
            }
            // If not found, relation remains as initialized (empty collection)
        }
        return $models;
    }

    /**
     * Build a dictionary of related models keyed by the parent's foreign key in the pivot table.
     * @param Collection $results
     * @return array
     */
    protected function buildDictionary(Collection $results): array
    {
        $dictionary = [];
        foreach ($results as $result) { // $result is a Model instance of the related type (e.g., Role)
            // The pivot data should have been loaded onto the $result model by selectPivotColumns
            // and QueryBuilder hydration. We need to access `pivot_{parentKey}`.
            $parentKeyValue = $result->getAttribute('pivot_' . $this->parentKey);

            // Create a new instance of the related model (e.g., Role)
            // and hydrate it with non-pivot attributes.
            $modelInstance = $this->related->newInstance([], true); // true for exists
            $modelAttributes = [];
            $pivotAttributes = [];

            foreach ($result->getAttributes() as $key => $value) {
                if (str_starts_with((string)$key, 'pivot_')) {
                    $pivotAttributes[substr((string)$key, 6)] = $value;
                } else {
                    // Only add attributes that actually belong to the related model's table
                    // This check is a bit naive; a better way is to check $this->related->isFillable() or similar
                    // or rely on the fact that QueryBuilder selected `related_table.*`
                    $modelAttributes[$key] = $value;
                }
            }
            $modelInstance->fill($modelAttributes)->syncOriginal();

            // Attach pivot data to the model instance
            if (!empty($pivotAttributes)) {
                // The Model class needs a way to store this, e.g., a dynamic 'pivot' property or method
                $modelInstance->setPivotData($this->relationName, (object)$pivotAttributes);
            }

            $dictionary[$parentKeyValue][] = $modelInstance;
        }
        return $dictionary;
    }


    /**
     * Get the results of the relationship.
     * For BelongsToMany, this is a collection of related models.
     * @return Collection
     */
    public function getResults(): Collection
    {
        // addConstraints and performJoin already set up the query.
        // QueryBuilder::get() should return a Collection of hydrated Models.
        $this->selectPivotColumns(); // Ensure pivot columns are selected for lazy loading too
        return $this->query->get();
    }

    /**
     * Helper to get key values from an array of models for eager loading.
     * @param array<Model> $models
     * @param string $keyName The name of the key to pluck.
     * @return array
     */
    protected function getEagerModelKeys(array $models, string $keyName): array
    {
        $keys = [];
        foreach ($models as $model) {
            if (!is_null($value = $model->getAttribute($keyName))) {
                $keys[] = $value;
            }
        }
        return array_values(array_unique(array_filter($keys)));
    }


    /**
     * Specify additional pivot columns to retrieve.
     * @param string|array $columns
     * @return static
     */
    public function withPivot(string|array $columns): static
    {
        $this->pivotColumns = array_unique(array_merge($this->pivotColumns, (array) $columns));
        // $this->selectPivotColumns(); // QueryBuilder will call this on get/eagerLoad
        return $this;
    }

    /**
     * Indicate that the pivot table has creation and update timestamps.
     * @return static
     */
    public function withTimestamps(): static
    {
        $this->withTimestamps = true;
        // $this->selectPivotColumns(); // QueryBuilder will call this
        return $this;
    }
}
