<?php

namespace Arpon\Database\ORM;

use ArrayAccess;
use BadMethodCallException;
use Arpon\Database\ORM\Concerns\HasAttributes;
use Arpon\Database\ORM\Concerns\HasTimestamps;
use Arpon\Database\ORM\Concerns\QueriesRelationships;
use Arpon\Database\ORM\Relations\Relation;
use Arpon\Database\Query\Builder as QueryBuilder;
use JsonSerializable;
use ReflectionException;



abstract class Model implements ArrayAccess, JsonSerializable
{
    // Use the defined traits for ORM functionalities
    use HasAttributes, HasTimestamps, QueriesRelationships;

    protected ?string $connection = null;
    protected string $table;
    protected string $primaryKey = 'id';
    protected string $keyType = 'int';
    public bool $incrementing = true;
    protected int $perPage = 15;
    public bool $exists = false;
    protected array $fillable = [];
    protected array $guarded = ['*'];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected array $hidden = [];
    public bool $timestamps = true;
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    protected string $dateFormat = 'Y-m-d H:i:s';
    protected static array $booted = [];

    public function __construct(array $attributes = [])
    {
        $this->bootIfNotBooted();
        $this->syncOriginal();
        $this->fill($attributes);
    }
    public function __debugInfo(): array
    {
        // Get all properties of the object.
        $properties = get_object_vars($this);

        // Filter the 'attributes' array to remove hidden items.
        if (isset($properties['attributes'])) {
            foreach ($this->hidden as $hiddenAttribute) {
                unset($properties['attributes'][$hiddenAttribute]);
            }
        }

        // Also filter the 'original' array for consistency.
        if (isset($properties['original'])) {
            foreach ($this->hidden as $hiddenAttribute) {
                unset($properties['original'][$hiddenAttribute]);
            }
        }
        return $properties;
    }
    protected function bootIfNotBooted(): void
    {
        $class = static::class;
        if (!isset(static::$booted[$class])) {
            static::$booted[$class] = true;
            if (\method_exists($class, 'boot')) {
                forward_static_call([$class, 'boot']);
            }
            $this->bootTraits();
        }
    }

