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
            add_filter('gform_replace_merge_tags', array($this, 'mergeTag_totalAmount'), 10, 7);
            add_action('gform_admin_pre_render', array($this, 'addMergeTags'));
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
                "label" => 'Profiler Server Address',
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
        
        protected function formFields() {
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
                        $field_settings['label'] = $inputvalue['label'];
                        $formfields[] = $field_settings;
                    }
                } elseif($field["type"] != "creditcard") {
                    // Process all fields except credit cards - we don't want them in the list
                    $field_settings = array();
                    $field_settings['value'] = $field['id'];
                    $field_settings['label'] = $field['label'];
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
        
        public function process_feed($feed, $entry, $form) {
            // Processes the feed and prepares to send it to Profiler
            
            $form_id = $form["id"];
            $settings = $this->get_form_settings($form);
            
            // All the POST data for Profiler gets stored in this variable
            $postData = array();
            
            $postData['DB'] = $feed['meta']['profilerdonation_dbname'];
            $postData['method'] = "integration.send";
            $postData['apikey'] = $feed['meta']['profilerdonation_apikey'];
            $postData['apipass'] = $feed['meta']['profilerdonation_apipass'];
            $postData['datatype'] = "OLDON";
            
            // Calculate the total or just use one field:
            if($feed['meta']['profilerdonation_amount'] == "total") {
                $postData['donationamount'] = $this->getTotalAmount($entry);
                $postData['pledgeamount'] = $this->getTotalAmount($entry);
            } else {
                $postData['donationamount'] = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_amount']);
                $postData['pledgeamount'] = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_amount']);
            }
            
            // Client Fields:
            $postData['title'] = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_clienttitle']);
            $postData['surname'] = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_clientlname']);
            $postData['firstname'] = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_clientfname']);
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
            
            // This feed only processes on success - so we assume an approved transaction
            $postData['status'] = "Approved";
            
            // Comments
            $postData['comments'] = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_comments']);
            $postData['userdefined2'] = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_comments']);
            
            // User Defined Fields:
            
            if($feed['meta']['profilerdonation_userdefined_sourcecode'] !== "") {
                // Donation Source Code
                $postData['userdefined' . $feed['meta']['profilerdonation_userdefined_sourcecode']] = $this->getDonationCode($feed, 'sourcecode');
            }
            
            if($feed['meta']['profilerdonation_userdefined_pledgesourcecode'] !== "") {
                // Pledge Source Code
                $postData['userdefined' . $feed['meta']['profilerdonation_userdefined_pledgesourcecode']] = $this->getDonationCode($feed, 'pledgesourcecode');
            }
            
            if($feed['meta']['profilerdonation_userdefined_pledgeacquisitioncode'] !== "") {
                // Pledge Acqusition code
                $postData['userdefined' . $feed['meta']['profilerdonation_userdefined_pledgeacquisitioncode']] = $this->getDonationCode($feed, 'pledgeacquisitioncode');
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
                // Recurring recurring donation
                $postData['pledgetype'] = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_pledgefreq']);
                
            } else {
                // Once-off donation
                unset($postData['cardnumber']);
                unset($postData['cardexpiry']);
                unset($postData['cardtype']);
                unset($postData['pledgeamount']);
                unset($postData['userdefined' . $feed['meta']['profilerdonation_userdefined_pledgeacquisitioncode']]);
                unset($postData['userdefined' . $feed['meta']['profilerdonation_userdefined_pledgesourcecode']]);
                
            }
            
            // Make the response to the Profiler server with our integration data
            $pfResponse = $this->sendDataToProfiler($feed['meta']['profilerdonation_serveraddress'], $postData);
            
            
            // Save Profiler response data back to the form entry
            $logsToStore = json_encode($pfResponse);
            $logsToStore = str_replace($postData['cardnumber'], "--REDACTED--", $logsToStore);
            $logsToStore = str_replace($postData['apikey'], "--REDACTED--", $logsToStore);
            $logsToStore = str_replace($postData['apipass'], "--REDACTED--", $logsToStore);
            $entry[$feed['meta']['profilerdonation_logs']] = htmlentities($logsToStore);
            GFAPI::update_entry($entry);
            
            if($pfResponse['dataArray']['status'] != "Pass") {
                // Profiler failed. Send the failure email.
                $this->sendFailureEmail($entry, $form, $pfResponse, $feed['meta']['profilerdonation_erroremailaddress']);
            }
            
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
        
        protected function sendDataToProfiler($url, $profiler_query) {
            // Sends the donation and client data to Profiler via POST
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Length: ' . strlen(http_build_query($profiler_query))));
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($profiler_query));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $result = curl_exec($ch);
            $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return array(
                "httpstatus" => $status_code,
                "dataSent" => $profiler_query,
                "data" => $result,
                "dataXML" => simplexml_load_string($result),
                "dataArray" => json_decode(json_encode((array)simplexml_load_string($result)), 1),
            );
        }
        
        public function getTotalAmount($entry) {
            // Returns the total amount as a float
            
            $form = RGFormsModel::get_forms_by_id($entry['form_id']);
            $total = 0;
            
            foreach ($form[0]["fields"] as $key => $field) {
                if ($field['type'] == 'product') {
                    $total += $this->clean_amount(rgpost('input_' . $field['id'])) / 100;
                    
                } elseif ($field['type'] == 'profilerdonate') {
                    $total += $this->clean_amount(rgpost('input_' . $field['id'].'.1')) / 100;
                    
                }
            }
            
            return $total;
            
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
                    $total = $this->getTotalAmount($entry);
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

    }
}
    
    
    
    
    
    
    
    
?>