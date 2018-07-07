<?php
if (class_exists("GFForms")) {
    
    GFForms::include_payment_addon_framework();
    
    class GFProfilerInteraction extends GFFeedAddOn {
        protected $_min_gravityforms_version = "1.8.12";
        protected $_slug = "profiler-interaction-gf";
        protected $_path = "profiler-interaction-gf/index.php";
        protected $_full_path = __FILE__;
        protected $_url = "";
        protected $_title = "Profiler / Gravity Forms - Interaction Integration Feed";
        protected $_short_title = "Profiler Interactions";
        protected $formid;
        protected $form;
        protected $gateways;
        private static $_instance = null;
        
        public static function get_instance() {
            if (self::$_instance == null) {
                self::$_instance = new GFProfilerInteraction();
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
            
            $field_settings = self::$_instance->formFields();
            $hiddenFields = self::$_instance->hiddenFields();
            $userdefinedfields = self::$_instance->userDefinedFields();
            $selectfields = self::$_instance->selectFields();
            $checkboxradiofields = self::$_instance->checkboxRadioFields();
            $yesno_options = array(
                array(
                    "value" => "",
                    "label" => ""
                ),
                array(
                    "value" => "false",
                    "label" => "False"
                ),
                array(
                    "value" => "true",
                    "label" => "True"
                )
            );
            
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
                "label" => 'Profiler Instance Name',
                "type" => "text",
                "name" => "profilerinteraction_instancename",
                "required" => true,
                "tooltip" => "Your Instance Name can be found in your login URL: https://<instance>.profiler.net.au/",
            );
            
            $fields[] = array(
                "label" => 'Profiler Database Name',
                "type" => "text",
                "name" => "profilerinteraction_dbname",
                "required" => true,
            );
            
            $fields[] = array(
                "label" => 'Profiler API Key',
                "type" => "text",
                "name" => "profilerinteraction_apikey",
                "required" => true,
            );
            
            $fields[] = array(
                "label" => 'Profiler API Password',
                "type" => "text",
                "name" => "profilerinteraction_apipass",
                "required" => true,
            );
            
            $fields[] = array(
                "label" => 'Profiler Errors Email Address',
                "type" => "text",
                "name" => "profilerinteraction_erroremailaddress",
                "required" => false,
            );
                        
            $fields[] = array(
                "label" => 'Client: Title',
                "type" => "select",
                "name" => "profilerinteraction_clienttitle",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: First Name',
                "type" => "select",
                "name" => "profilerinteraction_clientfname",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: Last Name',
                "type" => "select",
                "name" => "profilerinteraction_clientlname",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: Email',
                "type" => "select",
                "name" => "profilerinteraction_clientemail",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: Address',
                "type" => "select",
                "name" => "profilerinteraction_clientaddress",
                "required" => false,
                "choices" => $field_settings
            );

            $fields[] = array(
                "label" => 'Client: City',
                "type" => "select",
                "name" => "profilerinteraction_clientcity",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: State',
                "type" => "select",
                "name" => "profilerinteraction_clientstate",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: Zip/Postcode',
                "type" => "select",
                "name" => "profilerinteraction_clientpostcode",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: Country',
                "type" => "select",
                "name" => "profilerinteraction_clientcountry",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: Organisation',
                "type" => "select",
                "name" => "profilerinteraction_clientorganisation",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: Home Phone',
                "type" => "select",
                "name" => "profilerinteraction_clientphoneah",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: Business Phone',
                "type" => "select",
                "name" => "profilerinteraction_clientphonebus",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: Mobile Phone',
                "type" => "select",
                "name" => "profilerinteraction_clientphonemobile",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Client: Website',
                "type" => "select",
                "name" => "profilerinteraction_clientwebsite",
                "required" => false,
                "choices" => $field_settings
            );
            
            $fields[] = array(
                "label" => 'Interaction Text',
                "type" => "textarea",
                "name" => "profilerinteraction_interactiontext",
                "required" => true,
                "class" => "merge-tag-support",
                "tooltip" => "This is the text to be sent to Profiler as an Interaction. Protip: Include Gravity Forms Merge Fields in this textarea to accept user input."
            );
            
            $fields[] = array(
                "label" => 'UDF: Interaction Type ID',
                "type" => "select",
                "name" => "profilerinteraction_userdefined_interactiontype",
                "required" => false,
                "tooltip" => "Pick the Profiler User Defined Field you wish the Interaction Type ID/Code to be sent to",
                "choices" => $userdefinedfields,
            );

            $fields[] = array(
                "label" => 'Interaction Type ID',
                "type" => "select",
                "name" => "profilerinteraction_interactiontype",
                "tooltip" => 'Select a Interaction Type ID/Code from Profiler',
                "required" => false,
                "choices" => $hiddenFields + $checkboxradiofields + $selectfields,
            );
            
            $fields[] = array(
                "label" => 'UDF: Confidential?',
                "type" => "select",
                "name" => "profilerinteraction_userdefined_confidential",
                "required" => false,
                "tooltip" => "Pick the Profiler User Defined Field you wish the Confidential Status to be sent to",
                "choices" => $userdefinedfields,
            );

            $fields[] = array(
                "label" => 'Confidential?',
                "type" => "select",
                "name" => "profilerinteraction_confidential",
                "tooltip" => 'Should this interaction be marked as Confidential in Profiler? Values must be Y or N.',
                "required" => false,
                "choices" => array_merge($yesno_options, $hiddenFields, $checkboxradiofields, $selectfields),
            );
            
            $fields[] = array(
                "label" => 'UDF: Alert?',
                "type" => "select",
                "name" => "profilerinteraction_userdefined_alert",
                "required" => false,
                "tooltip" => "Pick the Profiler User Defined Field you wish the Alert Status to be sent to",
                "choices" => $userdefinedfields,
            );

            $fields[] = array(
                "label" => 'Alert?',
                "type" => "select",
                "name" => "profilerinteraction_alert",
                "tooltip" => 'Should this interaction be marked as an Alert in Profiler? Values must be Y or N.',
                "required" => false,
                "choices" => array_merge($yesno_options, $hiddenFields, $checkboxradiofields, $selectfields),
            );
            
            $fields[] = array(
                "label" => 'UDF: Client IP Address',
                "type" => "select",
                "name" => "profilerinteraction_userdefined_clientip",
                "required" => false,
                "tooltip" => "Pick the Profiler User Defined Field you wish the client's IP address to be sent to",
                "choices" => $userdefinedfields,
            );

            $fields[] = array(
                "label" => 'UDF: Form URL',
                "type" => "select",
                "name" => "profilerinteraction_userdefined_formurl",
                "required" => false,
                "tooltip" => "Pick the Profiler User Defined Field you wish the donation's form's URL to be sent to.",
                "choices" => $userdefinedfields,
            );
            
            $fields[] = array(
                "label" => 'Profiler Logs',
                "type" => "select",
                "name" => "profilerinteraction_logs",
                "tooltip" => 'Link it to a Hidden field that will hold Profiler Response Logs',
                "required" => false,
                "choices" => $hiddenFields
            );
            
            $fields[] = array(
                "label" => 'SSL Mode',
                "type" => "select",
                "name" => "profilerinteraction_sslmode",
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
                    "title" => "Profiler Interaction Feed Settings",
                    "fields" => $fields
                )
            );
            
        }
        
        public function feed_list_columns() {
            // Returns columns to feed index page
            return array(
                'feedName'  => 'Name',
                'profilerinteraction_dbname' => 'PF Database Name',
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

        protected function selectFields() {
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
                if ($field['type'] == 'select') {
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
            // Processes the feed and prepares to send it to Profiler as an Interaction

            $form_id = $form["id"];
            $settings = $this->get_form_settings($form);
            
            // All the POST data for Profiler gets stored in this variable
            $postData = array();
            
            $postData['method'] = "interaction";
            $postData['DB'] = $feed['meta']['profilerinteraction_dbname'];
            $postData['datatype'] = "INT";
            $postData['apikey'] = $feed['meta']['profilerinteraction_apikey'];
            $postData['apipass'] = $feed['meta']['profilerinteraction_apipass'];

            $API_URL = "https://" . $feed['meta']['profilerinteraction_instancename'] . ".profiler.net.au/ProfilerPROG/api/v2/interaction/";

            // Client Fields:
            $postData['title'] = $this->get_field_value($form, $entry, $feed['meta']['profilerinteraction_clienttitle']);
            $postData['surname'] = $this->get_field_value($form, $entry, $feed['meta']['profilerinteraction_clientlname']);
            $postData['firstname'] = $this->get_field_value($form, $entry, $feed['meta']['profilerinteraction_clientfname']);
            $postData['clientname'] = $postData['firstname'] . " " . $postData['surname'];
            $postData['org'] = $this->get_field_value($form, $entry, $feed['meta']['profilerinteraction_clientorganisation']);
            $postData['address'] = $this->get_field_value($form, $entry, $feed['meta']['profilerinteraction_clientaddress']);
            $postData['suburb'] = $this->get_field_value($form, $entry, $feed['meta']['profilerinteraction_clientcity']);
            $postData['state'] = $this->get_field_value($form, $entry, $feed['meta']['profilerinteraction_clientstate']);
            $postData['postcode'] = $this->get_field_value($form, $entry, $feed['meta']['profilerinteraction_clientpostcode']);
            $postData['country'] = $this->get_country_name($this->get_field_value($form, $entry, $feed['meta']['profilerinteraction_clientcountry']));
            $postData['phoneah'] = $this->get_field_value($form, $entry, $feed['meta']['profilerinteraction_clientphoneah']);
            $postData['phonebus'] = $this->get_field_value($form, $entry, $feed['meta']['profilerinteraction_clientphonebus']);
            $postData['phonemobile'] = $this->get_field_value($form, $entry, $feed['meta']['profilerinteraction_clientphonemobile']);
            $postData['email'] = $this->get_field_value($form, $entry, $feed['meta']['profilerinteraction_clientemail']);
            $postData['website'] = $this->get_field_value($form, $entry, $feed['meta']['profilerinteraction_clientwebsite']);
            
            // Interaction Text
            $postData['comments'] = GFCommon::replace_variables($feed['meta']['profilerinteraction_interactiontext'], $form, $entry, false, true, false, 'text');

            // Interaction Type ID
            $postData['userdefined' . $feed['meta']['profilerinteraction_userdefined_interactiontype']] = $this->get_field_value($form, $entry, $feed['meta']['profilerinteraction_interactiontype']);

            // Confidential? Y/N
            if(strtolower($feed['meta']['profilerinteraction_confidential']) == "true") {
                $confidential = "Y";
            } else if(strtolower($feed['meta']['profilerinteraction_iconfidential']) == "false") {
                $confidential = "N";
            } else {
                $confidential = strtolower($this->get_field_value($form, $entry, $feed['meta']['profilerinteraction_confidential']));
            }

            if($confidential === true || $confidential == "y" || $confidential == "true" || $confidential == "yes") {
                $confidential = "Y";
            } else {
                $confidential = "N";
            }

            $postData['userdefined' . $feed['meta']['profilerinteraction_userdefined_confidential']] = $confidential;
            
            // Alert? Y/N
            if(strtolower($feed['meta']['profilerinteraction_alert']) == "true") {
                $alert = "Y";
            } else if(strtolower($feed['meta']['profilerinteraction_alert']) == "false") {
                $alert = "N";
            } else {
                $alert = strtolower($this->get_field_value($form, $entry, $feed['meta']['profilerinteraction_alert']));
            }

            if($alert === true || $alert == "y" || $alert == "true" || $alert == "yes") {
                $alert  = "Y";
            } else {
                $alert = "N";
            }

            $postData['userdefined' . $feed['meta']['profilerinteraction_userdefined_alert']] = $alert;
            


            if($feed['meta']['profilerinteraction_userdefined_clientip'] !== "") {
                // Client's IP Address
                $postData['userdefined' . $feed['meta']['profilerinteraction_userdefined_clientip']] = $this->getClientIPAddress();
            }
            
            if($feed['meta']['profilerinteraction_userdefined_formurl'] !== "" && isset($entry['source_url'])) {
                // The URL the form has been embedded on
                $postData['userdefined' . $feed['meta']['profilerinteraction_userdefined_formurl']] = $entry['source_url'];
            }
            
            // Send the data to Profiler:
            $pfResponse = $this->sendDataToProfiler($API_URL, $postData, $feed['meta']['profilerinteraction_sslmode']);
            
            // Save Profiler response data back to the form entry
            $logsToStore = json_encode($pfResponse);
            $logsToStore = str_replace($postData['apikey'], "--REDACTED--", $logsToStore);
            $logsToStore = str_replace($postData['apipass'], "--REDACTED--", $logsToStore);
            $entry[$feed['meta']['profilerinteraction_logs']] = htmlentities($logsToStore);
            GFAPI::update_entry($entry);
            
            if(!isset($pfResponse['dataArray']['status']) || $pfResponse['dataArray']['status'] != "Pass") {
                // Profiler failed. Send the failure email.
                $this->sendFailureEmail($entry, $form, $pfResponse, $feed['meta']['profilerinteraction_erroremailaddress']);
            }
            
        }
        
        protected function sendDataToProfiler($url, $profiler_query, $ssl_mode = "normal") {
            // Sends the donation and client data to Profiler via POST

            // Remove whitespace
            foreach($profiler_query as $key => $val) {
                $profiler_query[$key] = trim($val);
            }

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
                if($key == "apikey" || $key == "apipass") {
                    $val = "--REDACTED--";
                }
                $message .= $key . ": " . $val . "\r\n";
            }
            
            wp_mail($sendTo, "Profiler API Failure", $message, $headers);
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
