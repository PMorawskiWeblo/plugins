<?php

/**
 * @package Octolize\Onboarding
 */
namespace FSProVendor\Octolize\Onboarding;

/**
 * When to show Onboarding.
 */
interface OnboardingShouldShowStrategy
{
    /**
     * Should Onboarding be visible?
     *
     * @return bool
     */
    public function should_display();
}
