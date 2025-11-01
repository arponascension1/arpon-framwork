<?php

namespace Arpon\Http;

class RedirectResponse extends Response
{
    protected string $targetUrl;
    protected array $with = [];
    protected array $withErrors = [];

    public function __construct(string $url, int $status = 302, array $headers = [])
    {
        parent::__construct('', $status, $headers);
        $this->targetUrl = $url;
        $this->headers->set('Location', $url);
    }

    public function with(array|string $key, mixed $value = null): static
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                app('session')->flash($k, $v);
            }
        } else {
            app('session')->flash($key, $value);
        }
        return $this;
    }

    public function withErrors(array $errors): static
    {
        $this->withErrors = array_merge($this->withErrors, $errors);
        return $this;
    }

    public function send(): void
    {
        foreach ($this->with as $key => $value) {
            app('session')->put($key, $value);
        }

        if (!empty($this->withErrors)) {
            app('session')->flash('errors', $this->withErrors);
        }

        parent::send();
    }

    public function intended(string $default = '/'): static
    {
        $url = app('session')->get('url.intended', $default);
        app('session')->forget('url.intended');
        $this->headers->set('Location', $url);
        return $this;
    }

    public function withInput(array $input): static
    {
        app('session')->flashInput($input);
        return $this;
    }
}