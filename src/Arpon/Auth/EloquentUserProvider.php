<?php

namespace Arpon\Auth;

use Arpon\Contracts\Auth\Authenticatable as UserContract;
use Arpon\Contracts\Auth\UserProvider;
use Arpon\Contracts\Hashing\Hasher as HasherContract;
use Arpon\Database\ORM\Model;

class EloquentUserProvider implements UserProvider
{
    /**
     * The hasher implementation.
     *
     * @var HasherContract
     */
    protected HasherContract $hasher;

    /**
     * The Eloquent user model.
     *
     * @var string
     */
    protected string $model;

    /**
     * Create a new database user provider.
     *
     * @param HasherContract $hasher
     * @param  string  $model
     * @return void
     */
    public function __construct(HasherContract $hasher, string $model)
    {
        $this->model = $model;
        $this->hasher = $hasher;
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return UserContract|null
     */
    public function retrieveById(mixed $identifier): ?Model
    {
        $model = $this->createModel();

        return $model->newQuery()
            ->where($model->getAuthIdentifierName(), $identifier)
            ->first();
    }

    public function retrieveByToken(mixed $identifier, string $token): ?Model
    {
        $model = $this->createModel();

        $retrievedModel = $model->newQuery()->where(
            $model->getAuthIdentifierName(), $identifier
        )->first();

        if (! $retrievedModel) {
            return null;
        }

        $rememberToken = $retrievedModel->getRememberToken();

        return $rememberToken && $this->hasher->check($token, $rememberToken)
                ? $retrievedModel : null;
    }

    public function updateRememberToken(Model $user, ?string $token): void
    {
        $user->setRememberToken($token);

        $user->save();
    }

    

    

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return UserContract|null
     */
    public function retrieveByCredentials(array $credentials): ?Model
    {
        if (empty($credentials) ||
            (count($credentials) === 1 &&
                str_contains($this->firstCredentialKey($credentials), 'password'))) {
            return null;
        }

        // First we will add each credential element to the query as a where clause.
        // Then we can execute the query and, if we found a user, return it in a
        // Eloquent User "model" that will be utilized by the Guard instances.
        $query = $this->createModel()->newQuery();

        foreach ($credentials as $key => $value) {
            if (str_contains($key, 'password') || str_contains($key, 'remember')) {
                continue;
            }

            if (is_array($value) || $value instanceof \Arpon\Contracts\Support\Arrayable) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }

        return $query->first();
    }

    /**
     * Get the first key from the credential array.
     *
     * @param  array  $credentials
     * @return string|null
     */
    protected function firstCredentialKey(array $credentials): ?string
    {
        foreach ($credentials as $key => $value) {
            return $key;
        }
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param UserContract|Model $user
     * @param array $credentials
     * @return bool
     */
    public function validateCredentials(UserContract|Model $user, array $credentials): bool
    {
        if (is_null($plain = $credentials['password'])) {
            return false;
        }

        return $this->hasher->check($plain, $user->getAuthPassword());
    }

    /**
     * Rehash the user's password if required.
     *
     * @param UserContract $user
     * @param  array  $credentials
     * @param  bool  $force
     * @return void
     */
    public function rehash(UserContract $user, array $credentials, bool $force = false): void
    {
        if (is_null($plain = $credentials['password'])) {
            return;
        }

        if ($this->hasher->needsRehash($user->getAuthPassword()) || $force) {
            $user->forceFill([
                $user->getAuthPasswordName() => $this->hasher->make($plain),
            ])->save();
        }
    }

    public function createModel(): Model
    {
        $class = '\\'.ltrim($this->model, '\\');

        return new $class;
    }

    public function getHasher(): HasherContract
    {
        return $this->hasher;
    }

    /**
     * Sets the hasher implementation.
     *
     * @param HasherContract $hasher
     * @return $this
     */
    public function setHasher(HasherContract $hasher): static
    {
        $this->hasher = $hasher;

        return $this;
    }

    /**
     * Gets the name of the Eloquent user model.
     *
     * @return string
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Sets the name of the Eloquent user model.
     *
     * @param  string  $model
     * @return $this
     */
    public function setModel(string $model): static
    {
        $this->model = $model;

        return $this;
    }
}