<?php

namespace FSProVendor\WPDesk\License;

/**
 * Provides server urls to check for upgrade/activation
 *
 * @depreacted Check LicenseServer namespace
 * @package WPDesk\License
 */
class ServerAddressRepository
{
    /** @var string */
    private $product_id;
    /**
     * @param string $product_id Product if of a plugin. Retrieve from plugin_info
     */
    public function __construct($product_id)
    {
        $this->product_id = $product_id;
    }
    /**
     * Returns default server to ping ie. when checking if upgrade is available but is not yet activated
     *
     * @return string
     */
    public function get_default_update_url()
    {
        $urls = $this->get_server_urls();
        return reset($urls);
    }
    /**
     * Return list of servers to check for update
     *
     * @return string[] Full URL with protocol. Without ending /
     */
    public function get_server_urls()
    {
        return ['https://octolize.com'];
    }
}
