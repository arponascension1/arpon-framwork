<?php

namespace Arpon\Database\ORM\Relations;

use Arpon\Database\ORM\Collection;
use Arpon\Database\ORM\Model;

// For type hint

// For type hint in match

class HasOne extends HasOneOrMany
{
    /**
     * Get the results of the relationship.
     * For HasOne, this is a single related model or null.
     * @return Model|null
     */
    public function getResults(): ?Model
    {
        // If the local key on the parent (used for joining) is null,
        // it's unlikely to find a related model.
        if (is_null($this->parent->getAttribute($this->localKey))) {
            return null;
        }
        // The addConstraints method in HasOneOrMany already sets up the where clause.
        // QueryBuilder::first() should return a hydrated Model or null.
        return $this->query->first();
    }

    /**
     * Initialize the relation on a set of models.
     * Sets the relation to null initially for each model.
     * @param array<Model> $models Array of parent models.
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
     * Match the eagerly loaded results to their parents.
     * @param array<Model> $models Array of parent models.
     * @param Collection $results Collection of related models.
     * @param string $relation Name of the relation.
     * @return array<Model>
     */
    public function match(array $models, Collection $results, string $relation): array
    {
        return $this->matchOneOrMany($models, $results, $relation, 'one');
    }
}
