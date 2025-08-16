<?php

namespace Arpon\Database\ORM\Concerns;

use Arpon\Database\ORM\Model;
use Arpon\Database\ORM\Relations\BelongsTo;
use Arpon\Database\ORM\Relations\BelongsToMany;
use Arpon\Database\ORM\Relations\HasMany;
use Arpon\Database\ORM\Relations\HasOne;
use Arpon\Database\ORM\Relations\Relation;

// Base Relation class
// For undefined relationship methods

trait QueriesRelationships
{
    /**
     * The loaded relationships for the model.
     * @var array<string, mixed>
     */
    protected array $relations = [];

    /**
     * The pivot attributes for the model.
     * Used for BelongsToMany relationships.
     * @var array<string, object|array> Keyed by pivot table name or relation name.
     */
    protected array $pivotData = [];


    /**
     * Define a one-to-one relationship.
     * Example: A User has one Profile.
     *
     * @param  string  $related  The fully qualified class name of the related model.
     * @param  string|null  $foreignKey  The foreign key on the related model's table.
     * @param  string|null  $localKey  The local key on the parent model's table.
     * @return \Arpon\Database\ORM\Relations\HasOne
     */
    protected function hasOne(string $related, ?string $foreignKey = null, ?string $localKey = null): HasOne
    {
        /** @var Model $instance */
        $instance = new $related; // Create an instance of the related model
        // Guess foreign key: parent_model_name_id (e.g., user_id)
        $foreignKey = $foreignKey ?: $this->getForeignKeyName();
        // Guess local key: primary key of the parent model (e.g., id)
        $localKey = $localKey ?: $this->getKeyName();

        return new HasOne($instance->newQuery(), $this, $instance->getTable().'.'.$foreignKey, $localKey);
    }

