<?php

namespace Arpon\Database\ORM\Relations;

use Arpon\Database\ORM\Collection;
use Arpon\Database\ORM\Model;

// For return type and new Collection

// For type hint

class HasMany extends HasOneOrMany
{
    /**
     * Get the results of the relationship.
     * For HasMany, this is a collection of related models.
     * @return Collection
     */
    public function getResults(): Collection
    {
        // If the local key on the parent (used for joining) is null,
        // it's unlikely to find related models.
        if (is_null($this->parent->getAttribute($this->localKey))) {
            return $this->newCollection(); // Return an empty collection
        }
        // The addConstraints method in HasOneOrMany already sets up the where clause.
        // QueryBuilder::get() should return a Collection of hydrated Models.
        return $this->query->get();
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
     * @param Collection $results Collection of related models.
     * @param string $relation Name of the relation.
     * @return array<Model>
     */
    public function match(array $models, Collection $results, string $relation): array
    {
        return $this->matchOneOrMany($models, $results, $relation, 'many');
    }
}
