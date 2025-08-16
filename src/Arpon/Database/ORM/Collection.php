<?php

namespace Arpon\Database\ORM;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

// For IteratorAggregate
// For IteratorAggregate getIterator return type

class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    /**
     * The items contained in the collection.
     * @var array<int|string, mixed>
     */
    protected array $items = [];

    /**
     * Create a new collection.
     * @param array<int|string, mixed> $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Get all of the items in the collection.
     * @return array<int|string, mixed>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Get the first item from the collection.
     */
    public function first(): mixed
    {
        return $this->items[array_key_first($this->items)] ?? null;
    }

    /**
     * Get the last item from the collection.
     */
    public function last(): mixed
    {
        return $this->items[array_key_last($this->items)] ?? null;
    }


    /**
     * Determine if the collection is empty or not.
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Determine if the collection is not empty.
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * Get the number of items in the collection.
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Get an iterator for the items.
     */
    public function getIterator(): Traversable // Or ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    // ArrayAccess methods
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null; // Return null if not set, consistent with array behavior
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    // JsonSerializable method
    public function jsonSerialize(): array
    {
        return array_map(function ($value) {
            if ($value instanceof JsonSerializable) {
                return $value->jsonSerialize();
            } elseif ($value instanceof Model) { // Assuming Model implements toArray()
                return $value->toArray();
            }
            return $value;
        }, $this->items);
    }

    /**
     * Convert the collection to its string representation.
     */
    public function __toString(): string
    {
        return json_encode($this->jsonSerialize());
    }


    /**
     * Add an item to the collection.
     */
    public function push(mixed $value): static
    {
        $this->items[] = $value;
        return $this;
    }

    /**
     * Get a Capped Collection
     */
    public function take(int $limit): static
    {
        if ($limit < 0) {
            return new static(array_slice($this->items, $limit, abs($limit)));
        }
        return new static(array_slice($this->items, 0, $limit));
    }


    /**
     * Find an item in the collection by key.
     * If items are Models, it searches by primary key by default.
     */
    public function find(mixed $keyToFind, ?string $attributeKey = null): mixed // ?Model or mixed
    {
        foreach ($this->items as $item) {
            if ($item instanceof Model) {
                $modelKeyName = $attributeKey ?? $item->getKeyName();
                if ($item->getAttribute($modelKeyName) == $keyToFind) {
                    return $item;
                }
            } elseif (is_array($item) && $attributeKey && isset($item[$attributeKey]) && $item[$attributeKey] == $keyToFind) {
                return $item;
            } elseif (!$attributeKey && $item == $keyToFind) { // Simple value search
                return $item;
            }
        }
        return null;
    }

    /**
     * Load a relationship on all models in the collection.
     */
    public function load(string|array $relations): static
    {
        if ($this->isEmpty()) {
            return $this;
        }

        // Assuming all items are Model instances of the same type for simplicity
        $firstItem = $this->first();
        if (!$firstItem instanceof Model) {
            // Cannot load relations on non-model collections or mixed collections easily
            return $this;
        }

        // Create a new query from the first model to access its 'with' and 'eagerLoadRelations'
        $query = $firstItem->newQuery()->with($relations);
        $query->eagerLoadRelations($this->items); // Pass the array of models

        return $this;
    }

    /**
     * Get the array of primary keys from a collection of models.
     * @param string|null $keyName The name of the primary key attribute.
     * @return array
     */
    public function modelKeys(?string $keyName = null): array
    {
        return array_map(function ($item) use ($keyName) {
            if ($item instanceof Model) {
                return $item->getAttribute($keyName ?? $item->getKeyName());
            }
            return null; // Or throw an exception if item is not a Model
        }, $this->items);
    }

    /**
     * Pluck an array of values from a collection.
     *
     * @param  string|array  $value
     * @param  string|null  $key
     * @return static
     */
    public function pluck(string|array $value, ?string $key = null): static
    {
        $results = [];
        [$value, $key] = $this->explodePluckParameters($value, $key);

        foreach ($this->items as $item) {
            $itemValue = $this->dataGet($item, $value);

            if (is_null($key)) {
                $results[] = $itemValue;
            } else {
                $itemKey = $this->dataGet($item, $key);
                if (is_object($itemKey) && method_exists($itemKey, '__toString')) {
                    $itemKey = (string) $itemKey;
                }
                $results[$itemKey] = $itemValue;
            }
        }
        return new static($results);
    }

    protected function explodePluckParameters(string|array $value, ?string $key): array
    {
        $value = is_string($value) ? explode('.', $value) : $value;
        $key = is_null($key) || is_array($key) ? $key : explode('.', (string) $key);
        return [$value, $key];
    }

    protected function dataGet(mixed $target, string|array|int|null $key, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $target;
        }
        $key = is_array($key) ? $key : explode('.', is_int($key) ? (string)$key : $key);
        foreach ($key as $i => $segment) {
            unset($key[$i]);
            if (is_null($segment)) {
                return $target;
            }
            if ($segment === '*') {
                // Handle wildcard - not implemented for simplicity here
                return $default;
            }
            if (is_array($target) && array_key_exists($segment, $target)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } elseif (is_object($target) && $target instanceof Model && $target->relationLoaded($segment)) { // Check loaded relations
                $target = $target->getRelationValue($segment);
            } elseif (is_object($target) && $target instanceof Model && method_exists($target, $segment)) { // Check relationship method
                $target = $target->getRelationValue($segment);
            }
            else {
                return $default;
            }
        }
        return $target;
    }

    /**
     * Convert the collection to an array.
     */
    public function toArray(): array
    {
        return array_map(function ($value) {
            return $value instanceof Model ? $value->toArray() : $value;
        }, $this->items);
    }

    /**
     * Map the collection to a new collection.
     */
    public function map(callable $callback): static
    {
        $keys = array_keys($this->items);
        $items = array_map($callback, $this->items, $keys);
        return new static(array_combine($keys, $items));
    }

    /**
     * Filter the collection using a callback.
     */
    public function filter(callable $callback): static
    {
        return new static(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
    }
}