    /**
     * Define a one-to-many relationship.
     * Example: A User has many Posts.
     *
     * @param  string  $related
     * @param  string|null  $foreignKey
     * @param  string|null  $localKey
     * @return HasMany
     */
    protected function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null): HasMany
    {
        /** @var Model $instance */
        $instance = new $related;
        $foreignKey = $foreignKey ?: $this->getForeignKeyName();
        $localKey = $localKey ?: $this->getKeyName();

        return new HasMany($instance->newQuery(), $this, $instance->getTable().'.'.$foreignKey, $localKey);
    }

    /**
     * Define an inverse one-to-one or one-to-many relationship.
     * Example: A Post belongs to a User. A Profile belongs to a User.
     *
     * @param  string  $related
     * @param  string|null  $foreignKey  The foreign key on the current model's table.
     * @param  string|null  $ownerKey    The key on the related (owner/parent) model's table.
     * @param  string|null  $relation    The name of the relationship method (guessed if null).
     * @return BelongsTo
     * @throws \LogicException If the relationship name cannot be automatically determined.
     */
    protected function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null, ?string $relation = null): BelongsTo // Line 88 was here
    {
        /** @var Model $instance */
        $instance = new $related; // Instance of the parent/owner model (e.g., User)

        if (\is_null($relation)) {
            // Guess relation name from the calling method (e.g., if method is "user()", relation is "user")
            // The backtrace should point to the method on the Model that called belongsTo()
            // e.g., App\Models\Post::user() -> YourNamespace\Database\ORM\Model::belongsTo() -> this trait's belongsTo()
            // So we need to go up enough frames. Frame 0 is debug_backtrace itself. Frame 1 is this method. Frame 2 is the caller (Model::belongsTo or the actual relation method).
            $backtrace = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3); // Limit to 3 frames

            if (isset($backtrace[2]) && isset($backtrace[2]['function'])) { // Line 89 was here
                $relation = $backtrace[2]['function'];
            } else {
                // Fallback or error if relation name cannot be inferred
                // This can happen if the call stack is different than expected (e.g. deeper inheritance or magic methods)
                throw new \LogicException(
                    "Could not automatically determine relationship name for BelongsTo on " . static::class .
                    ". Please provide it as the 4th argument to belongsTo(). Backtrace: " . \json_encode($backtrace)
                );
            }
        }

        // Guess foreign key: related_model_name_id (e.g., user_id on the current Post model)
        $foreignKey = $foreignKey ?: $instance->getForeignKeyName();
        // Guess owner key: primary key of the related model (e.g., id on User model)
        $ownerKey = $ownerKey ?: $instance->getKeyName();

        return new BelongsTo($instance->newQuery(), $this, $foreignKey, $ownerKey, $relation); // Line 97 was here
    }

    /**
     * Define a many-to-many relationship.
     * Example: A User has and belongs to many Roles.
     *
     * @param  string  $related  The related model class.
     * @param  string|null  $table  The pivot table name.
     * @param  string|null  $parentForeignKey  Foreign key on pivot table for this (parent) model.
     * @param  string|null  $relatedForeignKey  Foreign key on pivot table for the related model.
     * @param  string|null  $parentLocalKey  Local key on this (parent) model.
     * @param  string|null  $relatedLocalKey  Local key on the related model.
     * @return BelongsToMany
     * @throws \LogicException If the relationship name cannot be automatically determined.
     */
    protected function belongsToMany(
        string $related,
        ?string $table = null,
        ?string $parentForeignKey = null,
        ?string $relatedForeignKey = null,
        ?string $parentLocalKey = null,
        ?string $relatedLocalKey = null
    ): BelongsToMany {
        /** @var Model $instance */
        $instance = new $related; // Instance of the related model (e.g., Role)

        $backtrace = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        if (isset($backtrace[2]['function'])) {
            $relationName = $backtrace[2]['function'];
        } else {
            throw new \LogicException(
                "Could not automatically determine relationship name for BelongsToMany on " . static::class .
                ". Please provide it as an argument if conventions are not met. Backtrace: " . \json_encode($backtrace)
            );
        }

        // Guess pivot table name: alphabetical order of singular model names (e.g., role_user)
        $table = $table ?: $this->joiningTable($related);
        // Guess foreign key for parent model: parent_model_name_id (e.g., user_id)
        $parentForeignKey = $parentForeignKey ?: $this->getForeignKeyName();
        // Guess foreign key for related model: related_model_name_id (e.g., role_id)
        $relatedForeignKey = $relatedForeignKey ?: $instance->getForeignKeyName();
        // Parent model's local key (usually primary key)
        $parentLocalKey = $parentLocalKey ?: $this->getKeyName();
        // Related model's local key (usually primary key)
        $relatedLocalKey = $relatedLocalKey ?: $instance->getKeyName();

        return new BelongsToMany(
            $instance->newQuery(), $this, $table,
            $parentForeignKey, $relatedForeignKey,
            $parentLocalKey, $relatedLocalKey, $relationName
        );
    }

    /**
     * Get the joining table name for a many-to-many relation.
     * Sorts singular model names alphabetically and joins with an underscore.
     */
    public function joiningTable(string $relatedModelClass): string
    {
        // Get singular, snake_case names of the models
        $thisModelName = \strtolower(\preg_replace('/(?<!^)[A-Z]/', '_$0', \basename(\str_replace('\\', '/', static::class))));
        $relatedModelName = \strtolower(\preg_replace('/(?<!^)[A-Z]/', '_$0', \basename(\str_replace('\\', '/', $relatedModelClass))));

        $models = [$thisModelName, $relatedModelName];
        \sort($models); // Sort alphabetically
        return \implode('_', $models); // e.g., role_user
    }


    /**
     * Get the foreign key name for the model.
     * Convention: snake_case_model_name_primary_key_name (e.g., user_id).
     */
    public function getForeignKeyName(): string
    {
        return \strtolower(\preg_replace('/(?<!^)[A-Z]/', '_$0', \basename(\str_replace('\\', '/', static::class))))
            . '_' . $this->getKeyName();
    }

    /**
     * Get a relationship value from a method.
     * This is called when accessing a relationship as a property (lazy loading).
     */
    public function getRelationValue(string $key): mixed
    {
        // If the key is already a loaded relationship, return it.
        if ($this->relationLoaded($key)) {
            return $this->relations[$key];
        }

        // If the key corresponds to a relationship method on the model, we will
        // call that method and return the result of that call.
        if (\method_exists($this, $key)) {
            $relationInstance = $this->$key(); // Call the relationship method (e.g., public function posts())

            if ($relationInstance instanceof Relation) {
                // For BelongsTo and HasOne, get the single related model.
                // For HasMany and BelongsToMany, get the collection of models.
                // The Relation's getResults() or specific get()/first() should handle this.
                $results = $relationInstance->getResults(); // This needs to be defined in Relation or its children

                return $this->relations[$key] = $results;
            }
            // If the method exists but doesn't return a Relation, it's not a loadable relationship here.
            // This could be an accessor or other method.
        }
        return null;
    }

    /**
     * Determine if the given relation is loaded.
     */
    public function relationLoaded(string $key): bool
    {
        return \array_key_exists($key, $this->relations);
    }

    /**
     * Set the specific relationship in the model.
     */
    public function setRelation(string $relation, mixed $value): static
    {
        $this->relations[$relation] = $value;
        return $this;
    }

    /**
     * Get all loaded relations for the instance.
     * @return array<string, mixed>
     */
    public function getRelations(): array
    {
        return $this->relations;
    }

    /**
     * Set the entire relations array on the model.
     * @param array<string, mixed> $relations
     */
    public function setRelations(array $relations): static
    {
        $this->relations = $relations;
        return $this;
    }

    /**
     * Set the pivot accessor name for the model.
     * For BelongsToMany, allows accessing pivot data via $model->pivot->column_name.
     *
     * @param string $relationName The name of the BelongsToMany relation.
     * @param object|array $pivotData The pivot data.
     */
    public function setPivotData(string $relationName, object|array $pivotData): static
    {
        $this->pivotData[$relationName] = \is_array($pivotData) ? (object) $pivotData : $pivotData;
        return $this;
    }

    /**
     * Get the pivot data for a specific relation or all pivot data.
     *
     * @param string|null $relationName
     * @return object|array|null
     */
    public function getPivotData(?string $relationName = null): mixed
    {
        if ($relationName) {
            return $this->pivotData[$relationName] ?? null;
        }
        return $this->pivotData;
    }
}
