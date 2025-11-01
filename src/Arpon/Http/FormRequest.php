<?php

namespace Arpon\Http;

use Arpon\Http\Exceptions\AuthorizationException;
use Arpon\Validation\ValidationException;

class FormRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true; // By default, allow all requests. Override in child classes.
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return []; // No rules by default. Override in child classes.
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages(): array
    {
        return []; // No custom messages by default. Override in child classes.
    }

    /**
     * Get the validated data from the request.
     *
     * @return array
     * @throws ValidationException
     */
    public function validated(): array
    {
        return $this->validate($this->rules(), $this->messages());
    }

    /**
     * Validate the request's data.
     *
     * @return array
     * @throws ValidationException|Exceptions\AuthorizationException
     */
    public function validateResolved(): array
    {
        $this->ensureAuthorizationPasses();

        return $this->validate($this->rules(), $this->messages());
    }

    /**
     * Handle a failed authorization attempt.
     *
     * @throws AuthorizationException
     */
    protected function failedAuthorization()
    {
        throw new AuthorizationException('This action is unauthorized.');
    }

    /**
     * Ensure that the authorization passes.
     *
     * @throws AuthorizationException
     */
    protected function ensureAuthorizationPasses(): void
    {
        if (! $this->authorize()) {
            $this->failedAuthorization();
        }
    }
}