<?php

/**
 * Array logger.
 *
 * @package WPDesk\FS\TableRate\Logger
 */
namespace FSProVendor\WPDesk\FS\TableRate\Logger;

use FSProVendor\Psr\Log\LoggerInterface;
use FSProVendor\Psr\Log\LoggerTrait;
/**
 * Can log to array.
 */
if (defined('FSProVendor\FLEXIBLE_SHIPPING_PSR_NOT_PREFIXED') && FLEXIBLE_SHIPPING_PSR_NOT_PREFIXED) {
    class ArrayLogger implements LoggerInterface
    {
        use LoggerTrait;
        /**
         * @var array
         */
        private $messages = array();
        /**
         * @param mixed $level .
         * @param string $message .
         * @param array $context .
         */
        public function log($level, $message, array $context = array()): void
        {
            $this->messages[] = array('level' => $level, 'message' => $message, 'context' => $context);
        }
        /**
         * @return array
         */
        public function get_messages()
        {
            return $this->messages;
        }
    }
} else {
    class ArrayLogger implements \FSProVendor\FSVendor\Psr\Log\LoggerInterface
    {
        use \FSProVendor\FSVendor\Psr\Log\LoggerTrait;
        /**
         * @var array
         */
        private $messages = array();
        /**
         * @param mixed $level .
         * @param string $message .
         * @param array $context .
         */
        public function log($level, $message, array $context = array()): void
        {
            $this->messages[] = array('level' => $level, 'message' => $message, 'context' => $context);
        }
        /**
         * @return array
         */
        public function get_messages()
        {
            return $this->messages;
        }
    }
}
