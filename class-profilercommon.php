<?php

class GFProfilerCommon extends GFFeedAddOn {
    protected $_path = "profiler-donation-gf/index.php";
    protected $_full_path = __FILE__;
    protected $_url = "";
    protected $_title = "Profiler / Gravity Forms - Integration Feed";

    protected $api_type = "form"; // form or json
    protected $api_domain = "profilersystem.com";
    protected $apifield_apikey = "apikey";
    protected $apifield_apipass = "apipass";
    protected $apifield_endpoint = "";
    protected $apifield_ipaddress = false;
    protected $apifield_formurl = false;
    protected $gffield_legacyname = "";
    protected $supports_custom_fields = false;
    protected $supports_mailinglists = false;

    protected $_capabilities_form_settings = 'gravityforms_edit_settings';

    public function init() {
        parent::init();

        // Stripe - force Customer creation
        add_filter('gform_stripe_customer_id',              array($this, 'stripe_customer_id'), 10, 4);
        add_action('gform_stripe_customer_after_create',    array($this, 'stripe_customer_id_save'), 10, 4);
        add_filter('gform_stripe_charge_pre_create',        array($this, 'stripe_payment_intent'), 10, 5);
        add_filter('gform_stripe_charge_description',       array($this, 'stripe_payment_description'), 10, 5);
        add_filter('gform_stripe_payment_element_initial_payment_information', array($this, 'stripe_elements_setup'), 10, 3);

        // Stripe - allow immediate refund upon payment success.
        // Designed for Card Update workflows - preauth would be better, but the GF Stripe add-on has issues with this
        add_filter('gform_form_settings_fields',                        array($this, 'stripe_refund_form_setting'), 10, 2);
        add_action('gform_after_submission',                            array($this, 'stripe_refund_after_submission'), 10, 2);

        // Workaround for change/bug introduced in v2.9.1
        // See https://community.gravityforms.com/t/gf-2-9-stripe-transaction-id-in-gffeedaddon-empty/18770
        remove_filter('gform_entry_post_save', array($this, 'maybe_process_feed'), 10);
        add_filter('gform_entry_post_save', array($this, 'maybe_process_feed'), 12, 2);

        // Metabox for Profiler Logs
        add_filter('gform_entry_detail_meta_boxes',         array($this, 'meta_box_entry'), 10, 3);
    }

    public function get_menu_icon() {
		//return 'dashicons-admin-generic';
        return plugins_url('icon-profiler.png', __FILE__);
	}

