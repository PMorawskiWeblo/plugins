<?php

namespace FSProVendor\Octolize\BetterDocs\Beacon;

class BeaconOptions
{
    /**
     * @var array
     */
    private $options;
    public function __construct(array $doc_categories = [], string $url = 'https://octolize.com/docs/')
    {
        $this->options = $this->prepare_default_options($url, $doc_categories);
    }
    public function get_options()
    {
        return $this->options;
    }
    public function set_option(array $option): self
    {
        $this->options = array_merge_recursive($this->options, $option);
        return $this;
    }
    private function prepare_default_options(string $url, array $doc_categories): array
    {
        $categories = '';
        if (!empty($doc_categories)) {
            $categories = '?%64%6F%63%5F%63%61%74%65%67%6F%72%79=' . implode(',', $doc_categories);
        }
        return apply_filters('octolize/betterdocs-beacon/options', ['CHAT' => ['show' => \false, 'label' => __('Contact us', 'flexible-shipping-pro'), 'subtitle' => __('Need help? Send us a message.', 'flexible-shipping-pro'), 'subtitle_two' => __('We usually respond within max a few hours.', 'flexible-shipping-pro')], 'ANSWER' => ['label' => __('Find answer', 'flexible-shipping-pro'), 'subtitle' => __('Knowledge base', 'flexible-shipping-pro')], 'URL' => $url . 'wp-json/wp/v2/docs' . $categories, 'SEARCH' => ['SEARCH_URL' => $url . 'wp-json/wp/v2/docs' . $categories, 'SEARCH_PLACEHOLDER' => __('Search...', 'flexible-shipping-pro'), 'OOPS' => __('Oops...', 'flexible-shipping-pro'), 'NOT_FOUND' => __('We couldn’t find any docs that match your search. Try searching for a new term.', 'flexible-shipping-pro')], 'FEEDBACK' => ['DISPLAY' => \true, 'SUCCESS' => __('Thanks for your feedback', 'flexible-shipping-pro'), 'TEXT' => __('How did you like it?', 'flexible-shipping-pro'), 'URL' => $url . '?rest_route=/betterdocs/feedback'], 'RESPONSE' => ['title' => __('Thanks for the feedback', 'flexible-shipping-pro'), 'icon' => ['show' => \false]], 'ASKFORM' => ['NAME' => __('Name', 'flexible-shipping-pro'), 'EMAIL' => __('Email address', 'flexible-shipping-pro'), 'SUBJECT' => __('Subject', 'flexible-shipping-pro'), 'TEXTAREA' => __('How can we help?', 'flexible-shipping-pro'), 'ATTACHMENT' => __('Only .jpg, .png, .jpeg, .gif files are supported.', 'flexible-shipping-pro'), 'SENDING' => __('Sending', 'flexible-shipping-pro'), 'SEND' => __('Send', 'flexible-shipping-pro')], 'ASK_URL' => $url . '?rest_route=/betterdocs/ask', 'BRANDING' => ['show' => \false], 'THANKS' => ['title' => __('Thanks!', 'flexible-shipping-pro'), 'text' => __('Your message has been sent successfully.', 'flexible-shipping-pro')]]);
    }
}
