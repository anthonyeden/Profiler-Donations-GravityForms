<?php
if (class_exists("GFForms")) {
    
    GFForms::include_payment_addon_framework();
    
    class GFProfilerLists extends GFFeedAddOn {
        protected $_min_gravityforms_version = "1.8.12";
        protected $_slug = "profiler-lists-gf";
        protected $_path = "profiler-donation-gf/index.php";
        protected $_full_path = __FILE__;
        protected $_url = "";
        protected $_title = "Profiler / Gravity Forms - List Integration Feed";
        protected $_short_title = "Profiler Mailing Lists";
        protected $formid;
        protected $form;
        protected $gateways;
        private static $_instance = null;
        
        public static function get_instance() {
            if (self::$_instance == null) {
                self::$_instance = new GFProfilerLists();
            }
            
            self::$_instance->form = self::$_instance->get_current_form();
            self::$_instance->formid = self::$_instance->form["id"];
            
            return self::$_instance;
        }

        public function init() {
            parent::init();
        }

        public function feed_settings_fields() {
            // This function adds all the feed setting fields we need to communicate with Profiler
            $form = $this->get_current_form();
            $feed = $this->get_current_feed();

            $field_settings = self::$_instance->formFields();
            $hiddenFields = self::$_instance->hiddenFields();
            $checkboxRadioFields = self::$_instance->checkboxRadioFields();
            $userdefinedfields = self::$_instance->userDefinedFields();

            $mailingnumbers = array();
            for($i = 0; $i <= 99; $i++) {
                $mailingnumbers[] = array(
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
                "name" => "profilerlist_instancedomainname",
                "required" => true,
                "tooltip" => "Your Instance Domain Name can be found in your login URL: e.g. 'https://instance.profiler.net.au/' is 'instance.profiler.net.au'",
            );
            
            $fields[] = array(
                "label" => 'Profiler Database Name',
                "type" => "text",
                "name" => "profilerlist_dbname",
                "required" => true,
            );
            
            $fields[] = array(
                "label" => 'Profiler API Key',
                "type" => "text",
                "name" => "profilerlist_apikey",
                "required" => true,
            );
            
            $fields[] = array(
                "label" => 'Profiler API Password',
                "type" => "text",
                "name" => "profilerlist_apipass",
                "required" => true,
            );

            $fields[] = array(
                "label" => 'Profiler Errors Email Address',
                "type" => "text",
                "name" => "profilerlist_erroremailaddress",
                "required" => false,
            );

            $fields[] = array(
                "label" => 'Client: Title',
                "type" => "select",
                "name" => "profilerlist_clienttitle",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: First Name',
                "type" => "select",
                "name" => "profilerlist_clientfname",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: Last Name',
                "type" => "select",
                "name" => "profilerlist_clientlname",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: Email',
                "type" => "select",
                "name" => "profilerlist_clientemail",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: Address',
                "type" => "select",
                "name" => "profilerlist_clientaddress",
                "required" => false,
                "choices" => $field_settings
            );

            $fields[] = array(
                "label" => 'Client: City',
                "type" => "select",
                "name" => "profilerlist_clientcity",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: State',
                "type" => "select",
                "name" => "profilerlist_clientstate",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: Zip/Postcode',
                "type" => "select",
                "name" => "profilerlist_clientpostcode",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: Country',
                "type" => "select",
                "name" => "profilerlist_clientcountry",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: Organisation',
                "type" => "select",
                "name" => "profilerlist_clientorganisation",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: Home Phone',
                "type" => "select",
                "name" => "profilerlist_clientphoneah",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: Business Phone',
                "type" => "select",
                "name" => "profilerlist_clientphonebus",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: Mobile Phone',
                "type" => "select",
                "name" => "profilerlist_clientphonemobile",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: Website',
                "type" => "select",
                "name" => "profilerlist_clientwebsite",
                "required" => false,
                "choices" => $field_settings
            );

            $fields[] = array(
                "label" => 'Number of Mailing Lists',
                "type" => "select",
                "name" => "profilerlist_mailinglist_count",
                "required" => false,
                "tooltip" => "Select a quantity of Mailing Lists, save this page, and then configure them. You may need to refresh this page after saving to see the extra fields.",
                "choices" => $mailingnumbers,
                "default" => 0,
            );

            for($i = 1; $i <= $feed['meta']['profilerlist_mailinglist_count']; $i++) {
                // Loop over mailing list fields

                $fields[] = array(
                    "label" => 'Mailing List #'.$i.': UDF',
                    "type" => "select",
                    "name" => "profilerlist_mailinglist_".$i."_udf",
                    "required" => false,
                    "tooltip" => "Pick the Profiler User Defined Field you wish to use for this mailing",
                    "choices" => $userdefinedfields,
                );

                $fields[] = array(
                    "label" => 'Mailing List #'.$i.': UDF Text',
                    "type" => "text",
                    "name" => "profilerlist_mailinglist_".$i."_udftext",
                    "required" => false,
                    "tooltip" => "Enter the string Profiler is expecting in this UDF",
                );

                $fields[] = array(
                    "label" => 'Mailing List #'.$i.': Field',
                    "type" => "select",
                    "name" => "profilerlist_mailinglist_".$i."_field",
                    "tooltip" => 'Link it to a checkbox field - when checked, the mailing will be sent',
                    "required" => false,
                    "choices" => array_merge($checkboxRadioFields, array(array("value" => "always", "label" => "Always Subscribe"))),
                );
            }

            $fields[] = array(
                "label" => 'UDF: Client IP Address',
                "type" => "select",
                "name" => "profilerlist_userdefined_clientip",
                "required" => false,
                "tooltip" => "Pick the Profiler User Defined Field you wish the client's IP address to be sent to",
                "choices" => $userdefinedfields,
            );

            $fields[] = array(
                "label" => 'UDF: Form URL',
                "type" => "select",
                "name" => "profilerlist_userdefined_formurl",
                "required" => false,
                "tooltip" => "Pick the Profiler User Defined Field you wish the donation's form's URL to be sent to.",
                "choices" => $userdefinedfields,
            );
            
            $fields[] = array(
                "label" => 'Profiler Logs',
                "type" => "select",
                "name" => "profilerlist_logs",
                "tooltip" => 'Link it to a Hidden field that will hold Profiler Response Logs',
                "required" => false,
                "choices" => $hiddenFields
            );
            
            $fields[] = array(
                "label" => 'SSL Mode',
                "type" => "select",
                "name" => "profilerlist_sslmode",
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
                'profilerlist_dbname' => 'PF Database Name',
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
        
        public function process_feed($feed, $entry, $form, $fromValidatorProcessPFGateway = false) {
            // Processes the feed and prepares to send it to Profiler

            $form_id = $form["id"];
            $settings = $this->get_form_settings($form);
            
            // All the POST data for Profiler gets stored in this variable
            $postData = array();
            
            $postData['DB'] = $feed['meta']['profilerlist_dbname'];
            $postData['apikey'] = $feed['meta']['profilerlist_apikey'];
            $postData['apipass'] = $feed['meta']['profilerlist_apipass'];

            // Profiler will just record integration data
            $postData['method'] = "integration.send";
            $postData['datatype'] = "LISTS";

            // Build the URL for this API call
            $API_URL = "https://" . $feed['meta']['profilerlist_instancedomainname'] . "/ProfilerPROG/api/api_call.cfm";
            
            // Calculate mailing list subscriptions
            for($i = 1; $i <= $feed['meta']['profilerlist_mailinglist_count']; $i++) {
                // Loop over mailing list fields
                $mailingFieldValue = $this->get_field_value($form, $entry, $feed['meta']["profilerlist_mailinglist_".$i."_field"]);
                $udf = $feed['meta']["profilerlist_mailinglist_".$i."_udf"];
                $udfText = $feed['meta']["profilerlist_mailinglist_".$i."_udftext"];

                if(!empty($udf) && !empty($udfText) && (!empty($mailingFieldValue) || $feed['meta']["profilerlist_mailinglist_".$i."_field"] == "always")) {
                    $postData['userdefined' . $udf] = $udfText;
                }

            }

            // Client Fields:
            $postData['title'] = $this->get_field_value($form, $entry, $feed['meta']['profilerlist_clienttitle']);
            $postData['surname'] = $this->get_field_value($form, $entry, $feed['meta']['profilerlist_clientlname']);
            $postData['firstname'] = $this->get_field_value($form, $entry, $feed['meta']['profilerlist_clientfname']);
            $postData['clientname'] = $postData['firstname'] . " " . $postData['surname'];
            $postData['org'] = $this->get_field_value($form, $entry, $feed['meta']['profilerlist_clientorganisation']);
            $postData['address'] = $this->get_field_value($form, $entry, $feed['meta']['profilerlist_clientaddress']);
            $postData['suburb'] = $this->get_field_value($form, $entry, $feed['meta']['profilerlist_clientcity']);
            $postData['state'] = $this->get_field_value($form, $entry, $feed['meta']['profilerlist_clientstate']);
            $postData['postcode'] = $this->get_field_value($form, $entry, $feed['meta']['profilerlist_clientpostcode']);
            $postData['country'] = $this->get_country_name($this->get_field_value($form, $entry, $feed['meta']['profilerlist_clientcountry']));
            $postData['phoneah'] = $this->get_field_value($form, $entry, $feed['meta']['profilerlist_clientphoneah']);
            $postData['phonebus'] = $this->get_field_value($form, $entry, $feed['meta']['profilerlist_clientphonebus']);
            $postData['phonemobile'] = $this->get_field_value($form, $entry, $feed['meta']['profilerlist_clientphonemobile']);
            $postData['email'] = $this->get_field_value($form, $entry, $feed['meta']['profilerlist_clientemail']);
            $postData['website'] = $this->get_field_value($form, $entry, $feed['meta']['profilerlist_clientwebsite']);

            // Send data to Profiler
            $pfResponse = $this->sendDataToProfiler($API_URL, $postData, $feed['meta']['profilerlist_sslmode']);
            
            // Save Profiler response data back to the form entry
            $logsToStore = json_encode($pfResponse);
            $logsToStore = str_replace($postData['cardnumber'], "--REDACTED--", $logsToStore);
            $logsToStore = str_replace($postData['apikey'], "--REDACTED--", $logsToStore);
            $logsToStore = str_replace($postData['apipass'], "--REDACTED--", $logsToStore);
            $entry[$feed['meta']['profilerlist_logs']] = htmlentities($logsToStore);
            GFAPI::update_entry($entry);
            
            if(!isset($pfResponse['dataArray']['status']) || $pfResponse['dataArray']['status'] != "Pass") {
                // Profiler failed. Send the failure email.
                $this->sendFailureEmail($entry, $form, $pfResponse, $feed['meta']['profilerlist_erroremailaddress']);
            }

            // Store the Integration ID as meta so we can use it later
            if(isset($pfResponse['dataArray']['dataset']['id']))
                gform_add_meta($entry["id"], "profiler_integrationid", $pfResponse['dataArray']['dataset']['id'], $form_id);
            
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
        
        protected function sendDataToProfiler($url, $profiler_query, $ssl_mode = "normal") {
            // Sends the donation and client data to Profiler via POST

            // Remove whitespace
            foreach($profiler_query as $key => $val) {
                $profiler_query[$key] = trim($val);
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query(array("DB" => $profiler_query['DB'], "Call" => 'submit')));
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
        
        function sendFailureEmail($entry, $form, $pfResponse, $sendTo) {
            // Sends an alert email if integration with Profiler failed
            
            if(!isset($pfResponse['dataArray']['error'])) {
                $pfResponse['dataArray']['error'] = "";
            }

            $headers = '';
            $message = "--- PROFILER MAILING LIST DATA FAILURE #" . $form["id"] . "/" . $entry["id"] . " ---" . "\n\n";
            $message .= "Gravity Form #" . $form["id"] . " with Entry ID #" . $entry["id"] . " failed to be sent to the Profiler API.\r\n";
            $message .= "HTTP Status Code: " . $pfResponse['httpstatus'] . "\r\n";
            $message .= "Profiler Error Message: " . $pfResponse['dataArray']['error'] . "\r\n";
            $message .= "\r\n\r\n";
            $message .= "This is the data that was sent to the Profiler API:\r\n";
            
            foreach($pfResponse['dataSent'] as $key => $val) {
                if($key == "apikey" || $key == "apipass" || $key == "cardnumber" || $key == "api_user" || $key == "api_pass") {
                    $val = "--REDACTED--";
                }
                $message .= $key . ": " . $val . "\r\n";
            }
            
            wp_mail($sendTo, "Profiler API Failure (Mailing Lists RAPID)", $message, $headers);
        }

        private function get_country_name($country_code) {
            $countries = GF_Fields::get('address')->get_countries();
            foreach($countries as $key => $val) {
                if(strtoupper($key) == strtoupper($country_code)) {
                    return $val;
                }
            }
    
            // Code not found, fall back to the supplied code...
            return $country_code;
        }

    }
}
    
?>