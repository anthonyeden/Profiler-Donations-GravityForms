<?php
if (class_exists("GFForms")) {
    
    GFForms::include_payment_addon_framework();
    
    class GFProfilerDonate extends GFFeedAddOn {
        protected $_min_gravityforms_version = "1.8.12";
        protected $_slug = "profiler-donation-gf";
        protected $_path = "profiler-donation-gf/index.php";
        protected $_full_path = __FILE__;
        protected $_url = "";
        protected $_title = "Profiler / Gravity Forms - Donation Integration Feed";
        protected $_short_title = "Profiler Donations";
        protected $formid;
        protected $form;
        protected $gateways;
        private static $_instance = null;
        
        public static function get_instance() {
            if (self::$_instance == null) {
                self::$_instance = new GFProfilerDonate();
            }
            
            self::$_instance->form = self::$_instance->get_current_form();
            self::$_instance->formid = self::$_instance->form["id"];
            
            return self::$_instance;
        }

        public function init() {
            parent::init();
            //Add the "total amount" merge field:
            add_filter('gform_replace_merge_tags',          array($this, 'mergeTag_totalAmount'), 10, 7);
            add_action('gform_admin_pre_render',            array($this, 'addMergeTags'));

            // Filter to allow Profiler to process payments internally (instead of a gateway in Gravity Forms)
            add_filter("gform_validation",                  array($this, "validate_payment"), 1000);

            // Enable credit card fields
            add_filter('gform_enable_credit_card_field',    array($this, "enable_creditcard"), 11);

            // Filter to ensure the Payment Meta-Box is displayed
            add_filter("gform_entry_detail_meta_boxes",     array($this, "metabox_payments"), 10, 3);
        }

        public function feed_settings_fields() {
            // This function adds all the feed setting fields we need to communicate with Profiler
            $form = $this->get_current_form();
            
            $field_settings = self::$_instance->formFields();
            $product_field_settings = self::$_instance->productFields();
            $hiddenFields = self::$_instance->hiddenFields();
            $userdefinedfields = self::$_instance->userDefinedFields();
            
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
                "label" => 'Profiler Server Address (RAPID)',
                "type" => "text",
                "name" => "profilerdonation_serveraddress",
                "required" => true,
                "tooltip" => "URL in this format: https://your_profiler_url/ProfilerPROG/api/api_call.cfm",
            );
            
            $fields[] = array(
                "label" => 'Profiler Database Name',
                "type" => "text",
                "name" => "profilerdonation_dbname",
                "required" => true,
            );
            
            $fields[] = array(
                "label" => 'Profiler API Key',
                "type" => "text",
                "name" => "profilerdonation_apikey",
                "required" => true,
            );
            
            $fields[] = array(
                "label" => 'Profiler API Password',
                "type" => "text",
                "name" => "profilerdonation_apipass",
                "required" => true,
            );

            $fields[] = array(
                "label" => 'Use Profiler As A Gateway?',
                "type" => "select",
                "name" => "profilerdonation_useasgateway",
                "required" => false,
                "tooltip" => "Set this to 'Yes' if you want Profiler to be responsible for processing the payment (instead of a
                              Gravity Forms Payment Plugin). If you use this option, disable any other Payment Plugins.",
                'choices' => array(
                    array(
                        'label'         => 'No - Gravity Forms will Process Payments',
                        'value'         => 'false',
                    ),
                    array(
                        'label'         => 'Yes - Profiler will Process Payments',
                        'value'         => 'true',
                    ),
                ),
            );

            $fields[] = array(
                "label" => 'Profiler Server Address (Gateway)',
                "type" => "text",
                "name" => "profilerdonation_serveraddress_gateway",
                "required" => false,
                "tooltip" => "URL in this format: https://your_profiler_url/ProfilerPROG/api/v2/payments/<br />
                              This is only necessary if you set 'Use Profiler As A Gateway?' to True.",
            );
            
            $fields[] = array(
                "label" => 'Profiler Errors Email Address',
                "type" => "text",
                "name" => "profilerdonation_erroremailaddress",
                "required" => false,
            );
            
            $fields[] = array(
                "label" => 'Amount Field',
                "type" => "select",
                "name" => "profilerdonation_amount",
                "required" => true,
                "choices" => $product_field_settings
            );
            
            $fields[] = array(
                "label" => 'Donation Type',
                "type" => "select",
                "name" => "profilerdonation_donationtype",
                "required" => false,
                "choices" => $field_settings,
                "tooltip" => "The value of this field must be set to 'once' or 'regular'"
            );
            
            $fields[] = array(
                "label" => 'Payment Method',
                "type" => "select",
                "name" => "profilerdonation_paymentmethod",
                "required" => false,
                "choices" => $field_settings,
                "tooltip" => "The value of this field must be set to 'creditcard' or 'bankdebit'. If this field isn't set, or an invalid value is passed, we assume it's a credit card."
            );
            
            $fields[] = array(
                "label" => 'Pledge Frequency',
                "type" => "select",
                "name" => "profilerdonation_pledgefreq",
                "required" => false,
                "choices" => $field_settings,
                "tooltip" => "The value of this field must be set to 'monthly' or 'yearly'. This field will be used if 'Donation Type' is set to 'regular'."
            );
            
            $fields[] = array(
                "label" => 'Pledge Amount',
                "type" => "select",
                "name" => "profilerdonation_pledgeamount",
                "required" => false,
                "choices" => $product_field_settings,
                "tooltip" => "This amount field will be used if Donation Type is set to 'regular'.",
            );
            
            $fields[] = array(
                "label" => 'Client: Title',
                "type" => "select",
                "name" => "profilerdonation_clienttitle",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: First Name',
                "type" => "select",
                "name" => "profilerdonation_clientfname",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: Last Name',
                "type" => "select",
                "name" => "profilerdonation_clientlname",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: Email',
                "type" => "select",
                "name" => "profilerdonation_clientemail",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: Address',
                "type" => "select",
                "name" => "profilerdonation_clientaddress",
                "required" => false,
                "choices" => $field_settings
            );

            $fields[] = array(
                "label" => 'Client: City',
                "type" => "select",
                "name" => "profilerdonation_clientcity",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: State',
                "type" => "select",
                "name" => "profilerdonation_clientstate",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: Zip/Postcode',
                "type" => "select",
                "name" => "profilerdonation_clientpostcode",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: Country',
                "type" => "select",
                "name" => "profilerdonation_clientcountry",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: Organisation',
                "type" => "select",
                "name" => "profilerdonation_clientorganisation",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: Home Phone',
                "type" => "select",
                "name" => "profilerdonation_clientphoneah",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: Business Phone',
                "type" => "select",
                "name" => "profilerdonation_clientphonebus",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: Mobile Phone',
                "type" => "select",
                "name" => "profilerdonation_clientphonemobile",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: Website',
                "type" => "select",
                "name" => "profilerdonation_clientwebsite",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Bank Debit: Account Name',
                "type" => "select",
                "name" => "profilerdonation_bankdebit_accountname",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Bank Debit: BSB',
                "type" => "select",
                "name" => "profilerdonation_bankdebit_bsb",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Bank Debit: Account Number',
                "type" => "select",
                "name" => "profilerdonation_bankdebit_accountnumber",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Comments',
                "type" => "select",
                "name" => "profilerdonation_comments",
                "required" => false,
                "choices" => $field_settings,
            );
            
            $fields[] = array(
                "label" => 'UDF: Donation Source Code',
                "type" => "select",
                "name" => "profilerdonation_userdefined_sourcecode",
                "required" => false,
                "tooltip" => "Pick the Profiler User Defined Field you wish the donation source code to be sent to",
                "choices" => $userdefinedfields,
            );
            
            $fields[] = array(
                "label" => 'Donation Source Code - Default Value',
                "type" => "text",
                "name" => "profilerdonation_sourcecode",
                "required" => false,
                "tooltip" => "Can be overriden by GET parameter or Short Code. Sent to the UDF specificed above.",
            );

            $fields[] = array(
                "label" => 'Donation Source Code - Mode',
                "type" => "select",
                "name" => "profilerdonation_sourcecodemode",
                "required" => false,
                "tooltip" => "Donation Source Codes can be set the normal way (from shortcodes, URL Parameters, and defaults), or set based on the value of a specific form field.",
                "choices" => array(
                        array(
                            "label" => "Regular Behaviour (use Shortcodes, URL Parameters and Defaults)",
                            "value" => "normal"
                        )
                    )
                    + self::$_instance->formFields("Form Field: "),
            );
            
            $fields[] = array(
                "label" => 'UDF: Pledge Source Code',
                "type" => "select",
                "name" => "profilerdonation_userdefined_pledgesourcecode",
                "required" => false,
                "tooltip" => "Pick the Profiler User Defined Field you wish the pledge source code to be sent to",
                "choices" => $userdefinedfields,
            );
            
            $fields[] = array(
                "label" => 'Pledge Source Code - Default Value',
                "type" => "text",
                "name" => "profilerdonation_pledgesourcecode",
                "required" => false,
                "tooltip" => "Can be overriden by GET parameter or Short Code. Sent to the UDF specificed above.",
            );
            
            $fields[] = array(
                "label" => 'UDF: Pledge Acquisition Code',
                "type" => "select",
                "name" => "profilerdonation_userdefined_pledgeacquisitioncode",
                "required" => false,
                "tooltip" => "Pick the Profiler User Defined Field you wish the pledge acquisition code to be sent to",
                "choices" => $userdefinedfields,
            );
            
            $fields[] = array(
                "label" => 'Pledge Acquisition Code - Default Value',
                "type" => "text",
                "name" => "profilerdonation_pledgeacquisitioncode",
                "required" => false,
                "tooltip" => "Can be overriden by GET parameter or Short Code",
            );

            $fields[] = array(
                "label" => 'UDF: Donation Purpose',
                "type" => "select",
                "name" => "profilerdonation_userdefined_donationpurposecode",
                "required" => false,
                "tooltip" => "Pick the Profiler User Defined Field you wish the donation's purpose code to be sent to",
                "choices" => $userdefinedfields,
            );
            
            $fields[] = array(
                "label" => 'Donation Purpose Field',
                "type" => "select",
                "name" => "profilerdonation_donationpurposecode",
                "required" => false,
                "tooltip" => "This field's value should match the Purpose Codes setup within Profiler.",
                "choices" => $field_settings
            );

            $fields[] = array(
                "label" => 'UDF: Donation Tag',
                "type" => "select",
                "name" => "profilerdonation_userdefined_donationtagcode",
                "required" => false,
                "tooltip" => "Pick the Profiler User Defined Field you wish the donation's tag code to be sent to. Do not set this up if you use Tag Automation within Profiler.",
                "choices" => $userdefinedfields,
            );

            $fields[] = array(
                "label" => 'Donation Tag Field',
                "type" => "select",
                "name" => "profilerdonation_donationtagcode",
                "required" => false,
                "tooltip" => "This field's value should match the Tag Codes setup within Profiler.",
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'UDF: Client IP Address',
                "type" => "select",
                "name" => "profilerdonation_userdefined_clientip",
                "required" => false,
                "tooltip" => "Pick the Profiler User Defined Field you wish the client's IP address to be sent to",
                "choices" => $userdefinedfields,
            );
            
            $fields[] = array(
                "label" => 'UDF: Gateway Transaction ID',
                "type" => "select",
                "name" => "profilerdonation_userdefined_gatewaytransactionid",
                "required" => false,
                "tooltip" => "Pick the Profiler User Defined Field you wish the gateway transaction ID to be sent to (certain gateways only)",
                "choices" => $userdefinedfields,
            );
            
            $fields[] = array(
                "label" => 'Profiler Logs',
                "type" => "select",
                "name" => "profilerdonation_logs",
                "tooltip" => 'Link it to a Hidden field that will hold Profiler Response Logs',
                "required" => false,
                "choices" => $hiddenFields
            );
            
            $fields[] = array(
                "label" => 'SSL Mode',
                "type" => "select",
                "name" => "profilerdonation_sslmode",
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

            return array(
                array(
                    "title" => "Profiler Donation Feed Settings",
                    "fields" => $fields
                )
            );
            
        }
        
        public function feed_list_columns() {
            // Returns columns to feed index page
            return array(
                'feedName'  => 'Name',
                'profilerdonation_dbname' => 'PF Database Name',
            );
        }
        
        protected function formFields($preface = "") {
            // Returns an array of all fields on this form
            
            $form = $this->get_current_form();
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
                        $field_settings['label'] = $preface . $inputvalue['label'];
                        $formfields[] = $field_settings;
                    }
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

        
        protected function productFields() {
            // Returns product fields and total field
            
            $form = $this->get_current_form();
            $fields = $form['fields'];
            
            // An array holding all the product fields on the form - will be returned
            $formfields = array(
                array(
                    "value" => "",
                    "label" => ""
                )
            );
            
            $totalFieldExists = False;
            
            foreach ($fields as $key => $field) {
                if ($field['type'] == 'product' || $field['type'] == 'profilerdonate' || $field['type'] == 'total') {
                    if ($field['type'] == 'total') {
                        $totalFieldExists = True;
                    }
                    
                    $formfields[] = array(
                        "value" => $field['id'],
                        "label" => $field['label']
                    );
                }
            }
            
            //check if field total don't exist then add it
            if ($totalFieldExists == False) {
                $formfields[] = array(
                    "value" => "total",
                    "label" => "Total"
                );
            }
            
            return $formfields;
        }
        
        
        protected function hiddenFields() {
            // Returns an array of hidden fields
            
            $form = $this->get_current_form();
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
                        "label" => $field['label']
                    );
                }
            }
            
            return $formfields;
        }
        
        
        protected function checkboxRadioFields() {
            // Returns an array of checkbox and radio fields
            
            $form = $this->get_current_form();
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
                    $formfields[] = array(
                        "value" => $field['id'],
                        "label" => $field['label']
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
        
        public function process_feed($feed, $entry, $form, $fromValidatorProcessPFGateway = false) {
            // Processes the feed and prepares to send it to Profiler
            // This can either do a gateway payment, or just an integration

            if($feed['meta']['profilerdonation_useasgateway'] == "true" && $fromValidatorProcessPFGateway == true) {
                $useAsGateway = true;

            } elseif($feed['meta']['profilerdonation_useasgateway'] !== "true" && $fromValidatorProcessPFGateway == true) {
                // This shouldn't happen. Let's catch it just in case.
                return false;

            } else {
                $useAsGateway = false;

            }

            global $gf_profiler_gatewaydata;
            if(isset($gf_profiler_gatewaydata) && is_array($gf_profiler_gatewaydata)) {
                $entry = $this->gformEntryPostSave($entry, $form, $gf_profiler_gatewaydata);
            }

            $form_id = $form["id"];
            $settings = $this->get_form_settings($form);
            
            // All the POST data for Profiler gets stored in this variable
            $postData = array();
            
            $postData['DB'] = $feed['meta']['profilerdonation_dbname'];
            $postData['apikey'] = $feed['meta']['profilerdonation_apikey'];
            $postData['apipass'] = $feed['meta']['profilerdonation_apipass'];

            if($useAsGateway == true) {
                // Profiler processes this payment
                $postData['method'] = "gateway.payment";
            } else {
                // Profiler will just record integration data
                $postData['method'] = "integration.send";
                $postData['datatype'] = "OLDON";
            }

            // Calculate the total or just use one field:
            if($feed['meta']['profilerdonation_amount'] == "total") {
                $postData['amount'] = $this->getTotalAmount($entry, $form);
                $postData['donationamount'] = $this->getTotalAmount($entry, $form);
                $postData['pledgeamount'] = $this->getTotalAmount($entry, $form);
            } else {
                $postData['amount'] = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_amount']);
                $postData['donationamount'] = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_amount']);
                $postData['pledgeamount'] = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_amount']);
            }
            
            // Client Fields:
            $postData['title'] = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_clienttitle']);
            $postData['surname'] = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_clientlname']);
            $postData['firstname'] = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_clientfname']);
            $postData['clientname'] = $postData['firstname'] . " " . $postData['surname'];
            $postData['org'] = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_clientorganisation']);
            $postData['address'] = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_clientaddress']);
            $postData['suburb'] = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_clientcity']);
            $postData['state'] = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_clientstate']);
            $postData['postcode'] = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_clientpostcode']);
            $postData['country'] = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_clientcountry']);
            $postData['phoneah'] = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_clientphoneah']);
            $postData['phonebus'] = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_clientphonebus']);
            $postData['phonemobile'] = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_clientphonemobile']);
            $postData['email'] = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_clientemail']);
            $postData['website'] = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_clientwebsite']);
            
            // Credit card fields:
            $cardDetails = $this->getCardDetails($form);
            $postData['cardtype'] = $cardDetails['type'];
            $postData['cardnumber'] = $cardDetails['number'];
            $postData['cardexpiry'] = $cardDetails['expiry_month'] . " " . $cardDetails['expiry_year'];
            
            // Comments
            $postData['comments'] = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_comments']);
            $postData['userdefined2'] = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_comments']);
            
            // User Defined Fields:
            
            if($feed['meta']['profilerdonation_userdefined_sourcecode'] !== "") {
                // Donation Source Code

                if(isset($feed['meta']['profilerdonation_sourcecodemode']) && $feed['meta']['profilerdonation_sourcecodemode'] !== "normal") {
                    // The source code is a value of a specified field
                    $donationSourceCode = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_sourcecodemode']);

                } else {
                    // Regular behaviour
                    $donationSourceCode = $this->getDonationCode($feed, 'sourcecode');
                }

                $postData['userdefined' . $feed['meta']['profilerdonation_userdefined_sourcecode']] = $donationSourceCode;
                $postData['sourcecode'] = $donationSourceCode;
            }
            
            if($feed['meta']['profilerdonation_userdefined_pledgesourcecode'] !== "") {
                // Pledge Source Code
                $postData['userdefined' . $feed['meta']['profilerdonation_userdefined_pledgesourcecode']] = $this->getDonationCode($feed, 'pledgesourcecode');
            }
            
            if($feed['meta']['profilerdonation_userdefined_pledgeacquisitioncode'] !== "") {
                // Pledge Acqusition code
                $postData['userdefined' . $feed['meta']['profilerdonation_userdefined_pledgeacquisitioncode']] = $this->getDonationCode($feed, 'pledgeacquisitioncode');
            }
            
            if($feed['meta']['profilerdonation_userdefined_donationpurposecode'] !== "" && $feed['meta']['profilerdonation_donationpurposecode'] !== "") {
                $postData['userdefined' . $feed['meta']['profilerdonation_userdefined_donationpurposecode']] = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_donationpurposecode']);
            }

            if($feed['meta']['profilerdonation_userdefined_donationtagcode'] !== "" && $feed['meta']['profilerdonation_donationtagcode'] !== "") {
                $postData['userdefined' . $feed['meta']['profilerdonation_userdefined_donationtagcode']] = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_donationtagcode']);
            }

            if($feed['meta']['profilerdonation_userdefined_clientip'] !== "") {
                // Client's IP Address
                $postData['userdefined' . $feed['meta']['profilerdonation_userdefined_clientip']] = $this->getClientIPAddress();
            }
            
            if($feed['meta']['profilerdonation_userdefined_gatewaytransactionid'] !== "" && isset($entry['transaction_id'])) {
                // Gateway transaction id
                $postData['userdefined' . $feed['meta']['profilerdonation_userdefined_gatewaytransactionid']] = $entry['transaction_id'];
            }
            
            if($this->get_field_value($form, $entry, $feed['meta']['profilerdonation_donationtype']) == "regular") {
                // Recurring donation
                $postData['datatype'] = "PLG";
                $postData['pledgetype'] = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_pledgefreq']);
                
                if($feed['meta']['profilerdonation_userdefined_sourcecode'] !== "") {
                    // If it's recurring, the donation gets the pledge source code instead of the donation code
                    $postData['userdefined' . $feed['meta']['profilerdonation_userdefined_sourcecode']] = $this->getDonationCode($feed, 'pledgesourcecode');
                }

            } elseif($useAsGateway == false) {
                // Once-off donation (not using Profiler as the gateway)
                $postData['cardnumber'] = "4444333322221111"; //PF expects a card number and expiry even for once-offs which have already been processed
                $postData['cardexpiry'] = date("m") . " " . date("Y");
                unset($postData['pledgeamount']);
                unset($postData['userdefined' . $feed['meta']['profilerdonation_userdefined_pledgeacquisitioncode']]);
                unset($postData['userdefined' . $feed['meta']['profilerdonation_userdefined_pledgesourcecode']]);
                
            }
            
            if($this->get_field_value($form, $entry, $feed['meta']['profilerdonation_paymentmethod']) == "bankdebit") {
                // Bank Debit - not currently wired up to Profiler correctly
                $postData['status'] = "Pending";
                unset($postData['cardtype']);
                unset($postData['cardnumber']);
                unset($postData['cardexpiry']);
                
                $postData['bankaccountname'] = $this->get_field_value($form, $entry, $feed['meta']["profilerdonation_bankdebit_accountname"]);
                $postData['bankbsb'] = $this->get_field_value($form, $entry, $feed['meta']["profilerdonation_bankdebit_bsb"]);
                $postData['bankaccountnumber'] = $this->get_field_value($form, $entry, $feed['meta']["profilerdonation_bankdebit_accountnumber"]);
                
            } elseif($useAsGateway == false) {
                // Credit Card
                // This feed only processes on success - so we assume an approved transaction
                $postData['status'] = "Approved";
            }
            
            // Make the response to the Profiler server with our integration data
            if($useAsGateway == true) {
                $pfResponse = $this->sendDataToProfiler($feed['meta']['profilerdonation_serveraddress_gateway'], $postData, $feed['meta']['profilerdonation_sslmode']);
            } else {
                $pfResponse = $this->sendDataToProfiler($feed['meta']['profilerdonation_serveraddress'], $postData, $feed['meta']['profilerdonation_sslmode']);
            }
            
            
            if($useAsGateway == true) {
                // Return the Profiler response data
                return $pfResponse;

            } else {
                // Save Profiler response data back to the form entry
                $logsToStore = json_encode($pfResponse);
                $logsToStore = str_replace($postData['cardnumber'], "--REDACTED--", $logsToStore);
                $logsToStore = str_replace($postData['apikey'], "--REDACTED--", $logsToStore);
                $logsToStore = str_replace($postData['apipass'], "--REDACTED--", $logsToStore);
                $entry[$feed['meta']['profilerdonation_logs']] = htmlentities($logsToStore);
                GFAPI::update_entry($entry);

            }
            
            if(!isset($pfResponse['dataArray']['status']) || $pfResponse['dataArray']['status'] != "Pass") {
                // Profiler failed. Send the failure email.
                $this->sendFailureEmail($entry, $form, $pfResponse, $feed['meta']['profilerdonation_erroremailaddress']);
            }
            
        }

        public function validate_payment($gform_validation_result) {
            // This function allows Profiler to process the credit card (instead of a separate gateway plugin)

            if(!$gform_validation_result['is_valid']) {
                // If it's already failed validation...
                return $gform_validation_result;
            }

            $form = $gform_validation_result['form'];
            $entry = GFFormsModel::create_lead($form);
            $feed = $this->get_feed_instance($form, $entry);

            if(!$feed) {
                return $gform_validation_result;
            }

            if($feed['meta']['profilerdonation_useasgateway'] !== "true") {
                // If we're not using Profiler as a gateway, we don't need to continue validation here.
                return $gform_validation_result;
            }

            if($this->hasFormBeenProcessed($form)) {
                // Entry has already been created
                $gform_validation_result['is_valid'] = false;

                foreach($form['fields'] as &$field) {
                    if($field->type == "creditcard") {
                        $field->failed_validation = true;
                        $field->validation_message = 'Sorry, this transaction has already been processed.';
                    }
                    
                }

            } else {

                // Send the data through to process_feed with a special flag that makes it try to take the money
                $result = $this->process_feed($feed, $entry, $form, true);

                if($result['dataArray']['gateway']['response'] == "True") {
                    // The form passed validation. Good times.
                    $gform_validation_result['is_valid'] = true;

                    global $gf_profiler_gatewaydata;
                    $gf_profiler_gatewaydata = array(
                        "payment_status"                => "Approved",
                        "payment_date"                  => date('Y-m-d H:i:s'),
                        "transaction_id"                => $result['dataArray']['gateway']['txn'],
                        "transaction_type"              => 1,
                        "payment_method"                => '',
                        "gfprofilergateway_unique_id"   => GFFormsModel::get_form_unique_id($form['id']),
                    );

                    if($feed['meta']['profilerdonation_amount'] == "total") {
                        $gf_profiler_gatewaydata['payment_amount'] = $this->getTotalAmount($entry, $form);
                    } else {
                        $gf_profiler_gatewaydata['payment_amount'] = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_amount']);
                    }

                } else {
                    // The form (overall) failed validation
                    $gform_validation_result['is_valid'] = false;

                    // Add the error message to the credit card field:
                    foreach($form['fields'] as &$field) {
                        if($field->type == "creditcard") {
                            $field->failed_validation = true;
                            $field->validation_message = 'We could not process your credit card. Please check your details and try again.';
                        }
                        
                    }
                }

            }

            $gform_validation_result['form'] = $form;
            return $gform_validation_result;

        }

        private function get_feed_instance($form, $entry) {
            // Get all feeds and picks the first.
            // Realistically we'll only have one active Profiler feed per form

            $feeds = $this->get_feeds($form['id']);

            foreach($feeds as $feed) {
                if ($feed['is_active'] && $this->is_feed_condition_met($feed, $form, $entry)) {
                    return $feed;
                    break;
                }
            }
        }

        private function hasFormBeenProcessed($form) {
            global $wpdb;

            $unique_id = RGFormsModel::get_form_unique_id($form['id']);

            $sql = "select lead_id from {$wpdb->prefix}rg_lead_meta where meta_key='gfprofilergateway_unique_id' and meta_value = %s";
            $lead_id = $wpdb->get_var($wpdb->prepare($sql, $unique_id));

            return !empty($lead_id);
        }

        private function gformEntryPostSave($entry, $form, $gatewaydata) {
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
        
        protected function getDonationCodes($feed) {
            // Returns an array of the various donation codes based on the heirachy
            
            return array(
                "sourcecode" => $this->getDonationCode($feed, "sourcecode"),
                "pledgesourcecode" => $this->getDonationCode($feed, "pledgesourcecode"),
                "pledgeacquisitioncode" => $this->getDonationCode($feed, "pledgeacquisitioncode"),
            );
            
        }
        
        protected function getDonationCode($feed, $code) {
            // Returns a single donation code based on this heirachy:
            // 1. Page GET paramater
            // 2. Page Short Code
            // 3. Feed Default Settings
            
            if(isset($_GET[$code])) {
                // Strip out all non-alphanumeric characters
                $outputcode = preg_replace('/[^a-z\d ]/i', '', $_GET[$code]);
            } else {
                $outputcode = "";
            }
            
            if(empty($outputcode)
                && isset($_SESSION['profilerdonation_codes_page'])
                && isset($_SESSION['profilerdonation_' . $code])
                && !empty($_SESSION['profilerdonation_' . $code])
                && $_SERVER['REQUEST_URI'] == $_SESSION['profilerdonation_codes_page']) {
                    // This is a global/session variable used to override the sourcecode per-page - it's set via a shortcode
                    $outputcode = $_SESSION['profilerdonation_' . $code];
            }
            
            if(empty($outputcode)) {
                $outputcode = $feed['meta']['profilerdonation_' . $code];
            }
            
            return strtoupper($outputcode);
        }
        
        protected function sendDataToProfiler($url, $profiler_query, $ssl_mode = "normal") {
            // Sends the donation and client data to Profiler via POST
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Length: ' . strlen(http_build_query($profiler_query))));
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($profiler_query));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            if($ssl_mode == "bundled_ca") {
                // Use the CA Cert bundled with this plugin
                // Sourced from https://curl.haxx.se/ca/cacert.pem
                curl_setopt($ch, CURLOPT_CAINFO, plugin_dir_path(__FILE__) . "cacert.pem");

            } elseif($ssl_mode == "dontverifypeer") {
                // Don't verify the SSL peer. This is bad. No one should do this in production.
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

            }

            $result = curl_exec($ch);
            $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if(curl_error($ch)) {
                $cURL_error = curl_error($ch);
            } else {
                $cURL_error = null;
            }
            
            curl_close($ch);
            
            return array(
                "httpstatus" => $status_code,
                "dataSent" => $profiler_query,
                "data" => $result,
                "dataXML" => simplexml_load_string($result),
                "dataArray" => json_decode(json_encode((array)simplexml_load_string($result)), 1),
                "cURLError" => $cURL_error,
                "cURL_SSL_Mode" => $ssl_mode,
            );
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
        
        public function mergeTag_totalAmount($text, $form, $entry, $url_encode, $esc_html, $nl2br, $format) {
            //Populate the custom merge field "{total_amount}"
            
            if(!is_array($text)) {
                $text = array($text);
                $returnAsStr = true;
            } else {
                $returnAsStr = false;
            }
            
            foreach($text as $key => $val) {
                if(strpos($text[$key], '{total_amount}') !== false) {
                    $total = $this->getTotalAmount($entry, $form);
                    $text[$key] = str_replace('{total_amount}', "$" . number_format($total, 2), $text[$key]);
                }
            }
            
            if($returnAsStr == true) {
                return $text[0];
            } else {
                return $text;
            }
            
        }
        
        public function addMergeTags($form) {
            // Adds the merge tag to the admin form drop-down
            
            echo '
            <script type="text/javascript">
                gform.addFilter("gform_merge_tags", "add_merge_tags");
                function add_merge_tags(mergeTags, elementId, hideAllFields, excludeFieldTypes, isPrepop, option){
                    mergeTags["custom"].tags.push({ tag: "{total_amount}", label: "Total Amount" });
                    return mergeTags;
                }
            </script>
            ';
            
            // Return the form object from the php hook
            return $form;
        }
    
        function clean_amount($entry) {
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
        
        function sendFailureEmail($entry, $form, $pfResponse, $sendTo) {
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
                if($key == "apikey" || $key == "apipass" || $key == "cardnumber") {
                    $val = "--REDACTED--";
                }
                $message .= $key . ": " . $val . "\r\n";
            }
            
            wp_mail($sendTo, "Profiler API Failure", $message, $headers);
        }
        
        function creditcard_mask($number) {
            // Returns a credit card with all but the last four numbers masked
            return substr($number, 0, 4) . str_repeat("X", strlen($number) - 8) . substr($number, -4);
        }
        
        function getClientIPAddress() {
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

        function enable_creditcard($is_enabled) {
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

    }
}
    
    
    
    
    
    
    
    
?>