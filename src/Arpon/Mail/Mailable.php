<?php

namespace Arpon\Mail;

use Arpon\Support\Facades\View;

class Mailable
{
    /**
     * The view to use for the mailable.
     *
     * @var string
     */
    protected $view;

    /**
     * The view data for the mailable.
     *
     * @var array
     */
    protected $viewData = [];

    /**
     * The "from" address and name.
     *
     * @var array
     */
    public $from = [];

    /**
     * The "to" address and name.
     *
     * @var array
     */
    public $to = [];

    /**
     * The "cc" address and name.
     *
     * @var array
     */
    public $cc = [];

    /**
     * The "bcc" address and name.
     *
     * @var array
     */
    public $bcc = [];

    /**
     * The subject of the message.
     *
     * @var string
     */
    public $subject;

    /**
     * The attachments for the message.
     *
     * @var array
     */
    public $attachments = [];

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this;
    }

    /**
     * Set the view and view data for the mailable.
     *
     * @param  string  $view
     * @param  array  $data
     * @return $this
     */
    public function view($view, array $data = [])
    {
        $this->view = $view;
        $this->viewData = $data;

        return $this;
    }

    /**
     * Render the mailable into a view.
     *
     * @return string
     */
    public function render()
    {
        return View::make($this->view, $this->viewData)->render();
    }

    /**
     * Set the "from" address and name.
     *
     * @param  string|array  $address
     * @param  string|null  $name
     * @return $this
     */
    public function from($address, $name = null)
    {
        $this->from = $this->normalizeAddress($address, $name);

        return $this;
    }

    /**
     * Set the "to" address and name.
     *
     * @param  string|array  $address
     * @param  string|null  $name
     * @return $this
     */
    public function to($address, $name = null)
    {
        $this->to = $this->normalizeAddress($address, $name);

        return $this;
    }

    /**
     * Set the "cc" address and name.
     *
     * @param  string|array  $address
     * @param  string|null  $name
     * @return $this
     */
    public function cc($address, $name = null)
    {
        $this->cc = $this->normalizeAddress($address, $name);

        return $this;
    }

    /**
     * Set the "bcc" address and name.
     *
     * @param  string|array  $address
     * @param  string|null  $name
     * @return $this
     */
    public function bcc($address, $name = null)
    {
        $this->bcc = $this->normalizeAddress($address, $name);

        return $this;
    }

    /**
     * Set the subject of the message.
     *
     * @param  string  $subject
     * @return $this
     */
    public function subject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Normalize the address.
     *
     * @param  string|array  $address
     * @param  string|null  $name
     * @return array
     */
    protected function normalizeAddress($address, $name = null)
    {
        if (is_array($address)) {
            return $address;
        }

        return ['address' => $address, 'name' => $name];
    }
}
