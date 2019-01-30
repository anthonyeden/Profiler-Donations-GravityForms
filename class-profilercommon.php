<?php

class GFProfilerCommon extends GFFeedAddOn {
    protected $_path = "profiler-donation-gf/index.php";
    protected $_full_path = __FILE__;
    protected $_url = "";
    protected $_title = "Profiler / Gravity Forms - Integration Feed";

    protected $apifield_apikey = "apikey";
    protected $apifield_apipass = "apipass";
    protected $apifield_endpoint = "";
    protected $apifield_ipaddress = false;
    protected $apifield_formurl = false;
    protected $gffield_legacyname = "";

    public function init() {
        parent::init();
    }

    public function feed_settings_fields() {
        // This function adds all the feed setting fields we need to communicate with Profiler
        
        // Get lists of the various types of fields
        $field_settings = $this->formFields();
        $hiddenFields = $this->hiddenFields();
        $checkboxRadioFields = $this->checkboxRadioFields();
        $userdefinedfields = $this->userDefinedFields();
        
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
            "tooltip" => "Your Instance Domain Name can be found in your login URL: e.g. 'https://instance.profiler.net.au/' is 'instance.profiler.net.au'",
        );
        
        $fields[] = array(
            "label" => 'Profiler Database Name',
            "type" => "text",
            "name" => "profiler".$this->gffield_legacyname."_dbname",
            "required" => true,
        );
        
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

        $fields[] = array(
            "label" => 'Profiler Logs',
            "type" => "select",
            "name" => "profiler".$this->gffield_legacyname."_logs",
            "tooltip" => 'Link it to a Hidden field that will hold Profiler Response Logs',
            "required" => false,
            "choices" => $hiddenFields
        );
        
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

        return array(
            array(
                "title" => "Profiler Integration Settings",
                "fields" => $fields
            )
        );
        
    }

    public function process_feed($feed, $entry, $form) {
        // Processes the feed and prepares to send it to Profiler

        $form_id = $form["id"];
        $settings = $this->get_form_settings($form);
        
        // All the POST data for Profiler gets stored in this variable
        $postData = array();

        $postData['DB'] = $feed['meta']['profiler'.$this->gffield_legacyname.'_dbname'];
        $postData[$this->apifield_apikey] = $feed['meta']['profiler'.$this->gffield_legacyname.'_apikey'];
        $postData[$this->apifield_apipass] = $feed['meta']['profiler'.$this->gffield_legacyname.'_apipass'];

        if(empty($feed['meta']['profiler'.$this->gffield_legacyname.'_instancedomainname']) && !empty($feed['meta']['profiler'.$this->gffield_legacyname.'_instancename'])) {
            // Respect the setting from when we only accepted the first part of the domain name
            $feed['meta']['profiler'.$this->gffield_legacyname.'_instancedomainname'] = $feed['meta']['profiler'.$this->gffield_legacyname.'_instancename'] . ".profiler.net.au";
        }

        // Build the URL for this API call
        $API_URL = "https://" . $feed['meta']['profiler'.$this->gffield_legacyname.'_instancedomainname'] . $this->apifield_endpoint;

        // Work out GF/API field mappings
        $fields = $this->feed_settings_fields()[0]['fields'];

        foreach($fields as $field) {
            if(isset($field['pf_apifield']) && $this->get_field_value($form, $entry, $feed['meta'][$field['name']]) != '') {
                $postData[$field['pf_apifield']] = trim($this->get_field_value($form, $entry, $feed['meta'][$field['name']]));
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

        if($this->apifield_formurl === true && !empty($feed['meta']['profiler_userdefined_clientip'])) {
            $postData['userdefined' . $feed['meta']['profiler'.$this->gffield_legacyname.'_userdefined_clientip']] = $entry['source_url'];
        }

        // Allow filtering this via the child class
        if(method_exists($this, 'process_feed_custom')) {
            $postData = $this->process_feed_custom($feed, $entry, $form, $postData);
        }

        // Send data to Profiler
        $pfResponse = $this->sendDataToProfiler($API_URL, $postData, $feed['meta']['profiler_sslmode']);
        
        // Save Profiler response data back to the form entry
        $logsToStore = json_encode($pfResponse);
        $logsToStore = str_replace($postData['cardnumber'], "--REDACTED--", $logsToStore);
        $logsToStore = str_replace($postData[$this->apifield_apikey], "--REDACTED--", $logsToStore);
        $logsToStore = str_replace($postData[$this->apifield_apipass], "--REDACTED--", $logsToStore);
        $entry[$feed['meta']['profiler'.$this->gffield_legacyname.'_logs']] = htmlentities($logsToStore);
        GFAPI::update_entry($entry);

        if(method_exists($this, 'process_feed_success')) {
            $this->process_feed_success($feed, $entry, $form, $pfResponse);
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
            if($key == "apikey" || $key == "apipass" || $key == "cardnumber" || $key == "api_user" || $key == "api_pass" || $key == $this->apifield_apikey || $key == $this->apifield_apipass) {
                $val = "--REDACTED--";
            }
            $message .= $key . ": " . $val . "\r\n";
        }

        wp_mail($sendTo, "Profiler API Failure", $message, $headers);
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

}

?>