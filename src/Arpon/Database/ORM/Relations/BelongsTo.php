<?php

namespace Arpon\Database\ORM\Relations;

use Arpon\Database\ORM\Collection;
use Arpon\Database\ORM\Model;
use Arpon\Database\Query\Builder as QueryBuilder;

// For type hint

class BelongsTo extends Relation
{
    /**
     * The foreign key of the child model.
     * @var string
     */
    protected string $foreignKey; // e.g., user_id on Post model

    /**
     * The associated key on the parent model.
     * @var string
     */
    protected string $ownerKey; // e.g., id on User model

    /**
     * The name of the relationship.
     * @var string
     */
    protected string $relationName;

    /**
     * Create a new belongs to relationship instance.
     *
     * @param QueryBuilder $query Query builder for the parent/owner model.
     * @param Model $child The child model instance.
     * @param string $foreignKey The foreign key on the child model.
     * @param string $ownerKey The key on the owner model that the foreign key references.
     * @param string $relationName The name of this relationship method on the child model.
     */
    public function __construct(QueryBuilder $query, Model $child, string $foreignKey, string $ownerKey, string $relationName)
    {
        $this->foreignKey = $foreignKey;
        $this->ownerKey = $ownerKey;
        $this->relationName = $relationName;

        // For BelongsTo, $parent in Relation constructor is the child model.
        // $query is for the related (owner/parent) model.
        parent::__construct($query, $child);

        // Add constraints after parent constructor sets up $this->related (owner model)
        // $this->addConstraints(); // Constraints are added when results are fetched or eager loaded
    }

    /**
     * Set the base constraints on the relation query.
     * This links the owner model's ownerKey to the child model's foreignKey.
     */
    public function addConstraints(): void
    {
        if (\is_null($this->parent->getAttribute($this->foreignKey))) {
            // If the foreign key on the child is null, it cannot belong to any parent.
            // So, make the query effectively return no results.
            $this->query->whereRaw('1 = 0'); // This is line 61 where the error was reported
            return;
        }
        // Query on the related (owner) model's table.
        $this->query->where($this->related->getTable() . '.' . $this->ownerKey, '=', $this->parent->getAttribute($this->foreignKey));
    }

    /**
     * Set the constraints for an eager load of the relation.
     * Gathers all foreign key values from the child models and queries the owner models.
     * @param array<Model> $models Array of child models.
     */
    public function addEagerConstraints(array $models): void
    {
        // Get all unique, non-null foreign key values from the child models.
        $ownerKeyValues = $this->getEagerModelKeys($models, $this->foreignKey);

        if (empty($ownerKeyValues)) {
            $this->query->whereRaw('1 = 0'); // No keys to query for
            return;
        }
        // Query on the related (owner) model's table.
        $this->query->whereIn($this->related->getTable() . '.' . $this->ownerKey, $ownerKeyValues);
    }

    /**
     * Initialize the relation on a set of models.
     * Sets the relation to null initially for each model.
     * @param array<Model> $models Array of child models.
     * @param string $relation Name of the relation.
     * @return array<Model>
     */
    public function initRelation(array $models, string $relation): array
    {
        foreach ($models as $model) {
            $model->setRelation($relation, null);
        }
        return $models;
    }

    /**
     * Match the eagerly loaded results (owner models) to their respective child models.
     * @param array<Model> $models Array of child models.
     * @param Collection $results Collection of owner models.
     * @param string $relation Name of the relation.
     * @return array<Model>
     */
    public function match(array $models, Collection $results, string $relation): array
    {
        $foreign = $this->foreignKey; // Key on child model (e.g., post.user_id)
        $owner = $this->ownerKey;     // Key on owner model (e.g., user.id)

        // Build a dictionary of owner models keyed by their ownerKey.
        $dictionary = [];
        foreach ($results as $result) {
            $dictionary[$result->getAttribute($owner)] = $result;
        }

        // Assign the matched owner model to each child model.
        foreach ($models as $model) {
            $key = $model->getAttribute($foreign); // Get the foreign key value from child
            if (isset($dictionary[$key])) {
                $model->setRelation($relation, $dictionary[$key]);
            }
        }
        return $models;
    }

    /**
     * Get the results of the relationship.
     * For BelongsTo, this is a single owner model or null.
     * @return Model|null
     */
    public function getResults(): ?Model
    {
        // Apply constraints when results are actually fetched
        $this->addConstraints();

        // If the foreign key on the child is null, no parent can be found.
        // addConstraints now handles this by making the query return no results.
        // if (\is_null($this->parent->getAttribute($this->foreignKey))) {
        //     return null;
        // }
        return $this->query->first(); // QueryBuilder::first() should return a Model or null
    }

    /**
     * Helper to get the foreign key values from an array of models.
     * @param array<Model> $models
     * @param string $keyName The name of the key to pluck.
     * @return array
     */
    protected function getEagerModelKeys(array $models, string $keyName): array
    {
        $keys = [];
        foreach ($models as $model) {
            if (!\is_null($value = $model->getAttribute($keyName))) {
                $keys[] = $value;
            }
        }
        return \array_values(\array_unique(\array_filter($keys)));
    }

    /**
     * Associate the model instance to the parent.
     * Sets the foreign key on the child model and the loaded relation.
     * @param Model $model The owner model to associate.
     * @return Model The child model.
     */
    public function associate(Model $model): Model
    {
        $this->parent->setAttribute($this->foreignKey, $model->getAttribute($this->ownerKey));
        $this->parent->setRelation($this->relationName, $model);
        return $this->parent;
    }

    /**
     * Dissociate the model instance from the parent.
     * Clears the foreign key on the child model and the loaded relation.
     * @return Model The child model.
     */
    public function dissociate(): Model
    {
        $this->parent->setAttribute($this->foreignKey, null);
        $this->parent->setRelation($this->relationName, null);
        return $this->parent;
    }
}
