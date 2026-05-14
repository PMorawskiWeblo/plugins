<?php

namespace FSProVendor\WPDesk\Tracker\Deactivation;

interface ReasonsFactory
{
    /**
     * Create default reasons.
     *
     * @return Reason[]
     */
    public function createReasons(): array;
}
