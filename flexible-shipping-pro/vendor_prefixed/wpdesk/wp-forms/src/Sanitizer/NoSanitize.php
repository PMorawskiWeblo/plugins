<?php

namespace FSProVendor\WPDesk\Forms\Sanitizer;

use FSProVendor\WPDesk\Forms\Sanitizer;
class NoSanitize implements Sanitizer
{
    public function sanitize($value)
    {
        return $value;
    }
}