    public function feed_settings_fields() {
        // This function adds all the feed setting fields we need to communicate with Profiler
        
        $feed = $this->get_current_feed();

        // Get lists of the various types of fields
        $field_settings = $this->formFields();
        $hiddenFields = $this->hiddenFields();
        $checkboxRadioFields = $this->checkboxRadioFields();
        $checkboxFields = $this->checkboxFields();
        $userdefinedfields = $this->userDefinedFields();

        $numbers = array();
        for($i = 0; $i <= 99; $i++) {
            $numbers[] = array(
                "value" => $i,
                "label" => $i
            );
        }
        
        // All the fields to add to the feed:
        $fields = array();
        
        $fields[] = array(
            "label" => "Feed Name",
            "type" => "text",
            "name" => "feedName",
            "required" => true,
            "tooltip" => 'Enter a feed name to uniquely identify this setup'
        );
        
        $fields[] = array(
            "label" => 'Profiler Instance Domain Name',
            "type" => "text",
            "name" => "profiler".$this->gffield_legacyname."_instancedomainname",
            "required" => true,
            "tooltip" => "Your Instance Domain Name can be found in your login URL: e.g. 'https://instance.profilersystem.com/' is 'instance.profilesystem.com'",
        );
        
        if($this->api_type !== 'json') {
            $fields[] = array(
                "label" => 'Profiler Database Name',
                "type" => "text",
                "name" => "profiler".$this->gffield_legacyname."_dbname",
                "required" => true,
            );
        }
        
        $fields[] = array(
            "label" => 'Profiler API Key',
            "type" => "text",
            "name" => "profiler".$this->gffield_legacyname."_apikey",
            "required" => true,
        );
        
        $fields[] = array(
            "label" => 'Profiler API Password',
            "type" => "text",
            "name" => "profiler".$this->gffield_legacyname."_apipass",
            "required" => true,
        );

        $fields[] = array(
            "label" => 'Profiler Errors Email Address',
            "type" => "text",
            "name" => "profiler".$this->gffield_legacyname."_erroremailaddress",
            "required" => false,
        );

        // Add in all the fields required by the child feed class
        $fields = array_merge($fields, $this->feed_settings_fields_custom());

        if($this->apifield_ipaddress == 'udf') {
            // Client's IP Address - UDF Field
            $fields[] = array(
                "label" => 'UDF: Client IP Address',
                "type" => "select",
                "name" => "profiler".$this->gffield_legacyname."_userdefined_clientip",
                "required" => false,
                "tooltip" => "Pick the Profiler User Defined Field you wish the client's IP address to be sent to",
                "choices" => $userdefinedfields,
            );
        }

        if($this->apifield_formurl === true) {
            $fields[] = array(
                "label" => 'UDF: Form URL',
                "type" => "select",
                "name" => "profiler".$this->gffield_legacyname."_userdefined_formurl",
                "required" => false,
                "tooltip" => "Pick the Profiler User Defined Field you wish the form's URL to be sent to.",
                "choices" => $userdefinedfields,
            );
        }

        // Mailing list support
        if($this->supports_mailinglists === true) {
            $fields[] = array(
                "label" => 'Number of Mailing Lists',
                "type" => "select",
                "name" => "profiler".$this->gffield_legacyname."_mailinglist_count",
                "required" => false,
                "tooltip" => "Select a quantity of Mailing Lists, save this page, and then configure them. You will need to refresh this page after saving to see the extra fields.",
                "choices" => $numbers,
                "default" => 0,
            );

            if(isset($feed['meta']['profiler'.$this->gffield_legacyname.'_mailinglist_count']) && is_numeric($feed['meta']['profiler'.$this->gffield_legacyname.'_mailinglist_count'])) {
                for($i = 1; $i <= $feed['meta']['profiler'.$this->gffield_legacyname.'_mailinglist_count']; $i++) {
                    // Loop over mailing list fields
    
                    if($this->api_type !== 'json') {
                        $fields[] = array(
                            "label" => 'Mailing List #'.$i.': UDF',
                            "type" => "select",
                            "name" => "profiler".$this->gffield_legacyname."_mailinglist_".$i."_udf",
                            "required" => false,
                            "tooltip" => "Pick the Profiler User Defined Field you wish to use for this mailing",
                            "choices" => $userdefinedfields,
                        );
                    }

                    $fields[] = array(
                        "label" => ($this->api_type == 'json' ? 'Mailing List #'.$i.': Mailing Type Code' : 'Mailing List #'.$i.': UDF Text'),
                        "type" => "text",
                        "name" => "profiler".$this->gffield_legacyname."_mailinglist_".$i."_udftext",
                        "required" => false,
                        "tooltip" => ($this->api_type == 'json' ? "Enter the Profiler Mailing Type Code" : "Enter the string Profiler is expecting in this UDF") . ". This field also accept post meta merge fields in this format: {postmeta:meta_field_name}",
                    );

                    $fields[] = array(
                        "label" => 'Mailing List #'.$i.': Field',
                        "type" => "select",
                        "name" => "profiler".$this->gffield_legacyname."_mailinglist_".$i."_field",
                        "tooltip" => 'Link it to a checkbox field - when checked, the mailing will be sent',
                        "required" => false,
                        "choices" => array_merge($checkboxRadioFields, array(array("value" => "always", "label" => "Always Subscribe"))),
                    );
                }
            }
        }

        if($this->supports_custom_fields === true) {
            $fields[] = array(
                "label" => 'Number of Custom Fields',
                "type" => "select",
                "name" => "profiler_customfields_count",
                "required" => false,
                "tooltip" => "How many custom fields do you want to send back to Profiler? You will need to refresh this page after saving to see the extra fields.",
                "choices" => $numbers,
            );

            if(isset($feed['meta']['profiler_customfields_count']) && is_numeric($feed['meta']['profiler_customfields_count'])) {
                for($i = 1; $i <= $feed['meta']['profiler_customfields_count']; $i++) {
                    // Loop over custom fields
    
                    $fields[] = array(
                        "label" => 'Custom Field #'.$i.': UDF',
                        "type" => "select",
                        "name" => "profiler_customfield_".$i."_pffield",
                        "required" => false,
                        "tooltip" => "Pick the UDF field in Profiler you wish to use",
                        "choices" => $userdefinedfields,
                    );
    
                    $fields[] = array(
                        "label" => 'Custom Field #'.$i.': Gravity Forms Field',
                        "type" => "select",
                        "name" => "profiler_customfield_".$i."_gffield",
                        "required" => false,
                        "tooltip" => "Pick the field in Gravity Forms you wish to use",
                        "choices" => $field_settings,
                    );
                }
            }
        }

        $fields[] = array(
            "label" => 'SSL Mode',
            "type" => "select",
            "name" => "profiler".$this->gffield_legacyname."_sslmode",
            "required" => false,
            "choices" => array(
                array(
                    "value" => "normal",
                    "label" => "Normal"
                ),
                array(
                    "value" => "bundled_ca",
                    "label" => "Use Plugin Bundled CA Certs"
                ),
                array(
                    "value" => "dontverifypeer",
                    "label" => "Don't Verify SSL Peers (Super dangerous. Don't use this!!)"
                )
            ),
            "tooltip" => "Only change this if there is a legitimate technical reasons for doing so. This will cause insecurities. Use with caution."
        );

        $fields[] = array(
            'type'           => 'feed_condition',
            'name'           => 'feed_condition',
            'label'          => 'Feed Condition',
            'checkbox_label' => 'Enable Conditional Logic for this Feed',
            'instructions'   => 'This Feed will only be processed if the condition(s) specified here are met.'
        );

        return array(
            array(
                "title" => "Profiler Integration Settings",
                "fields" => $fields
            )
        );
        
    }

