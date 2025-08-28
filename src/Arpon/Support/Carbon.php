<?php

namespace Arpon\Support;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;

class Carbon
{
    protected DateTimeImmutable $date;

    public function __construct($time = 'now', $timezone = null)
    {
        if ($time instanceof DateTimeImmutable) {
            $this->date = $time;
        } elseif ($time instanceof DateTime) {
            $this->date = DateTimeImmutable::createFromMutable($time);
        } else {
            $this->date = new DateTimeImmutable($time, $timezone ? new DateTimeZone($timezone) : null);
        }
    }

    public static function now($timezone = null)
    {
        return new static('now', $timezone);
    }

    public static function parse($time, $timezone = null)
    {
        return new static($time, $timezone);
    }

    public function addDays($days)
    {
        return new static($this->date->modify("+{$days} days"));
    }

    public function subDays($days)
    {
        return new static($this->date->modify("-{$days} days"));
    }

    public function format($format)
    {
        return $this->date->format($format);
    }

    public function isPast()
    {
        return $this->date < new DateTimeImmutable();
    }

    public function addMinutes($minutes)
    {
        return new static($this->date->modify("+{$minutes} minutes"));
    }

    public function __toString()
    {
        return $this->format('Y-m-d H:i:s');
    }
}
