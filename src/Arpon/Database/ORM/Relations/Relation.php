<?php

namespace Arpon\Database\ORM\Relations;

use BadMethodCallException;
use Arpon\Database\ORM\Collection;
use Arpon\Database\ORM\Model;
use Arpon\Database\Query\Builder as QueryBuilder;

abstract class Relation
{
    /**
     * The query builder instance for the relationship.
     * @var QueryBuilder
     */
    protected QueryBuilder $query;

    /**
     * The parent model instance of the relationship.
     * @var Model
     */
    protected Model $parent;

    /**
     * The related model instance.
     * @var Model
     */
    protected Model $related;

    /**
     * The foreign key of the parent model.
     * (For BelongsTo, this is the FK on the child/parent model; for HasOne/Many, it's FK on related)
     * @var string
     */
    protected string $foreignKey;

    /**
     * The local key of the parent model.
     * (For BelongsTo, this is owner key on related; for HasOne/Many, it's local key on parent)
     * @var string
     */
    protected string $localKey;


    /**
     * Create a new relation instance.
     *
     * @param QueryBuilder $query
     * @param Model $parent
     * @return void
     */
    public function __construct(QueryBuilder $query, Model $parent)
    {
        $this->query = $query;
        $this->parent = $parent;
        $this->related = $query->getModelInstance(); // QueryBuilder should have getModelInstance()

        if (!$this->related) {
            // This might happen if the QueryBuilder wasn't properly initialized with a model.
            // Or, the relationship was defined with a class name that couldn't be resolved to a model.
            throw new \LogicException("Related model instance not found in QueryBuilder for relation.");
        }
    }

    /**
     * Set the base constraints on the relation query.
     * (Implemented by specific relation subclasses)
     */
    abstract public function addConstraints(): void;

    /**
     * Set the constraints for an eager load of the relation.
     * (Implemented by specific relation subclasses)
     * @param array<Model> $models Array of parent models.
     */
    abstract public function addEagerConstraints(array $models): void;

    /**
     * Initialize the relation on a set of models.
     * (Implemented by specific relation subclasses)
     * @param array<Model> $models Array of parent models.
     * @param string $relation Name of the relation.
     * @return array<Model> The array of models with the relation initialized.
     */
    abstract public function initRelation(array $models, string $relation): array;

    /**
     * Match the eagerly loaded results to their parents.
     * (Implemented by specific relation subclasses)
     * @param array<Model> $models Array of parent models.
     * @param Collection $results Collection of related models.
     * @param string $relation Name of the relation.
     * @return array<Model> The array of models with relations matched.
     */
    abstract public function match(array $models, Collection $results, string $relation): array;

    /**
     * Get the results of the relationship.
     * This is the primary method called when lazy-loading a relation (e.g., $user->posts).
     *
     * @return mixed \YourNamespace\Database\ORM\Model|\YourNamespace\Database\ORM\Collection|null
     */
    abstract public function getResults(): mixed;


    /**
     * Get the underlying query builder instance.
     */
    public function getQuery(): QueryBuilder
    {
        return $this->query;
    }

    /**
     * Get the parent model of the relation.
     */
    public function getParent(): Model
    {
        return $this->parent;
    }

    /**
     * Get the related model of the relation.
     */
    public function getRelated(): Model
    {
        return $this->related;
    }

    /**
     * Handle dynamic method calls to the relationship.
     * Forwards calls to the underlying QueryBuilder instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     * @throws BadMethodCallException
     */
    public function __call(string $method, array $parameters)
    {
        try {
            $result = $this->query->$method(...$parameters);
            // If the result is the QueryBuilder itself, return $this (the Relation) for chaining.
            if ($result instanceof QueryBuilder) {
                return $this;
            }
            // Otherwise, return the result from the QueryBuilder (e.g., count, exists, etc.)
            return $result;
        } catch (BadMethodCallException $e) {
            throw new BadMethodCallException(sprintf(
                'Call to undefined method %s::%s() on relation %s or %s::%s() on its QueryBuilder.',
                static::class, $method, get_class($this), QueryBuilder::class, $method
            ));
        }
    }

    /**
     * Create a new Collection instance.
     *
     * @param  array  $models
     * @return Collection
     */
    protected function newCollection(array $models = []): Collection
    {
        return $this->related->newCollection($models);
    }
}
