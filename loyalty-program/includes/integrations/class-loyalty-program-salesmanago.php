<?php

/**
 * SalesManago Integration Class
 *
 * @package Loyalty_Program
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Loyalty Program SalesManago Integration
 */
class Loyalty_Program_SalesManago
{
    /**
     * Check if SalesManago integration is enabled
     * 
     * @return bool
     */
    public static function is_enabled()
    {
        return get_option('loyalty_program_salesmanago_enabled', 'no') === 'yes';
    }

    /**
     * Get SalesManago credentials
     * 
     * @return array|false
     */
    public static function get_credentials()
    {
        if (!self::is_enabled()) {
            return false;
        }

        return array(
            'clientId' => get_option('loyalty_program_salesmanago_client_id', ''),
            'sha' => get_option('loyalty_program_salesmanago_sha', ''),
            'apiKey' => get_option('loyalty_program_salesmanago_api_key', ''),
            'owner' => get_option('loyalty_program_salesmanago_owner', ''),
        );
    }

    /**
     * Send user to SalesManago (upsert contact)
     * 
     * @param string $email User email (required)
     * @param array $contact_data Array with all contact data (optional)
     *   Possible keys:
     *   - name: First name
     *   - lastName: Last name  
     *   - phone: Phone number
     *   - birthday: Birth date (Y-m-d format)
     *   - streetAddress: Street address
     *   - zipCode: ZIP code
     *   - city: City
     *   - country: Country
     *   - province: Province/State
     * @param array $tags Tags to add (optional)
     * @param array $consents Array with consent details (optional)
     *   - sms: bool
     *   - newsletter: bool
     *   - sms_date: timestamp (optional)
     *   - newsletter_date: timestamp (optional)
     * @return array Response with 'success' and 'message' or 'contactId'
     */
    public static function upsert_contact($email, $contact_data = array(), $tags = null, $consents = array())
    {
        // Load logger
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        // Check if enabled
        if (!self::is_enabled()) {
            Loyalty_Program_Logger::warning('SalesManago integration is disabled');
            return array('success' => false, 'message' => 'Integration disabled');
        }

        $credentials = self::get_credentials();

        // Validate credentials
        if (empty($credentials['clientId']) || empty($credentials['sha']) || empty($credentials['apiKey']) || empty($credentials['owner'])) {
            Loyalty_Program_Logger::error('SalesManago credentials incomplete');
            return array('success' => false, 'message' => 'Credentials incomplete');
        }

        // Prepare request data
        $request_data = array(
            'clientId' => $credentials['clientId'],
            'requestTime' => time() * 1000,
            'sha' => $credentials['sha'],
            'apiKey' => $credentials['apiKey'],
            'owner' => $credentials['owner'],
            'contact' => array(
                'email' => $email,
            ),
        );

        // Newsletter consent (optedOut)
        if (isset($consents['newsletter'])) {
            if ($consents['newsletter']) {
                $request_data['forceOptIn'] = true;
                $request_data['forceOptOut'] = false;
            } else {
                $request_data['forceOptIn'] = false;
                $request_data['forceOptOut'] = true;
            }
        }

        // SMS consent (optedOutPhone)
        if (isset($consents['sms'])) {
            if ($consents['sms']) {
                $request_data['forcePhoneOptIn'] = true;
                $request_data['forcePhoneOptOut'] = false;
            } else {
                $request_data['forcePhoneOptIn'] = false;
                $request_data['forcePhoneOptOut'] = true;
            }
        }

        // Add contact basic fields
        $basic_fields = array('name', 'lastName', 'phone');
        foreach ($basic_fields as $field) {
            if (!empty($contact_data[$field])) {
                $request_data['contact'][$field] = $contact_data[$field];
            }
        }

        // Add birthday - format: yyyyMMdd (e.g., "19900515")
        if (!empty($contact_data['birthday'])) {
            $birthday = $contact_data['birthday'];
            // Convert from Y-m-d to yyyyMMdd
            if (strpos($birthday, '-') !== false) {
                $birthday = str_replace('-', '', $birthday);
            }
            $request_data['birthday'] = $birthday;

            Loyalty_Program_Logger::debug('Birthday formatted for SalesManago', array(
                'original' => $contact_data['birthday'],
                'formatted' => $birthday,
            ));
        }

        // Add address (without province - it goes to main level)
        $address_fields = array('streetAddress', 'zipCode', 'city', 'country');
        $address = array();
        foreach ($address_fields as $field) {
            if (!empty($contact_data[$field])) {
                $address[$field] = $contact_data[$field];
            }
        }
        if (!empty($address)) {
            $request_data['contact']['address'] = $address;
        }

        // Province goes to main level (not in address)
        if (!empty($contact_data['province'])) {
            $request_data['province'] = $contact_data['province'];
        }

        // Add tags
        if ($tags !== null) {
            if (!is_array($tags)) {
                $tags = array($tags);
            }
            $request_data['tags'] = $tags;
        }

        // Create clean request for logging (without sensitive data)
        $log_request = $request_data;
        unset($log_request['apiKey'], $log_request['sha']);

        Loyalty_Program_Logger::info('📤 Wysyłanie kontaktu do SalesManago', array(
            'email' => $email,
            'has_name' => !empty($contact_data['name']),
            'has_lastName' => !empty($contact_data['lastName']),
            'has_phone' => !empty($contact_data['phone']),
            'has_birthday' => !empty($request_data['birthday']),
            'has_address' => !empty($address),
            'has_province' => !empty($request_data['province']),
            'tags' => $tags,
            'newsletter_consent' => isset($consents['newsletter']) ? ($consents['newsletter'] ? 'OPT-IN' : 'OPT-OUT') : 'not set',
            'sms_consent' => isset($consents['sms']) ? ($consents['sms'] ? 'OPT-IN' : 'OPT-OUT') : 'not set',
        ));

        Loyalty_Program_Logger::debug('📋 Pełny request JSON (bez sensitive data)', array(
            'request_json' => json_encode($log_request, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ));

        // Send request
        $response = wp_remote_post('https://www.salesmanago.pl/api/contact/upsert', array(
            'timeout' => 15,
            'headers' => array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($request_data),
        ));

        if (is_wp_error($response)) {
            Loyalty_Program_Logger::error('SalesManago API request failed', array(
                'error' => $response->get_error_message(),
            ));
            return array('success' => false, 'message' => $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);

        Loyalty_Program_Logger::debug('📥 SalesManago API response', array(
            'code' => $response_code,
            'body' => $response_body,
        ));

        if ($response_code === 200 && isset($response_data['success']) && $response_data['success'] === true) {
            $contact_id = isset($response_data['contactId']) ? $response_data['contactId'] : null;

            Loyalty_Program_Logger::info('✅ Kontakt zapisany w SalesManago', array(
                'email' => $email,
                'contact_id' => $contact_id,
            ));

            return array(
                'success' => true,
                'contactId' => $contact_id,
                'message' => 'Contact upserted successfully',
            );
        } else {
            $error_message = isset($response_data['message']) ? $response_data['message'] : 'Unknown error';
            if (is_array($error_message)) {
                $error_message = implode(', ', $error_message);
            }

            Loyalty_Program_Logger::error('❌ SalesManago upsert failed', array(
                'email' => $email,
                'error' => $error_message,
                'response_code' => $response_code,
                'response_data' => $response_data,
            ));

            return array(
                'success' => false,
                'message' => $error_message,
            );
        }
    }

    /**
     * Check if contact exists in SalesManago
     * 
     * @param string $email Contact email
     * @return array|false Array with 'exists' and 'contactId' or false on error
     */
    public static function has_contact($email)
    {
        // Load logger
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        // Check if enabled
        if (!self::is_enabled()) {
            Loyalty_Program_Logger::warning('SalesManago integration is disabled');
            return false;
        }

        $credentials = self::get_credentials();

        // Validate credentials
        if (empty($credentials['clientId']) || empty($credentials['sha']) || empty($credentials['apiKey']) || empty($credentials['owner'])) {
            Loyalty_Program_Logger::error('SalesManago credentials incomplete');
            return false;
        }

        // Prepare request data
        $request_data = array(
            'clientId' => $credentials['clientId'],
            'requestTime' => time() * 1000,
            'sha' => $credentials['sha'],
            'apiKey' => $credentials['apiKey'],
            'email' => $email,
            'owner' => $credentials['owner'],
        );

        Loyalty_Program_Logger::info('Checking if contact exists in SalesManago', array(
            'email' => $email,
            'endpoint' => 'contact/hasContact',
        ));

        // Send request - use contact/hasContact endpoint
        $response = wp_remote_post('https://www.salesmanago.pl/api/contact/hasContact', array(
            'timeout' => 15,
            'headers' => array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($request_data),
        ));

        if (is_wp_error($response)) {
            Loyalty_Program_Logger::error('SalesManago API request failed', array(
                'error' => $response->get_error_message(),
            ));
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);

        Loyalty_Program_Logger::debug('SalesManago hasContact response', array(
            'code' => $response_code,
            'body' => $response_body,
            'success' => isset($response_data['success']) ? $response_data['success'] : null,
            'result' => isset($response_data['result']) ? $response_data['result'] : null,
        ));

        if ($response_code === 200 && isset($response_data['success']) && $response_data['success'] === true) {
            $exists = isset($response_data['result']) && $response_data['result'] === true;
            $contactId = isset($response_data['contactId']) ? $response_data['contactId'] : null;

            Loyalty_Program_Logger::info('Contact check completed', array(
                'email' => $email,
                'exists' => $exists,
                'contact_id' => $contactId,
            ));

            return array(
                'exists' => $exists,
                'contactId' => $contactId,
            );
        } else {
            $error_message = isset($response_data['message']) ? $response_data['message'] : 'Unknown error';

            Loyalty_Program_Logger::error('SalesManago hasContact failed', array(
                'email' => $email,
                'error' => $error_message,
                'response_code' => $response_code,
            ));

            return false;
        }
    }

    /**
     * Get contact data from SalesManago
     * 
     * @param string $email Contact email
     * @return array|false Contact data or false on failure
     */
    public static function get_contact($email)
    {
        // Load logger
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        // Check if enabled
        if (!self::is_enabled()) {
            Loyalty_Program_Logger::warning('SalesManago integration is disabled');
            return false;
        }

        $credentials = self::get_credentials();

        // Validate credentials
        if (empty($credentials['clientId']) || empty($credentials['sha']) || empty($credentials['apiKey']) || empty($credentials['owner'])) {
            Loyalty_Program_Logger::error('SalesManago credentials incomplete');
            return false;
        }

        // Use contact/basic endpoint (most reliable)
        $request_data = array(
            'clientId' => $credentials['clientId'],
            'requestTime' => time() * 1000,
            'sha' => $credentials['sha'],
            'apiKey' => $credentials['apiKey'],
            'owner' => $credentials['owner'],
            'email' => array($email), // Array of emails
        );

        Loyalty_Program_Logger::info('Getting contact data from SalesManago', array(
            'email' => $email,
            'endpoint' => 'contact/basic',
        ));

        // Send request - use contact/basic endpoint
        $response = wp_remote_post('https://www.salesmanago.pl/api/contact/basic', array(
            'timeout' => 15,
            'headers' => array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($request_data),
        ));

        if (is_wp_error($response)) {
            Loyalty_Program_Logger::error('SalesManago API request failed', array(
                'error' => $response->get_error_message(),
            ));
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);

        Loyalty_Program_Logger::debug('SalesManago get contact response', array(
            'code' => $response_code,
            'body' => substr($response_body, 0, 1000), // Log first 1000 chars
            'success' => isset($response_data['success']) ? $response_data['success'] : null,
        ));

        if ($response_code === 200 && isset($response_data['success']) && $response_data['success'] === true) {
            // contact/basic returns array of contacts
            $contacts = isset($response_data['contacts']) ? $response_data['contacts'] : array();

            // Get first contact (we only requested one)
            $contact = !empty($contacts) ? $contacts[0] : null;

            if ($contact) {
                // Add debug info about what data is available
                $contact['_debug'] = array(
                    'has_name' => !empty($contact['name']),
                    'has_lastName' => !empty($contact['lastName']),
                    'has_phone' => !empty($contact['phone']),
                    'has_address' => isset($contact['address']) && $contact['address'] !== null,
                    'has_company' => !empty($contact['company']),
                    'has_birthday' => !empty($contact['birthdayYear']) || !empty($contact['birthday']),
                    'has_tags' => isset($contact['tags']) && !empty($contact['tags']),
                    'has_agreement1' => isset($contact['agreement1']),
                    'has_agreement2' => isset($contact['agreement2']),
                    'optedOut' => isset($contact['optedOut']) ? $contact['optedOut'] : null,
                    'optedOutPhone' => isset($contact['optedOutPhone']) ? $contact['optedOutPhone'] : null,
                    'state' => isset($contact['state']) ? $contact['state'] : null,
                    'available_fields' => array_keys($contact),
                );

                Loyalty_Program_Logger::info('Contact data retrieved successfully', array(
                    'email' => $email,
                    'contact_id' => isset($contact['contactId']) ? $contact['contactId'] : 'N/A',
                    'debug_info' => $contact['_debug'],
                ));

                return $contact;
            } else {
                Loyalty_Program_Logger::warning('Contact not found in SalesManago', array(
                    'email' => $email,
                ));

                return false;
            }
        } else {
            $error_message = isset($response_data['message']) ? $response_data['message'] : 'Unknown error';

            Loyalty_Program_Logger::error('SalesManago get contact failed', array(
                'email' => $email,
                'error' => $error_message,
                'response_code' => $response_code,
            ));

            return false;
        }
    }

    /**
     * Send event to SalesManago
     * 
     * @param string $email User email
     * @param string $event_name Event name
     * @param array $event_data Event data (optional)
     * @return array Response with 'success' and 'message'
     */
    public static function send_event($email, $event_name, $event_data = array())
    {
        // Load logger
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        // Check if enabled
        if (!self::is_enabled()) {
            return array('success' => false, 'message' => 'Integration disabled');
        }

        $credentials = self::get_credentials();

        // Validate credentials
        if (empty($credentials['clientId']) || empty($credentials['sha']) || empty($credentials['apiKey']) || empty($credentials['owner'])) {
            return array('success' => false, 'message' => 'Credentials incomplete');
        }

        // Prepare request data
        $request_data = array(
            'clientId' => $credentials['clientId'],
            'requestTime' => time() * 1000,
            'sha' => $credentials['sha'],
            'apiKey' => $credentials['apiKey'],
            'owner' => $credentials['owner'],
            'email' => $email,
            'contactEvent' => array(
                'date' => time() * 1000,
                'description' => $event_name,
                'detail' => $event_data,
            ),
        );

        Loyalty_Program_Logger::info('Sending event to SalesManago', array(
            'email' => $email,
            'event_name' => $event_name,
        ));

        // Send request
        $response = wp_remote_post('https://www.salesmanago.pl/api/contact/event', array(
            'timeout' => 15,
            'headers' => array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($request_data),
        ));

        if (is_wp_error($response)) {
            Loyalty_Program_Logger::error('SalesManago event request failed', array(
                'error' => $response->get_error_message(),
            ));
            return array('success' => false, 'message' => $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);

        if ($response_code === 200 && isset($response_data['success']) && $response_data['success'] === true) {
            Loyalty_Program_Logger::info('Event sent successfully', array(
                'email' => $email,
                'event_name' => $event_name,
            ));

            return array('success' => true, 'message' => 'Event sent successfully');
        } else {
            $error_message = isset($response_data['message']) ? $response_data['message'] : 'Unknown error';

            Loyalty_Program_Logger::error('SalesManago event failed', array(
                'email' => $email,
                'event_name' => $event_name,
                'error' => $error_message,
            ));

            return array('success' => false, 'message' => $error_message);
        }
    }

    /**
     * Sync user consents to SalesManago
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public static function sync_user_consents($user_id)
    {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        $sms_consent = get_user_meta($user_id, 'loyalty_program_sms_consent', true) === 'yes';
        $newsletter_consent = get_user_meta($user_id, 'loyalty_program_newsletter_consent', true) === 'yes';

        $tags = array();
        if ($sms_consent) {
            $tags[] = 'loyalty_sms_consent';
        }
        if ($newsletter_consent) {
            $tags[] = 'loyalty_newsletter_consent';
        }

        $result = self::upsert_contact(
            $user->user_email,
            $user->display_name,
            $tags,
            get_user_meta($user_id, 'billing_phone', true)
        );

        return $result['success'];
    }

    /**
     * Send survey completion event to SalesManago
     * 
     * @param int $user_id User ID
     * @param string $survey_name Survey name
     * @param int $points_earned Points earned
     * @param int $score Score (for quizzes)
     * @return bool
     */
    public static function send_survey_completion($user_id, $survey_name, $points_earned = 0, $score = null)
    {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        $event_data = array(
            'survey_name' => $survey_name,
            'points_earned' => $points_earned,
        );

        if ($score !== null) {
            $event_data['score'] = $score;
        }

        $result = self::send_event(
            $user->user_email,
            'Loyalty Program - Survey Completed',
            $event_data
        );

        return $result['success'];
    }
}