    public function process_feed($feed, $entry, $form, $fromValidatorProcessPFGateway = false) {
        // Processes the feed and prepares to send it to Profiler

        $form_id = $form["id"];
        $settings = $this->get_form_settings($form);
        
        // All the POST data for Profiler gets stored in this variable
        $postData = array();

        if($this->api_type !== 'json') {
            $postData['DB'] = $feed['meta']['profiler'.$this->gffield_legacyname.'_dbname'];
        }
        $postData[$this->apifield_apikey] = $feed['meta']['profiler'.$this->gffield_legacyname.'_apikey'];
        $postData[$this->apifield_apipass] = $feed['meta']['profiler'.$this->gffield_legacyname.'_apipass'];

        if(empty($feed['meta']['profiler'.$this->gffield_legacyname.'_instancedomainname']) && !empty($feed['meta']['profiler'.$this->gffield_legacyname.'_instancename'])) {
            // Respect the setting from when we only accepted the first part of the domain name
            $feed['meta']['profiler'.$this->gffield_legacyname.'_instancedomainname'] = $feed['meta']['profiler'.$this->gffield_legacyname.'_instancename'] . ".profilersystem.com";
        }

        if(empty($feed['meta']['profiler'.$this->gffield_legacyname.'_instancedomainname']) && !empty($feed['meta']['profiler'.$this->gffield_legacyname.'_serveraddress'])) {
            $parse = parse_url($feed['meta']['profiler'.$this->gffield_legacyname.'_serveraddress']);
            $feed['meta']['profiler'.$this->gffield_legacyname.'_instancedomainname'] = $parse['host'];
        }

        // Build the URL for this API call
        $API_URL = "https://" . $feed['meta']['profiler'.$this->gffield_legacyname.'_instancedomainname'] . $this->apifield_endpoint;

        // Work out GF/API field mappings
        $fields = $this->feed_settings_fields()[0]['fields'];

        foreach($fields as $field) {
            $field_value = $this->get_field_value($form, $entry, $feed['meta'][$field['name']]);
            if(isset($field['pf_apifield']) && !empty($field['pf_apifield']) && !empty($field_value)) {

                $postData[$field['pf_apifield']] = trim($field_value);

                $field_object = GFFormsModel::get_field($form, $feed['meta'][$field['name']]);

				if(is_object($field_object)) {
                    if($field_object->type == 'product' && ($field_object->get_input_type() == 'radio' || $field_object->get_input_type() == 'select')) {
                        // By default these fields return 'Value ($ 1.00)', but we only want 'Value'
                        if(strpos($field_value, "(") !== false) {
                            $postData[$field['pf_apifield']] = trim(substr($field_value, 0, strrpos($field_value, "(") - 1));
                        }
                    }
                }

                // Auto formatting for phone fields
                if(isset($field['auto_format']) && $field['auto_format'] == "phone") {
                    $postData[$field['pf_apifield']] = $this->auto_format_phone($postData[$field['pf_apifield']]);
                }
            }
        }

        if(isset($postData['country'])) {
            $postData['country'] = $this->get_country_name($postData['country']);
        }

        if($this->apifield_ipaddress != false && $this->apifield_ipaddress != 'udf') {
            // Client's IP Address - fixed field name
            $postData[$this->apifield_ipaddress] = $this->get_client_ip_address();

        } else if($this->apifield_ipaddress != false && $this->apifield_ipaddress == 'udf') {
            // Client's IP Address - UDF field
            $postData['userdefined' . $feed['meta']['profiler'.$this->gffield_legacyname.'_userdefined_clientip']] = $this->get_client_ip_address();

        }

        if($this->apifield_formurl === true && !empty($feed['meta']['profiler'.$this->gffield_legacyname.'_userdefined_formurl'])) {
            $postData['userdefined' . $feed['meta']['profiler'.$this->gffield_legacyname.'_userdefined_formurl']] = $entry['source_url'];
        } else if($this->apifield_formurl !== false) {
            $postData[$this->apifield_formurl] = $entry['source_url'];
        }

        if(isset($entry['transaction_id']) && substr($entry['transaction_id'], 0, 3) == "pi_") {
            // Stripe Payment - find the Customer ID and Card ID, and pass it to the PF API

            try {
                if(!class_exists('\Stripe\Stripe')) {
                    require_once(plugin_dir_path(__DIR__) . 'gravityformsstripe/includes/autoload.php');
                }

                // Set Stripe API key.
                $stripe_options = get_option('gravityformsaddon_gravityformsstripe_settings');
                \Stripe\Stripe::setApiKey( $stripe_options[$stripe_options['api_mode'] . '_secret_key'] );
                
                // Get the Payment Intent
                $payment_intent = \Stripe\PaymentIntent::retrieve($entry['transaction_id']);

            } catch(Exception $e) {
                error_log("STRIPE/PROFILER SETUP ERROR: " . print_r($e, true));
            }

            if(isset($payment_intent)) {
                // Gateway Customer ID
                $postData['paymentGatewayCustomerId'] = $payment_intent->customer;
            }

            if(isset($payment_intent)) {
                // Gateway Card Token
                try {
                    $postData['gatewayCardToken'] = $payment_intent->charges->data[0]->payment_method;
                } catch(Exception $e) {
                    error_log("STRIPE/PROFILER CARD TOKEN ERROR: " . print_r($e, true));
                }
            }
        }

        // PayFURL-supplied Gateway Token
        if(isset($_POST['payfurl_payment_details']['captured_payment']['payfurl_payment_method_id_provider'])) {
            $payfurl_provider_token = $_POST['payfurl_payment_details']['captured_payment']['payfurl_payment_method_id_provider'];

            if(!empty($payfurl_provider_token)) {
                $postData['gatewayCardToken'] = $payfurl_provider_token;
            }
        }

        // Custom Fields
        if($this->supports_custom_fields === true && !empty($feed['meta']['profiler_customfields_count'])) {
            for($i = 1; $i <= $feed['meta']['profiler_customfields_count']; $i++) {

                $value = trim($this->get_field_value($form, $entry, $feed['meta']["profiler_customfield_".$i."_gffield"]));

                if($this->api_type == 'json') {
                    if(!isset($postData['userDefinedFields'])) {
                        $postData['userDefinedFields'] = array();
                    }

                    $postData['userDefinedFields']["userDefined" . $feed['meta']["profiler_customfield_".$i."_pffield"]] = $value;

                } else {
                    $postData["userdefined" . $feed['meta']["profiler_customfield_".$i."_pffield"]] = $value;
                }

                
            }
        }

        // Calculate mailing list subscriptions
        if($this->supports_mailinglists === true && isset($feed['meta']['profiler'.$this->gffield_legacyname.'_mailinglist_count']) && is_numeric($feed['meta']['profiler'.$this->gffield_legacyname.'_mailinglist_count'])) {
            for($i = 1; $i <= $feed['meta']['profiler'.$this->gffield_legacyname.'_mailinglist_count']; $i++) {
                // Loop over mailing list fields
                $mailingFieldValue = $this->get_field_value($form, $entry, $feed['meta']["profiler".$this->gffield_legacyname."_mailinglist_".$i."_field"]);
                $udfText = $feed['meta']["profiler".$this->gffield_legacyname."_mailinglist_".$i."_udftext"];

                // Allow merging post meta fields into $udfText
                if(isset($entry['post_id']) && !empty($entry['post_id'])) {
                    $post_id = $entry['post_id'];
                } else {
                    global $post;
                    if(isset($post)) {
                        $post_id = $post->ID;
                    }
                }

                if(isset($post_id)) {
                    preg_match_all("/{postmeta:(.*?)}/", $udfText, $matches);

                    if(is_array($matches) && isset($matches[1])) {
                        foreach($matches[1] as $match) {
                            $post_meta = get_post_meta($post_id, $match, true);
                            $udfText = str_replace("{postmeta:".$match."}", $post_meta, $udfText);
                        }
                    }
                }

                if($this->api_type == 'json') {

                    if(!isset($postData['mailingList'])) {
                        $postData['mailingList'] = '';
                    }

                    if(!empty($udfText) && (!empty($mailingFieldValue) || $feed['meta']["profiler".$this->gffield_legacyname."_mailinglist_".$i."_field"] == 'always')) {
                        $postData['mailingList'] .= (!empty($postData['mailingList']) ? ',' : '') . $udfText;
                    }

                } else {
                    $udf = $feed['meta']["profiler".$this->gffield_legacyname."_mailinglist_".$i."_udf"];

                    if(!empty($udf) && !empty($udfText) && (!empty($mailingFieldValue) || $feed['meta']["profiler".$this->gffield_legacyname."_mailinglist_".$i."_field"] == 'always')) {
                        $postData['userdefined' . $udf] = $udfText;
                    }
                }
            }
        }

        // Allow filtering this via the child class
        if(method_exists($this, 'process_feed_custom')) {
            $postData = $this->process_feed_custom($feed, $entry, $form, $postData, $fromValidatorProcessPFGateway);

            if($postData === false) {
                return false;
            }

        }

        if(isset($postData['apiurl_override'])) {
            $API_URL = $postData['apiurl_override'];
        }

        // Allow filtering the Profiler request
        $postData = apply_filters('profiler_integration_api_request_data', $postData, $form, $entry, $this->apifield_endpoint);

        // Update URL for newer APIs
        if($this->api_domain !== 'profilersystem.com' && !isset($postData['apiurl_override'])) {
            $API_URL = str_replace(".profilersystem.com", "." . $this->api_domain, $API_URL);
        }

        // Send data to Profiler
        $pfResponse = $this->sendDataToProfiler($API_URL, $postData, $feed['meta']['profiler'.$this->gffield_legacyname.'_sslmode']);

        if($fromValidatorProcessPFGateway === false) {
            // Save Profiler response data back to the form entry
            $logsToStore = json_encode($pfResponse, JSON_PRETTY_PRINT);
            $logsToStore = str_replace($postData['cardnumber'], "--REDACTED--", $logsToStore);
            $logsToStore = str_replace($postData[$this->apifield_apikey], "--REDACTED--", $logsToStore);
            $logsToStore = str_replace($postData[$this->apifield_apipass], "--REDACTED--", $logsToStore);
            gform_add_meta($entry["id"], "profiler_logs", $logsToStore, $form['id']);

            if(method_exists($this, 'process_feed_success')) {
                $this->process_feed_success($feed, $entry, $form, $pfResponse, $postData);
            }

        } else {
            return $pfResponse;
        }
    }

