<?php

namespace Arpon\Database\ORM\Concerns;

// For type hinting if needed

trait HasAttributes
{
    /**
     * The model's attributes.
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    /**
     * The model attribute's original state.
     * @var array<string, mixed>
     */
    protected array $original = [];

    /**
     * The attributes that should be cast to native types.
     * Example: ['is_admin' => 'boolean', 'options' => 'array']
     * @var array<string, string>
     */
    protected array $casts = [];


    /**
     * Get an attribute from the model.
     *
     * @param string $key
     * @return mixed
     */
    public function getAttribute(string $key): mixed
    {
        if (!$key) {
            return null;
        }

        // If the attribute exists in the attributes array, we will return that value.
        if (array_key_exists($key, $this->attributes)) {
            return $this->castAttribute($key, $this->attributes[$key]);
        }

        // Check for an accessor method (e.g., getFirstNameAttribute)
        $accessorMethod = 'get' . str_replace('_', '', ucwords($key, '_')) . 'Attribute';
        if (method_exists($this, $accessorMethod)) {
            // Call the accessor and cache its result in attributes for this instance lifecycle
            // This behavior is similar to Laravel's, preventing multiple accessor calls.
            // However, be cautious if the accessor depends on other dynamic state.
            // For simplicity, we're not caching the accessor result back into $this->attributes here,
            // but a more advanced ORM might.
            return $this->castAttribute($key, $this->$accessorMethod());
        }

        // If the key is a loaded relationship (handled in Model::getAttribute)
        // This trait focuses on direct attributes.

        return null;
    }

    /**
     * Set a given attribute on the model.
     *
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function setAttribute(string $key, mixed $value): static
    {
        // Check for a mutator method (e.g., setFirstNameAttribute)
        $mutatorMethod = 'set' . str_replace('_', '', ucwords($key, '_')) . 'Attribute';
        if (method_exists($this, $mutatorMethod)) {
            $this->$mutatorMethod($value); // Mutator is responsible for setting the attribute
            return $this;
        }

        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * Determine if an attribute exists on the model.
     */
    public function attributeExists(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    /**
     * Unset an attribute on the model.
     */
    public function unsetAttribute(string $key): static
    {
        unset($this->attributes[$key]);
        // Consider also unsetting from $this->original if it makes sense for your dirty tracking.
        return $this;
    }

    /**
     * Get all of the current attributes on the model.
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        $attrs = [];
        // Merge attributes and accessor results for toArray/jsonSerialize
        // This requires knowing which methods are accessors.
        // For simplicity, just return $this->attributes. A more complete ORM
        // would iterate through fillable/visible and call accessors.
        foreach (array_keys($this->attributes) as $key) {
            $attrs[$key] = $this->getAttribute($key); // Ensures casting and accessors are applied
        }
        return $attrs;
    }

    /**
     * Get the model's original attribute values.
     * @param string|null $key The specific original attribute to get.
     * @param mixed $default Default value if the original attribute doesn't exist.
     * @return mixed
     */
    public function getOriginal(?string $key = null, mixed $default = null): mixed
    {
        if ($key) {
            return array_key_exists($key, $this->original) ? $this->original[$key] : $default;
        }
        return $this->original;
    }

    /**
     * Sync the original attributes with the current attributes.
     * This is typically called after a model is saved or fetched.
     */
    public function syncOriginal(): static
    {
        $this->original = $this->attributes;
        return $this;
    }

    /**
     * Get the attributes that have been changed since last sync.
     * @return array<string, mixed>
     */
    public function getDirty(): array
    {
        $dirty = [];
        $currentAttributes = $this->getAttributes(); // Use getAttributes to include accessor values if they modify underlying state

        foreach ($currentAttributes as $key => $value) {
            if (!array_key_exists($key, $this->original)) {
                // Attribute is new
                $dirty[$key] = $value;
            } elseif ($this->original[$key] !== $value && !$this->originalIsNumericallyEquivalent($key, $value)) {
                // Attribute has changed
                $dirty[$key] = $value;
            }
        }
        return $dirty;
    }

    protected function originalIsNumericallyEquivalent(string $key, mixed $currentValue): bool
    {
        if (!isset($this->original[$key])) return false;
        $originalValue = $this->original[$key];

        if (is_numeric($currentValue) && is_numeric($originalValue)) {
            return (string) $currentValue === (string) $originalValue;
        }
        return false;
    }


    /**
     * Determine if the model or given attributes are "dirty".
     * @param string|array|null $attributes
     */
    public function isDirty(string|array|null $attributes = null): bool
    {
        $dirty = $this->getDirty();
        if (is_null($attributes)) {
            return !empty($dirty);
        }
        $attributes = is_array($attributes) ? $attributes : func_get_args();
        foreach ($attributes as $attribute) {
            if (array_key_exists($attribute, $dirty)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get attributes that are suitable for saving (e.g., excluding non-column data if any).
     * For now, returns all attributes. Could be extended to respect $fillable or other rules.
     */
    protected function getAttributesForSave(): array
    {
        // This should return raw attributes, not those modified by accessors for saving.
        return $this->attributes;
    }

    /**
     * Cast an attribute to a native PHP type.
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    protected function castAttribute(string $key, mixed $value): mixed
    {
        if (is_null($value) || !isset($this->casts[$key])) {
            return $value;
        }

        return match (strtolower($this->casts[$key])) {
            'int', 'integer' => (int) $value,
            'real', 'float', 'double' => (float) $value,
            'string' => (string) $value,
            'bool', 'boolean' => (bool) $value,
            'array', 'json' => is_string($value) ? json_decode($value, true) : (array) $value,
            'object' => is_string($value) ? json_decode($value, false) : (object) $value,
            'date', 'datetime' => $this->asDateTime($value),
            // 'timestamp' => $this->asTimestamp($value), // Requires Carbon or similar
            default => $value,
        };
    }

    /**
     * Return a timestamp as DateTime object.
     * For simplicity, assumes $value is a string or can be parsed by DateTime.
     */
    protected function asDateTime(mixed $value): ?\DateTimeInterface
    {
        if ($value instanceof \DateTimeInterface) {
            return $value;
        }
        if (is_numeric($value)) { // Assume Unix timestamp
            return (new \DateTimeImmutable())->setTimestamp((int)$value);
        }
        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception $e) {
            return null; // Or throw an exception for invalid date format
        }
    }

    // Magic methods for attribute access
    public function __get(string $key): mixed
    {
        // This will be overridden by Model::__get to include relationship handling
        return $this->getAttribute($key);
    }

    public function __set(string $key, mixed $value): void
    {
        // This will be overridden by Model::__set
        $this->setAttribute($key, $value);
    }

    public function __isset(string $key): bool
    {
        // This will be overridden by Model::__isset
        return !is_null($this->getAttribute($key));
    }

    public function __unset(string $key): void
    {
        // This will be overridden by Model::__unset
        $this->unsetAttribute($key);
    }
}
