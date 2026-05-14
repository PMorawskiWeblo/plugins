<?php

namespace FSProVendor\WPDesk\License\Page;

use FSProVendor\WPDesk\License\Page\License\Action\LicenseActivation;
use FSProVendor\WPDesk\License\Page\License\Action\LicenseDeactivation;
use FSProVendor\WPDesk\License\Page\License\Action\Nothing;
/**
 * Action factory.
 *
 * @package WPDesk\License\Page\License
 */
class LicensePageActions
{
    /**
     * Creates action object according to given param
     *
     * @param string $action .
     * @param bool   $add_settings_error .
     *
     * @return Action
     */
    public function create_action($action, $add_settings_error = \true)
    {
        if ('activate' === $action) {
            return new LicenseActivation($add_settings_error);
        }
        if ('deactivate' === $action) {
            return new LicenseDeactivation($add_settings_error);
        }
        return new Nothing();
    }
}