    public function feed_list_columns() {
        // Returns columns to feed index page
        return array(
            'feedName'  => 'Name',
            'profiler_dbname' => 'PF Database Name',
        );
    }
    
    protected function formFields($preface = "") {
        // Returns an array of all fields on this form
        
        $form = $this->get_current_form();

        if(!is_array($form) || !isset($form['fields'])) {
            return array();
        }

        $fields = $form['fields'];
        
        // An array holding all the fields on the form - will be returned
        $formfields = array(
            array(
                "value" => "",
                "label" => ""
            )
        );
        
        foreach ($fields as $key => $field) {
            if ($field["type"] == 'address' || ($field["type"] == 'name' && (!isset($field["nameFormat"]) || $field["nameFormat"] != 'simple'))) {
                // Address and name are handled specially
                foreach ($field['inputs'] as $keyvalue => $inputvalue) {
                    $field_settings = array();
                    $field_settings['value'] = $inputvalue['id'];
                    $field_settings['label'] = $preface . (!empty($field['label']) ? ' ' . $field['label'] . ': ' : '') . $inputvalue['label'];
                    $formfields[] = $field_settings;
                }
            } elseif ($field['type'] == 'hidden') {
                $formfields[] = array(
                    "value" => $field['id'],
                    "label" => (!empty($field['label']) ? $field['label'] : 'Hidden Field #' . $field['id'])
                );
            } elseif ($field['type'] == 'select') {
                $formfields[] = array(
                    "value" => $field['id'],
                    "label" => (!empty($field['label']) ? $field['label'] : 'Select Field #' . $field['id'])
                );
            } elseif($field["type"] != "creditcard") {
                // Process all fields except credit cards - we don't want them in the list
                $field_settings = array();
                $field_settings['value'] = $field['id'];
                $field_settings['label'] = $preface . $field['label'];
                $formfields[] = $field_settings;
            }
        }
        
        return $formfields;
    }
    
    
    protected function hiddenFields() {
        // Returns an array of hidden fields
        
        $form = $this->get_current_form();

        if(!is_array($form) || !isset($form['fields'])) {
            return array();
        }

        $fields = $form['fields'];
        
        // An array holding all the hidden fields on the form - will be returned
        $formfields = array(
            array(
                "value" => "",
                "label" => ""
            )
        );
        
        foreach ($fields as $key => $field) {
            if ($field['type'] == 'hidden') {
                $formfields[] = array(
                    "value" => $field['id'],
                    "label" => (!empty($field['label']) ? $field['label'] : 'Hidden Field #' . $field['id'])
                );
            }
        }
        
        return $formfields;
    }

