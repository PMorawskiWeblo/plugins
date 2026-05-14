<?php

namespace FSProVendor\WPDesk\Forms\Serializer;

use FSProVendor\WPDesk\Forms\Serializer;
class NoSerialize implements Serializer
{
    public function serialize($value)
    {
        return $value;
    }
    public function unserialize($value)
    {
        return $value;
    }
}
