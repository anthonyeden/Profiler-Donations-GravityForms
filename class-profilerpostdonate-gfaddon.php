<?php
if (class_exists("GFForms")) {
    
    GFForms::include_payment_addon_framework();
    
    class GFProfilerPostDonate extends GFFeedAddOn {
        protected $_min_gravityforms_version = "1.8.12";
        protected $_slug = "profiler-postdonation-gf";
        protected $_path = "profiler-donation-gf/index.php";
        protected $_full_path = __FILE__;
        protected $_url = "";
        protected $_title = "Profiler / Gravity Forms - Post-Donation Integration Feed";
        protected $_short_title = "Profiler Post-Donation";
        protected $formid;
        protected $form;
        protected $gateways;
        private static $_instance = null;
        
        public static function get_instance() {
            if (self::$_instance == null) {
                self::$_instance = new GFProfilerPostDonate();
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
                "label" => 'Profiler Server Address (RAPID)',
                "type" => "text",
                "name" => "profilerdonation_serveraddress",
                "required" => true,
                "tooltip" => "URL in this format: https://your_profiler_url/ProfilerPROG/api/api_oldon.cfm",
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
                "label" => 'Comments',
                "type" => "select",
                "name" => "profilerdonation_comments",
                "required" => false,
                "choices" => $field_settings,
            );

            $fields[] = array(
                "label" => 'UDF: Comments',
                "type" => "select",
                "name" => "profilerdonation_userdefined_comments",
                "required" => false,
                "tooltip" => "Pick the Profiler User Defined Field you wish to use for the Comments field",
                "choices" => $userdefinedfields,
            );

            $fields[] = array(
                "label" => 'Number of Mailing Lists',
                "type" => "select",
                "name" => "profilerdonation_mailinglist_count",
                "required" => false,
                "tooltip" => "Select a quantity of Mailing Lists, save this page, and then configure them. You may need to refresh this page after saving to see the extra fields.",
                "choices" => $mailingnumbers,
                "default" => 0,
            );

            for($i = 1; $i <= $feed['meta']['profilerdonation_mailinglist_count']; $i++) {
                // Loop over mailing list fields

                $fields[] = array(
                    "label" => 'Mailing List #'.$i.': UDF',
                    "type" => "select",
                    "name" => "profilerdonation_mailinglist_".$i."_udf",
                    "required" => false,
                    "tooltip" => "Pick the Profiler User Defined Field you wish to use for this mailing",
                    "choices" => $userdefinedfields,
                );

                $fields[] = array(
                    "label" => 'Mailing List #'.$i.': UDF Text',
                    "type" => "text",
                    "name" => "profilerdonation_mailinglist_".$i."_udftext",
                    "required" => false,
                    "tooltip" => "Enter the string Profiler is expecting in this UDF",
                );

                $fields[] = array(
                    "label" => 'Mailing List #'.$i.': Field',
                    "type" => "select",
                    "name" => "profilerdonation_mailinglist_".$i."_field",
                    "tooltip" => 'Link it to a checkbox field - when checked, the mailing will be sent',
                    "required" => false,
                    "choices" => $checkboxRadioFields
                );
            }

            $fields[] = array(
                "label" => 'Existing Profiler Integeration ID',
                "type" => "select",
                "name" => "profilerdonation_profilerid",
                "tooltip" => 'Link it to a Hidden field that will hold the existing Profiler Integeration ID',
                "required" => false,
                "choices" => $hiddenFields
            );

            $fields[] = array(
                "label" => 'Existing GF Entry ID',
                "type" => "select",
                "name" => "profilerdonation_gfentryid",
                "tooltip" => 'Link it to a Hidden field that will hold the existing Gravity Forms Entry ID',
                "required" => false,
                "choices" => $hiddenFields
            );

            $fields[] = array(
                "label" => 'User-Submitted Token',
                "type" => "select",
                "name" => "profilerdonation_token",
                "tooltip" => 'Link it to a Hidden field that will hold the existing generated token',
                "required" => false,
                "choices" => $hiddenFields
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
            
            $postData['DB'] = $feed['meta']['profilerdonation_dbname'];
            $postData['Call'] = "submit";
            $postData['api_user'] = $feed['meta']['profilerdonation_apikey'];
            $postData['api_pass'] = $feed['meta']['profilerdonation_apipass'];

            // Profiler will just record integration data
            $postData['method'] = "integration.send";
            $postData['datatype'] = "OLDON";

            // Only allow ASCII printable characters.
            // This is a work-around to the API endpoint not allowing some characters
            $comments = preg_replace('/[^\x20-\x7E]/','', $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_comments']));

            // Comments
            $postData['comments'] = $comments;
            $postData['userdefined' . $feed['meta']['profilerdonation_userdefined_comments']] = $comments;
            
            $gfEntryId = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_gfentryid']);
            $pfIntegrationId = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_profilerid']);
            $token = $this->get_field_value($form, $entry, $feed['meta']['profilerdonation_token']);

            $token_required = md5(password_hash($pfIntegrationId . "/" . $gfEntryId, PASSWORD_DEFAULT, array('salt' => NONCE_SALT)));

            if($token !== $token_required) {
                // Don't allow entries older than 1hr
                $entry[$feed['meta']['profilerdonation_logs']] = "Invalid security token was provided!";
                GFAPI::update_entry($entry);
                return false;
            }

            $originalEntryTime = GFAPI::get_entry($gfEntryId)['date_created'];

            if(strtotime($originalEntryTime) <= time() - 3600) {
                // Don't allow entries older than 1hr
                $entry[$feed['meta']['profilerdonation_logs']] = "Original Gravity Forms entry is too old - it was created " . $originalEntryTime;
                GFAPI::update_entry($entry);
                return false;
            }

            // This is the ID of the actual donation entry
            $postData['HoldingID'] = $pfIntegrationId;

            // Calculate mailing list subscriptions
            for($i = 1; $i <= $feed['meta']['profilerdonation_mailinglist_count']; $i++) {
                // Loop over mailing list fields
                $mailingFieldValue = $this->get_field_value($form, $entry, $feed['meta']["profilerdonation_mailinglist_".$i."_field"]);
                $udf = $feed['meta']["profilerdonation_mailinglist_".$i."_udf"];
                $udfText = $feed['meta']["profilerdonation_mailinglist_".$i."_udftext"];

                if(!empty($udf) && !empty($udfText) && !empty($mailingFieldValue)) {
                    $postData['userdefined' . $udf] = $udfText;
                }

            }

            // Send data to Profiler
            $pfResponse = $this->sendDataToProfiler($feed['meta']['profilerdonation_serveraddress'], $postData, $feed['meta']['profilerdonation_sslmode']);
            
            // Save Profiler response data back to the form entry
            $logsToStore = json_encode($pfResponse);
            $logsToStore = str_replace($postData['cardnumber'], "--REDACTED--", $logsToStore);
            $logsToStore = str_replace($postData['apikey'], "--REDACTED--", $logsToStore);
            $logsToStore = str_replace($postData['apipass'], "--REDACTED--", $logsToStore);
            $entry[$feed['meta']['profilerdonation_logs']] = htmlentities($logsToStore);
            GFAPI::update_entry($entry);
            
            if(!isset($pfResponse['dataArray']['dataset']['method']) || $pfResponse['dataArray']['dataset']['method'] != "Update") {
                // Profiler failed. Send the failure email.
                $this->sendFailureEmail($entry, $form, $pfResponse, $feed['meta']['profilerdonation_erroremailaddress']);
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
            $message = "--- PROFILER POST-DONATION DATA FAILURE #" . $form["id"] . "/" . $entry["id"] . " ---" . "\n\n";
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
            
            wp_mail($sendTo, "Profiler API Failure (Post-Donation)", $message, $headers);
        }

    }
}
    
    
    
    
    
    
    
    
?>