    protected function userDefinedFields() {
        
        $fields = array(array(
                "value" => "",
                "label" => "None",
            ));
        
        for($i = 1; $i <= 99; $i++) {
            $fields[] = array(
                "value" => $i,
                "label" => "User Defined Field " . $i,
            );
        }
        
        return $fields;
        
    }
    
    
    protected function checkboxRadioFields() {
        // Returns an array of checkbox and radio fields
        
        $form = $this->get_current_form();

        if(!is_array($form) || !isset($form['fields'])) {
            return array();
        }

        $fields = $form['fields'];
        
        // An array holding all the hidden fields on the form - will be returned
        $formfields = array(
            array(
                "value" => "",
                "label" => ""
            )
        );
        
        foreach ($fields as $key => $field) {
            if ($field['type'] == 'checkbox' || $field['type'] == 'radio') {
                foreach($field['inputs'] as $input) {
                    $formfields[] = array(
                        "value" => $input['id'],
                        "label" => "Field #" . $input['id'] . " - " . $field['label'] . " / " . $input['label']
                    );
                }
            }
        }

        return $formfields;
    }

    protected function checkboxFields() {
        // Returns an array of checkbox fields

        $form = $this->get_current_form();

        if(!is_array($form) || !isset($form['fields'])) {
            return array();
        }

        $fields = $form['fields'];

        // An array holding all the hidden fields on the form - will be returned
        $formfields = array(
            array(
                "value" => "",
                "label" => ""
            )
        );

        foreach ($fields as $key => $field) {
            if ($field['type'] == 'checkbox') {
                foreach($field['inputs'] as $input) {
                    $formfields[] = array(
                        "value" => $input['id'],
                        "label" => "Field #" . $input['id'] . " - " . $field['label'] . " / " . $input['label']
                    );
                }
            }
        }

        return $formfields;
    }

    protected function selectFields() {
        // Returns an array of checkbox and radio fields
        
        $form = $this->get_current_form();

        if(!is_array($form) || !isset($form['fields'])) {
            return array();
        }

        $fields = $form['fields'];
        
        // An array holding all the hidden fields on the form - will be returned
        $formfields = array(
            array(
                "value" => "",
                "label" => ""
            )
        );
        
        foreach ($fields as $key => $field) {
            if ($field['type'] == 'select') {
                $formfields[] = array(
                    "value" => $field['id'],
                    "label" => (!empty($field['label']) ? $field['label'] : 'Select Field #' . $field['id'])
                );
            }
        }

        return $formfields;
    }

    protected function productFields() {
        // Returns product fields and total field
        
        $form = $this->get_current_form();

        if(!is_array($form) || !isset($form['fields'])) {
            return array();
        }

        $fields = $form['fields'];
        
        // An array holding all the product fields on the form - will be returned
        $formfields = array(
            array(
                "value" => "",
                "label" => ""
            )
        );
        
        foreach ($fields as $key => $field) {
            if ($field['type'] == 'product' || $field['type'] == 'profilerdonate' || $field['type'] == 'total') {
                if ($field['type'] == 'total') {
                    $totalFieldExists = True;
                }
                
                $formfields[] = array(
                    "value" => $field['id'],
                    "label" => (!empty($field['label']) ? $field['label'] : 'Product Field #' . $field['id'])
                );
            }
        }

        // Add a form total field - handled specially by our plugin
        $formfields[] = array(
            "value" => "total",
            "label" => "Form Total"
        );

        return $formfields;
    }
    
    protected function sendDataToProfiler($url, $profiler_query, $ssl_mode = "normal") {
        // Sends the donation and client data to Profiler via POST

        // Remove whitespace
        foreach($profiler_query as $key => $val) {
            if(is_string($val)) {
                $profiler_query[$key] = trim($val);
            }
        }

        if(isset($profiler_query['DB'])) {
            $url .= '?' . http_build_query(array("DB" => $profiler_query['DB'], "Call" => 'submit'));
        }

        $api_type = $this->api_type;

        if(isset($profiler_query['apiurl_override'])) {
            $api_type = 'xml';
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

        if($ssl_mode == "bundled_ca") {
            // Use the CA Cert bundled with this plugin
            // Sourced from https://curl.haxx.se/ca/cacert.pem
            curl_setopt($ch, CURLOPT_CAINFO, plugin_dir_path(__FILE__) . "cacert.pem");

        } elseif($ssl_mode == "dontverifypeer") {
            // Don't verify the SSL peer. This is bad. No one should do this in production.
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        }

        if($api_type === 'json') {
            // JSON POST
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($profiler_query));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

        } else {
            // FORM POST
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Length: ' . strlen(http_build_query($profiler_query))));
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($profiler_query));
        }

        $result = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if(curl_error($ch)) {
            $cURL_error = curl_error($ch);
        } else {
            $cURL_error = null;
        }

        curl_close($ch);

        if($api_type === 'json') {
            $data_decoded = json_decode($result, true);

            if($data_decoded === null) {
                // In some cases (e.g. Mailing List Basic), Profiler accepts JSON but returns XML.
                $data_decoded = json_decode(json_encode((array)simplexml_load_string($result)), true);
            }
        } else {
            $data_decoded = json_decode(json_encode((array)simplexml_load_string($result)), true);
        }

