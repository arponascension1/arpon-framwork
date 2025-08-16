<?php

namespace Arpon\Database\ORM\Relations;

use Arpon\Database\ORM\Collection;
use Arpon\Database\ORM\Model;
use Arpon\Database\Query\Builder as QueryBuilder;
use function Arpon\Database\ORM\Relations\last;
use function MyFramework\Database\ORM\Relations\last;

abstract class HasOneOrMany extends Relation
{
    /**
     * The foreign key of the related model.
     * @var string
     */
    protected string $foreignKey; // e.g., user_id on Post or Profile model

    /**
     * The local key of the parent model.
     * @var string
     */
    protected string $localKey; // e.g., id on User model

    /**
     * Create a new has one or has many relationship instance.
     *
     * @param QueryBuilder $query Query builder for the related model.
     * @param Model $parent The parent model instance.
     * @param string $foreignKey The foreign key on the related model's table.
     * @param string $localKey The local key on the parent model's table.
     */
    public function __construct(QueryBuilder $query, Model $parent, string $foreignKey, string $localKey)
    {
        $this->foreignKey = $foreignKey; // e.g., posts.user_id
        $this->localKey = $localKey;     // e.g., users.id

        // For HasOne/Many, $parent is the parent model.
        // $query is for the related model.
        parent::__construct($query, $parent);

        // Add constraints after parent constructor sets up $this->related
        $this->addConstraints();
    }

    /**
     * Set the base constraints on the relation query.
     * Links the related model's foreignKey to the parent model's localKey.
     */
    public function addConstraints(): void
    {
        // Query on the related model's table.
        // The foreignKey here includes the table name from the constructor (e.g., posts.user_id)
        // So, we need to extract the column part for the where clause.
        $foreignKeyColumn = last(explode('.', $this->foreignKey));

        $this->query->where($foreignKeyColumn, '=', $this->parent->getAttribute($this->localKey));
    }

    /**
     * Set the constraints for an eager load of the relation.
     * Gathers all local key values from parent models and queries related models.
     * @param array<Model> $models Array of parent models.
     */
    public function addEagerConstraints(array $models): void
    {
        // Get all unique, non-null local key values from the parent models.
        $localKeyValues = $this->getEagerModelKeys($models, $this->localKey);

        if (empty($localKeyValues)) {
            $this->query->whereRaw('1 = 0'); // No keys to query for
            return;
        }
        // Query on the related model's table.
        $foreignKeyColumn = last(explode('.', $this->foreignKey));
        $this->query->whereIn($foreignKeyColumn, $localKeyValues);
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param array $models Array of parent models.
     * @param Collection $results Collection of related models.
     * @param string $relation Name of the relation.
     * @param string $type 'one' or 'many'.
     * @return array
     */
    protected function matchOneOrMany(array $models, Collection $results, string $relation, string $type): array
    {
        // The foreign key on the related model (e.g., post.user_id)
        $foreignKeyColumn = last(explode('.', $this->foreignKey));

        // Build a dictionary of related models, keyed by the foreign key.
        $dictionary = $this->buildDictionary($results, $foreignKeyColumn);

        // Assign the matched related model(s) to each parent model.
        foreach ($models as $model) {
            // Get the local key value from the parent model (e.g., user.id)
            $key = $model->getAttribute($this->localKey);

            if (isset($dictionary[$key])) {
                $value = ($type === 'one')
                    ? (reset($dictionary[$key]) ?: null) // Get the first related model for HasOne
                    : $this->newCollection($dictionary[$key]); // Get a collection for HasMany
                $model->setRelation($relation, $value);
            }
            // If no match, the relation remains as initialized (null for HasOne, empty Collection for HasMany)
        }
        return $models;
    }

    /**
     * Build a dictionary of models keyed by a given key.
     * @param Collection $results
     * @param string $keyName The key to use for dictionary keys.
     * @return array
     */
    protected function buildDictionary(Collection $results, string $keyName): array
    {
        $dictionary = [];
        foreach ($results as $result) {
            // Group related models by the foreign key value.
            $dictionary[$result->getAttribute($keyName)][] = $result;
        }
        return $dictionary;
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
     * Attach a model instance to the parent model.
     * Sets the foreign key on the related model and saves it.
     * @param Model $model The related model instance to save.
     * @return Model|false The saved model or false on failure.
     */
    public function save(Model $model): Model|false
    {
        // Set the foreign key on the related model to the parent's local key value.
        $foreignKeyColumn = last(explode('.', $this->foreignKey));
        $model->setAttribute($foreignKeyColumn, $this->parent->getAttribute($this->localKey));
        return $model->save() ? $model : false;
    }

    /**
     * Create a new instance of the related model and associate it.
     * @param array $attributes Attributes for the new related model.
     * @return Model|false The created model or false on failure.
     */
    public function create(array $attributes = []): Model|false
    {
        // Create a new instance of the related model.
        $instance = $this->related->newInstance($attributes);
        // Save will set the foreign key and persist.
        return $this->save($instance);
    }
}

// Helper function if not globally available
if (!function_exists('last')) {
    function last(array $array) {
        return end($array);
    }
}