    /**
     * Boot all of the bootable traits on the model.
     * This version manually collects traits from the class and its parents
     * if class_uses_recursive is unavailable.
     */
    protected function bootTraits(): void
    {
        $class = static::class;
        $traitsToBoot = [];

        if (\function_exists('\class_uses_recursive')) {
            $traitsToBoot = \class_uses_recursive($class);
        } else {
            $currentClass = $class;
            do {
                $traits = \class_uses($currentClass);
                if ($traits) {
                    foreach ($traits as $trait) {
                        if (!\in_array($trait, $traitsToBoot)) {
                            $traitsToBoot[] = $trait;
                        }
                    }
                }
                $currentClass = \get_parent_class($currentClass);
            } while ($currentClass);
        }

        foreach ($traitsToBoot as $trait) {
            $traitBasename = \basename(\str_replace('\\', '/', $trait));
            $method = 'boot' . $traitBasename;
            if (\method_exists($class, $method)) {
                forward_static_call([$class, $method]);
            }
        }
    }

    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }
        return $this;
    }

    public function isFillable(string $key): bool
    {
        if (!empty($this->fillable)) {
            return \in_array($key, $this->fillable);
        }
        if ($this->guarded === ['*']) {
            return false;
        }
        return empty($this->guarded) || !\in_array($key, $this->guarded);
    }

    public function getTable(): string
    {
        if (!isset($this->table)) {
            $className = \basename(\str_replace('\\', '/', static::class));
            $this->table = \strtolower(\preg_replace('/(?<!^)[A-Z]/', '_$0', $className)) . 's';
        }
        return $this->table;
    }

    public function getKeyName(): string { return $this->primaryKey; }
    public function getKey(): mixed { return $this->getAttribute($this->getKeyName()); }
    public function setKey(mixed $value): static { $this->setAttribute($this->getKeyName(), $value); return $this; }

    public function newInstance(array $attributes = [], bool $exists = false): static
    {
        $model = new static;
        $model->exists = $exists;
        foreach ($attributes as $key => $value) { $model->attributes[$key] = $value; }
        if ($exists) { $model->syncOriginal(); }
        return $model;
    }

    /**
     * Get a new query builder for the model's table.
     * MODIFIED: Directly uses DatabaseManager's query method via the facade.
     */
    public function newQuery(): QueryBuilder
    {
        $manager = app('db'); // Get the manager instance from the container
        // Directly use manager's query method, which returns a QueryBuilder
        $builder = $manager->query($this->connection)->table($this->getTable());
        $builder->setModel($this);
        return $builder;
    }

    public function save(): bool
    {
        $query = $this->newQuery();
        $attributesToSave = $this->attributes;
        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
            $attributesToSave = \array_merge($attributesToSave, $this->getTimestampAttributesForSave());
        }
        if ($this->exists) {
            if (empty($this->getDirty())) { return true; }
            $id = $this->getKey();
            if ($id === null && $this->incrementing) { return false; }
            $dirtyAttributes = $this->getDirty();
            if (empty($dirtyAttributes)) return true;
            $result = $query->where($this->getKeyName(), '=', $id)->update($dirtyAttributes);
        } else {
            if ($this->incrementing && empty($attributesToSave[$this->getKeyName()])) {
                unset($attributesToSave[$this->getKeyName()]);
            }
            $id = $query->insertGetId($attributesToSave, $this->getKeyName());
            if ($id) {
                if ($this->incrementing) { $this->setAttribute($this->getKeyName(), $id); }
                $this->exists = true; $result = true;
            } else { $result = false; }
        }
        if ($result) { $this->syncOriginal(); }
        return (bool) $result;
    }

    public function update(array $attributes = []): bool
    {
        if (!$this->exists) { return false; }
        $this->fill($attributes);
        return $this->save();
    }

    public function delete(): bool
    {
        if (!$this->exists || $this->getKey() === null) { return false; }
        $query = $this->newQuery();
        $result = $query->where($this->getKeyName(), '=', $this->getKey())->delete();
        if ($result) { $this->exists = false; }
        return (bool) $result;
    }

    public static function create(array $attributes = []): static
    {
        $instance = new static();
        $instance->fill($attributes);
        $instance->save();
        return $instance;
    }

    public static function find(mixed $id): ?static
    {
        $instance = new static; return $instance->newQuery()->find($id);
    }
    public static function all(array $columns = ['*']): Collection
    {
        $instance = new static; return $instance->newQuery()->get($columns);
    }

    /**
     * @throws ReflectionException
     */
    public static function paginate(int $perPage = 15, array $columns = ['*'], string $pageName = 'page', ?int $page = null): \MyFramework\Pagination\LengthAwarePaginator
    {
        return (new static)->newQuery()->paginate($perPage, $columns, $pageName, $page);
    }


    public function fresh(array $with = []): ?static
    {
        if (! $this->exists) { return null; }
        $instance = static::find($this->getKey());
        if ($instance && !empty($with)) { $instance->load($with); }
        return $instance;
    }

    public function load(string|array $relations): static
    {
        $query = $this->newQuery()->with($relations);
        $query->eagerLoadRelations([$this]);
        return $this;
    }

    public function __get(string $key): mixed
    {
        if (\array_key_exists($key, $this->attributes) || \method_exists($this, 'get' . \str_replace('_', '', \ucwords($key, '_')) . 'Attribute')) {
            return $this->getAttribute($key);
        }
        if ($this->relationLoaded($key)) { return $this->relations[$key]; }
        if (\method_exists($this, $key)) { return $this->getRelationValue($key); }
        return null;
    }

    public function __set(string $key, mixed $value): void { $this->setAttribute($key, $value); }
    public function __isset(string $key): bool { return !\is_null($this->__get($key)); }
    public function __unset(string $key): void { $this->unsetAttribute($key); unset($this->relations[$key]); }

    public static function __callStatic(string $method, array $parameters)
    {
        return (new static)->newQuery()->$method(...$parameters);
    }

    public function __call(string $method, array $parameters)
    {
        try {
            if (\method_exists($this, $method)) {
                $reflection = new \ReflectionMethod($this, $method);
                $returnType = $reflection->getReturnType();
                if ($returnType && !$returnType->isBuiltin()) {
                    try {
                        if (\is_subclass_of($returnType->getName(), Relation::class)) {
                            return $this->$method(...$parameters);
                        }
                    } catch (\Throwable $e) { /* Log if needed */ }
                }
            }
            return $this->newQuery()->$method(...$parameters);
        } catch (BadMethodCallException $e) {
            throw new BadMethodCallException(\sprintf(
                'Call to undefined method %s::%s() or it is not a QueryBuilder method.',
                static::class, $method
            ), 0, $e);
        }
    }

    public function offsetExists(mixed $offset): bool { return !\is_null($this->__get((string)$offset)); }
    public function offsetGet(mixed $offset): mixed { return $this->__get((string)$offset); }
    public function offsetSet(mixed $offset, mixed $value): void { $this->setAttribute((string)$offset, $value); }
    public function offsetUnset(mixed $offset): void { $this->__unset((string)$offset); }

    public function toArray(): array
    {
        $attributes = $this->getAttributes();

        // Hide attributes that are in the $hidden array.
        foreach ($this->hidden as $hiddenAttribute) {
            unset($attributes[$hiddenAttribute]);
        }

        $relationsData = [];
        foreach ($this->getRelations() as $key => $relationValue) {
            if ($relationValue instanceof Model || $relationValue instanceof Collection || $relationValue instanceof JsonSerializable) {
                $relationsData[$key] = $relationValue->jsonSerialize();
            } elseif (\is_array($relationValue)) {
                $relationsData[$key] = \array_map(fn($item) => $item instanceof Model ? $item->toArray() : $item, $relationValue);
            }
        }
        return \array_merge($attributes, $relationsData);
    }

    public function jsonSerialize(): array { return $this->toArray(); }
    public function __toString(): string { return \json_encode($this->jsonSerialize()); }
    public function newCollection(array $models = []): Collection { return new Collection($models); }

    public function getPivot(?string $relationName = null): ?object
    {
        $pivotData = $this->getPivotData($relationName);
        if (empty($pivotData) && $relationName === null && !empty($this->pivotData)) {
            $pivotData = \reset($this->pivotData);
        }
        if ($pivotData) { return \is_array($pivotData) ? (object) $pivotData : $pivotData; }
        return null;
    }
}