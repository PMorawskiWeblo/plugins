<?php

namespace FSProVendor\WPDesk\Forms\Serializer;

use FSProVendor\WPDesk\Forms\Serializer;
class SerializeSerializer implements Serializer
{
    public function serialize($value)
    {
        return serialize($value);
    }
    public function unserialize($value)
    {
        return unserialize($value);
    }
}