        return array(
            "httpstatus" => $status_code,
            "url" => $url,
            "dataSent" => $profiler_query,
            "data" => $result,
            "dataArray" => $data_decoded,
            "cURLError" => $cURL_error,
            "cURL_SSL_Mode" => $ssl_mode,
            "api_type" => $api_type,
        );
    }

    public function sendFailureEmail($entry, $form, $pfResponse, $sendTo) {
        // Sends an alert email if integration with Profiler failed

        if(!isset($pfResponse['dataArray']['error'])) {
            $pfResponse['dataArray']['error'] = "";
        }

        $headers = '';
        $message = "--- PROFILER DATA FAILURE #" . $form["id"] . "/" . $entry["id"] . " ---" . "\n\n";
        $message .= "Gravity Form #" . $form["id"] . " with Entry ID #" . $entry["id"] . " failed to be sent to the Profiler API.\r\n";
        $message .= "HTTP Status Code: " . $pfResponse['httpstatus'] . "\r\n";
        $message .= "Profiler Error Message: " . $pfResponse['dataArray']['error'] . "\r\n";
        $message .= "\r\n\r\n";
        $message .= "This is the data that was sent to the Profiler API:\r\n";

        foreach($pfResponse['dataSent'] as $key => $val) {
            if($key == "apikey" || $key == "apipass" || $key == "apipassword" || $key == "cardnumber" || $key == "api_user" || $key == "api_pass" || $key == $this->apifield_apikey || $key == $this->apifield_apipass) {
                $val = "--REDACTED--";
            }
            $message .= $key . ": " . $val . "\r\n";
        }

        wp_mail($sendTo, "Profiler API Failure", $message, $headers);
    }

    protected function get_feed_instance($form, $entry) {
        // Get all feeds and picks the first.
        // Realistically we'll only have one active Profiler feed per form

        $feeds = $this->get_feeds($form['id']);

        foreach($feeds as $feed) {
            if ($feed['is_active'] && $this->is_feed_condition_met($feed, $form, $entry)) {
                return $feed;
            }
        }

        return false;
    }

    public function get_country_name($country_code) {
        $countries = GF_Fields::get('address')->get_countries();

        foreach($countries as $key => $val) {
            if(strtoupper($key) == strtoupper($country_code)) {
                return $val;
            }
        }

        // Code not found, fall back to the supplied code...
        return $country_code;
    }

    private function get_client_ip_address() {
        // Returns the client's IP Address

        if (getenv('HTTP_CLIENT_IP')) {
            $ipaddress = getenv('HTTP_CLIENT_IP');
        } else if(getenv('HTTP_X_FORWARDED_FOR')) {
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        } else if(getenv('HTTP_X_FORWARDED')) {
            $ipaddress = getenv('HTTP_X_FORWARDED');
        } else if(getenv('HTTP_FORWARDED_FOR')) {
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        } else if(getenv('HTTP_FORWARDED')) {
            $ipaddress = getenv('HTTP_FORWARDED');
        } else if(getenv('REMOTE_ADDR')) {
            $ipaddress = getenv('REMOTE_ADDR');
        } else {
            $ipaddress = 'UNKNOWN';
        }

        return $ipaddress;
    }

    public function save_feed_settings($feed_id, $form_id, $settings) {
        // We override this function in order to trigger an update of the custom fields cache
        $result = parent::save_feed_settings($feed_id, $form_id, $settings);

        return $result;

    }

    public function getTotalAmount($entry, $form = null) {
        // Returns the total amount as a float
        if(!isset($entry['payment_amount']) && $form !== null) {
            return GFCommon::get_order_total($form, $entry);
        }
        
        return (float)$entry['payment_amount'];
        
    }

    public function getCardDetails($form) {
        // Returns an array with all the credit card details
        
        $details = array(
            "type" => false,
            "number" => False,
            "expiry_month" => False,
            "expiry_year" => False,
            "ccv" => false,
            "name" => False,
            "usingSpecialCardField" => False,
        );
        
        foreach ($form["fields"] as $fieldkey => $field) {
            if ($field['type'] == 'creditcard' && !RGFormsModel::is_field_hidden($form, $field, array())) {
                $details['number'] = rgpost('input_' . $field['id'] . '_1');
                $details['type'] = $this->getCardTypeFromNumber($details['number']);
                
                $ccdate_array = rgpost('input_' . $field['id'] . '_2');
                
                $details['expiry_month'] = $ccdate_array[0];
                if (strlen($details['expiry_month']) < 2) {
                    $details['expiry_month'] = '0' . $details['expiry_month'];
                }
                
                $details['expiry_year'] = $ccdate_array[1];
                if (strlen($details['expiry_year']) <= 2) {
                    $details['expiry_year'] = '20'.$ccdate_year;
                }
                
                $details['name'] = rgpost('input_' . $field['id'] . '_5');
                $details['ccv'] = rgpost('input_' . $field['id'] . '_3');
            }
        }
        
        if(isset($_POST['gf_pf_cardnum']) && !empty($_POST['gf_pf_cardnum'])) {
            $details['number'] = $_POST['gf_pf_cardnum'];
            $details['usingSpecialCardField'] = True;
            $details['type'] = $this->getCardTypeFromNumber($details['number']);
        }
        
        return $details;
        
    }
    
    public function getCardTypeFromNumber($number) {
        // Atempts to parse the credit card number and return the card type (Visa, MC, etc.)
        // From http://wephp.co/detect-credit-card-type-php/
        
        $number = preg_replace('/[^\d]/','', $number);
        
        if (preg_match('/^3[47][0-9]{13}$/', $number)) {
            return 'Amex';
        } elseif (preg_match('/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/', $number)) {
            return 'Diner';
        } elseif (preg_match('/^6(?:011|5[0-9][0-9])[0-9]{12}$/', $number)) {
            return 'Discover';
        } elseif (preg_match('/^(?:2131|1800|35\d{3})\d{11}$/', $number)) {
            return 'JCB';
        } elseif (preg_match('/^5[1-5][0-9]{14}$/', $number)) {
            return 'Master';
        } elseif (preg_match('/^4[0-9]{12}(?:[0-9]{3})?$/', $number)) {
            return 'Visa';
        } else {
            return 'Unknown';
        }

    }

    protected function clean_amount($entry) {
        // Clean up pricing amounts
        
        $entry = preg_replace("/\|(.*)/", '', $entry); // replace everything from the pipe symbol forward
        if (strpos($entry, '.') === false) {
            $entry .= ".00";
        }
        if (strpos($entry, '$') !== false) {
            $startsAt = strpos($entry, "$") + strlen("$");
            $endsAt = strlen($entry);
            $amount = substr($entry, 0, $endsAt);
            $amount = preg_replace("/[^0-9,.]/", "", $amount);
        } else {
            $amount = preg_replace("/[^0-9,.]/", "", sprintf("%.2f", $entry));
        }

        $amount = str_replace('.', '', $amount);
        $amount = str_replace(',', '', $amount);
        return $amount;
    }

    protected function creditcard_mask($number) {
        // Returns a credit card with all but the first six and last four numbers masked

        if(strlen($number) < 11) {
            // Prevents a fatal error on str_repeat in PHP8
            return '';
        }

        return implode("-", str_split(substr($number, 0, 6) . str_repeat("X", strlen($number) - 10) . substr($number, -4), 4));
    }

    public function enable_creditcard($is_enabled) {
        return true;
    }

    public function metabox_payments($meta_boxes, $entry, $form) {
        // Allows the Payment Meta Box to be displayed on the 'Entries' screen
        // From https://www.gravityhelp.com/documentation/article/gform_entry_detail_meta_boxes/
        
        if (!isset($meta_boxes['payment'])) {
            $meta_boxes['payment'] = array(
                'title'         => 'Payment Details',
                'callback'      => array('GFEntryDetail', 'meta_box_payment_details'),
                'context'       => 'side',
                'callback_args' => array($entry, $form),
            );
        }

        return $meta_boxes;
    }

    protected function gformEntryPostSave($entry, $form, $gatewaydata) {
        // Log the successful gateway data

        foreach ($gatewaydata as $key => $val) {
            switch ($key) {
                case 'payment_status':
                case 'payment_date':
                case 'payment_amount':
                case 'transaction_id':
                case 'transaction_type':
                case 'payment_gateway':
                case 'authcode':
                    // update entry
                    $entry[$key] = $val;
                    break;

                default:
                    // update entry meta
                    gform_update_meta($entry['id'], $key, $val);
                    break;
            }
        }

        GFAPI::update_entry($entry);

        return $entry;
    }

    public function stripe_customer_id($customer_id, $feed, $entry, $form) {
        // Create a new customer in Stripe

        static $cached_customer_ids = array();

        // Find email address field
        foreach($form['fields'] as &$field) {
            if($field->type == "email") {
                $email_address = $this->get_field_value($form, $entry, $field->id);
            }
        }

        // Find name field on form
        $name = '';
        foreach($form['fields'] as $fieldKey => &$field) {
            if($field->type == "name") {
                $name = $entry[$field->id . '.3'] . ' ' . $entry[$field->id . '.6'];
            }
        }

        if(!isset($email_address) || empty($email_address)) {
            return $customer_id;
        }

        if(isset($cached_customer_ids[$email_address]) && !empty($cached_customer_ids[$email_address])) {
            return $cached_customer_ids[$email_address];
        }

        if(class_exists('\Stripe\Customer')) {
            // Find an existing customer by email
            $stripe_all_customers = \Stripe\Customer::all(array(
                'email' => $email_address,
                'limit' => 1
            ));

            if(isset($stripe_all_customers['data'][0]['id']) && !empty($name) && $name != $stripe_all_customers['data'][0]['name']) {
                // Update the name of the customer
                $update = \Stripe\Customer::update(
                    $stripe_all_customers['data'][0]['id'],
                    array(
                        'name' => $name
                    )
                );

                // Return existing customer ID
                if(isset($stripe_all_customers['data'][0]['id'])) {
                    $cached_customer_ids[$email_address] = $stripe_all_customers['data'][0]['id'];
                    return $stripe_all_customers['data'][0]['id'];
                }
            }
        }

        // Create a new customer
        $customer_meta = array();
        $customer_meta['description'] = $email_address . ' ' . $name;
        $customer_meta['name'] = $name;
        $customer_meta['email'] = $email_address;

        $customer = gf_stripe()->create_customer($customer_meta, $feed, $entry, $form);
        $cached_customer_ids[$email_address] = $customer->id;

        return $customer->id;
    }

    public function stripe_customer_id_save($customer, $feed, $entry, $form) {
        // Get the new Stripe Customer ID and save for later use
        gform_update_meta($entry['id'], 'stripe_customer_id', $customer->id);
        return $customer;
    }

    public function stripe_payment_intent($charge_meta, $feed, $submission_data, $form, $entry) {
        $charge_meta['setup_future_usage'] = 'off_session';
        return $charge_meta;
    }

    public function stripe_elements_setup($intent_information, $feed, $form) {
        $intent_information['setup_future_usage'] = 'off_session';
        return $intent_information; 
    }

    public function stripe_payment_description($description, $strings, $entry, $submission_data, $feed) {

        if(!class_exists('GFAPI') || !isset($entry['form_id'])) {
            return $description;
        }

        $description = 'Form #' . $entry['form_id'] . ', Entry #' . $entry['id'];

        // Find Name field
        $form = GFAPI::get_form($entry['form_id']);
        foreach($form['fields'] as $fieldKey => $field) {
            if($field->type == "name") {
                $description .= ' - ' . $entry[$field->id . '.3'] . ' ' . $entry[$field->id . '.6'];
            }
        }

        return $description;

    }

    public function stripe_refund_form_setting($fields, $form) {
        // Form setting to enable immediate Stripe refunds

        // Only show this setting if the form has a Stripe feed
        $feeds = GFAPI::get_feeds(null, $form['id']);
        $has_stripe_feed = false;

        foreach($feeds as $feed) {
            if($feed['addon_slug'] === 'gravityformsstripe') {
                $has_stripe_feed = true;
                break;
            }
        }

        if ($has_stripe_feed === false) {
            return $fields;
        }

        $fields['stripe_immediate_refund'] = array(
            'title'      => 'Stripe Immediate Refund',
            'tooltip'    => 'By default, Stripe captures the payment when the form is submitted. If you wish to issue an immediate refund during the form submission process, emails this option. This option is used for Card Update workflows, such as updating regular pledge payment details.',
            'fields'     => array(),
        );

        $fields['stripe_immediate_refund']['fields'][] = array(
            'name'       => 'stripe_immediate_refund_mode',
            'label'      => 'Stripe Immediate Refund Mode',
            'type'       => 'checkbox',
            'choices' => array(
                array(
                    'label'         => 'Enable Immediate Refund',
                    'name'          => 'stripe_immediate_refund',
                    'default_value' => 0,
                ),
            ),
        );

        return $fields;
    }

    public function stripe_refund_after_submission($entry, $form) {
        // Processes the refund from Stripe

        if(!isset($form['stripe_immediate_refund']) || $form['stripe_immediate_refund'] != 1) {
            return $entry;
        }

        if(!isset($entry['transaction_id']) || substr($entry['transaction_id'], 0, 3) !== "pi_") {
            return $entry;
        }

        if(gform_get_meta($entry['id'], 'stripe_immediate_refund_id') != false) {
            // If refund has already been processed, don't try and refund again
            return $entry;
        }

        try {
            if(!class_exists('\Stripe\Stripe')) {
                require_once(plugin_dir_path(__DIR__) . 'gravityformsstripe/includes/autoload.php');
            }

            // Set Stripe API key.
            $stripe_options = get_option('gravityformsaddon_gravityformsstripe_settings');
            \Stripe\Stripe::setApiKey($stripe_options[$stripe_options['api_mode'] . '_secret_key']);

        } catch(Exception $e) {
            error_log("STRIPE/PROFILER IMMEDIATE REFUND SETUP ERROR: " . print_r($e, true));
            GFAPI::add_note($entry['id'], 0, '', 'Stripe Immediate Refund - Failed. Error Technical Information (Setup): ' . $e->getMessage(), $this->_slug, 'error');
            return $entry;
        }

        try {
            $refund = \Stripe\Refund::create([
                'payment_intent' => $entry['transaction_id'],
                'metadata' => array(
                    'refund_source' => 'profiler_gravityforms_stripe_immediate_refund',
                ),
            ]);

            GFAPI::add_note($entry['id'], 0, '', 'Stripe Immediate Refund - Successful. Refund ID: ' . $refund->id, $this->_slug, 'success');
            gform_add_meta($entry['id'], 'stripe_immediate_refund_id', $refund->id, $form['id']);

        } catch(Exception $e) {
            error_log("STRIPE/PROFILER IMMEDIATE REFUND ERROR: " . print_r($e, true));
            GFAPI::add_note($entry['id'], 0, '', 'Stripe Immediate Refund - Failed. Error Technical Information: ' . $e->getMessage(), $this->_slug, 'error');
        }

        return $entry;
    }


    public function meta_box_entry($meta_boxes, $entry, $form) {
        // Custom Metabox

        if($this->get_active_feeds($form['id'])) {

            $profiler_logs_meta = gform_get_meta($entry['id'], 'profiler_logs');

            if(!empty($profiler_logs_meta)) {
                $meta_boxes[$this->_slug] = array(
                    'title'    => 'Profiler API',
                    'callback' => array($this, 'meta_box_entry_render'),
                    'context'  => 'side',
                    'callback_args' => array($entry, $form),
                );
            }
        }

        return $meta_boxes;
    }

    public function meta_box_entry_render($data) {
        // Render the Custom Metabox
        $html = '';

        $meta_fields = array(
            "profiler_integrationid" => "Integration ID",
            "profiler_integration_guid" => "Integration GUID",
            "profiler_logs" => "Profiler API Logs",
        );

        foreach($meta_fields as $field_name => $field_title) {

            $meta_value = gform_get_meta($data['entry']['id'], $field_name);

            if(!empty($meta_value)) {
                $html .= '<p><strong>'.$field_title.'</strong>:<br /><pre style="width: 100%; overflow-x: scroll;">'.esc_html($meta_value).'</pre></p>';
            }
        }

        echo $html;
    }

    private function auto_format_phone($phone_number_original) {
        // This function auto-formats a phone number into an Australian format (0000 000 000 or 00 0000 0000)

        // Remove certain characters
        $phone_number = str_replace(array(" ", "-", "(", ")"), array("", "", "", ""), $phone_number_original);

        // Remove +61 prefix
        if(substr($phone_number, 0, 3) == "+61") {
            $phone_number = substr($phone_number, 3);

            if(strlen($phone_number) === 9) {
                $phone_number = "0" . $phone_number;
            }
        }

        // International number. Return as-is
        if(substr($phone_number, 0, 1) == "+") {
            return $phone_number;
        }

        // Probably an Australian number
        if(strlen($phone_number) == 10) {
            // 0000 000 000
            if(substr($phone_number, 0, 2) == "04") {
                $phone_number = substr($phone_number, 0, 4) . " " . substr($phone_number, 4, 3) . " " . substr($phone_number, 7);
                return $phone_number;
            }

            // 00 0000 0000
            $phone_number = substr($phone_number, 0, 2) . " " . substr($phone_number, 2, 4) . " " . substr($phone_number, 6);
            return $phone_number;
        }

        // Probably Australian landline with no area prefix
        if(strlen($phone_number) == 8) {
            // Adjust to 0000 0000 format
            $phone_number = substr($phone_number, 0, 4) . " " . substr($phone_number, 4);
        }

        return $phone_number;
    }
}

?>