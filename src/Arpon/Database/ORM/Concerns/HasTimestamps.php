<?php

namespace Arpon\Database\ORM\Concerns;

// If you decide to use Carbon:
// use Carbon\Carbon;
// use Carbon\CarbonInterface;

trait HasTimestamps
{
    /**
     * Indicates if the model should be timestamped.
     * This property MUST be defined in the Model class that uses this trait.
     * public bool $timestamps = true;
     */

    /**
     * Update the model's update timestamp.
     *
     * @return bool False if an update was not performed.
     */
    public function touch(): bool
    {
        if (!$this->usesTimestamps()) {
            return false;
        }
        $this->updateTimestamps();
        // The save method in the Model class handles the actual database update.
        // It checks if anything is dirty (now the updated_at timestamp is).
        return $this->save();
    }

    /**
     * Update the creation and update timestamps.
     * This method is called during the save process in the Model.
     */
    protected function updateTimestamps(): void
    {
        $time = $this->freshTimestampString();

        // If the model is new and uses timestamps, set created_at
        if (!$this->exists && $this->usesTimestamps() && static::CREATED_AT && !$this->isDirty(static::CREATED_AT)) {
            $this->setCreatedAt($time);
        }

        // Always set updated_at if using timestamps (unless it's already dirty from manual set)
        if ($this->usesTimestamps() && static::UPDATED_AT && !$this->isDirty(static::UPDATED_AT)) {
            $this->setUpdatedAt($time);
        }
    }

    /**
     * Set the value of the "created at" attribute.
     *
     * @param  mixed  $value
     * @return static
     */
    public function setCreatedAt(mixed $value): static
    {
        // Assumes Model class has `static::CREATED_AT` defined
        $this->attributes[static::CREATED_AT] = $value;
        return $this;
    }

    /**
     * Set the value of the "updated at" attribute.
     *
     * @param  mixed  $value
     * @return static
     */
    public function setUpdatedAt(mixed $value): static
    {
        // Assumes Model class has `static::UPDATED_AT` defined
        $this->attributes[static::UPDATED_AT] = $value;
        return $this;
    }

    /**
     * Get a fresh timestamp for the model.
     *
     * @return string In 'Y-m-d H:i:s' format.
     */
    public function freshTimestampString(): string
    {
        // If using Carbon:
        // return Carbon::now($this->getDateFormat());
        return date($this->getDateFormat());
    }

    /**
     * Get a fresh timestamp for the model as a DateTime object.
     *
     * @return \DateTimeInterface
     */
    public function freshTimestamp(): \DateTimeInterface
    {
        // If using Carbon:
        // return Carbon::now();
        return new \DateTimeImmutable();
    }


    /**
     * Get the name of the "created at" column.
     *
     * @return string|null
     */
    public function getCreatedAtColumn(): ?string
    {
        return defined('static::CREATED_AT') ? static::CREATED_AT : null;
    }

    /**
     * Get the name of the "updated at" column.
     *
     * @return string|null
     */
    public function getUpdatedAtColumn(): ?string
    {
        return defined('static::UPDATED_AT') ? static::UPDATED_AT : null;
    }

    /**
     * Determine if the model uses timestamps.
     * Accesses the public $timestamps property of the consuming Model class.
     *
     * @return bool
     */
    public function usesTimestamps(): bool
    {
        // This relies on the Model class having: public bool $timestamps = true;
        return $this->timestamps ?? true; // Default to true if not explicitly set
    }

    /**
     * Get the date format for timestamps.
     * This can be overridden in the Model class if needed.
     *
     * @return string
     */
    protected function getDateFormat(): string
    {
        // Access $dateFormat property if it exists on the Model, otherwise default.
        return $this->dateFormat ?? 'Y-m-d H:i:s';
    }

    /**
     * Get timestamp attributes that should be included when saving.
     * This is used by the Model's save method.
     *
     * @return array
     */
    protected function getTimestampAttributesForSave(): array
    {
        $attrs = [];
        if ($this->usesTimestamps()) {
            $createdAtColumn = $this->getCreatedAtColumn();
            if ($createdAtColumn && isset($this->attributes[$createdAtColumn])) {
                $attrs[$createdAtColumn] = $this->attributes[$createdAtColumn];
            }

            $updatedAtColumn = $this->getUpdatedAtColumn();
            if ($updatedAtColumn && isset($this->attributes[$updatedAtColumn])) {
                $attrs[$updatedAtColumn] = $this->attributes[$updatedAtColumn];
            }
        }
        return $attrs;
    }
}
