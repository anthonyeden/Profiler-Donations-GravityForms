<?php

class GFProfilerLists extends GFProfilerCommon {
    protected $_slug = "profiler-lists-gf";
    protected $_title = "Profiler / Gravity Forms - Mailing Lists (Advanced) Integration Feed";
    protected $_short_title = "Profiler Mailing Lists (Advanced)";
    protected $formid;
    protected $form;
    protected $gateways;
    protected static $_instance = null;

    protected $api_type = "json";
    protected $api_domain = "profilersoftware.com";
    protected $apifield_endpoint = "/ProfilerAPI/SubscribeList/";
    protected $apifield_apikey = "apiuser";
    protected $apifield_apipass = "apipassword";
    protected $apifield_ipaddress = 'requestIPAddress';
    protected $apifield_formurl = 'pageURL';

    protected $supports_custom_fields = true;
    protected $supports_mailinglists = true;

    public static function get_instance() {
        if (self::$_instance == null) {
            self::$_instance = new GFProfilerLists();
        }

        self::$_instance->form = self::$_instance->get_current_form();
        if(is_array(self::$_instance->form)) {
            self::$_instance->formid = self::$_instance->form["id"];
        }

        return self::$_instance;
    }

    public function init() {
        parent::init();
    }
    
    public function feed_settings_fields_custom() {
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
            "label" => 'Client: Title',
            "type" => "select",
            "name" => "profilerlist_clienttitle",
            "required" => false,
            "choices" => $field_settings,
            "pf_apifield" => "title",
        );
        
        $fields[] = array(
            "label" => 'Client: First Name',
            "type" => "select",
            "name" => "profilerlist_clientfname",
            "required" => false,
            "choices" => $field_settings,
            "pf_apifield" => "firstname",
        );
        
        $fields[] = array(
            "label" => 'Client: Last Name',
            "type" => "select",
            "name" => "profilerlist_clientlname",
            "required" => false,
            "choices" => $field_settings,
            "pf_apifield" => "surname",
        );
        
        $fields[] = array(
            "label" => 'Client: Email',
            "type" => "select",
            "name" => "profilerlist_clientemail",
            "required" => false,
            "choices" => $field_settings,
            "pf_apifield" => "email",
        );
        
        $fields[] = array(
            "label" => 'Client: Address',
            "type" => "select",
            "name" => "profilerlist_clientaddress",
            "required" => false,
            "choices" => $field_settings,
            "pf_apifield" => "address",
        );

        $fields[] = array(
            "label" => 'Client: City',
            "type" => "select",
            "name" => "profilerlist_clientcity",
            "required" => false,
            "choices" => $field_settings,
            "pf_apifield" => "suburb",
        );
        
        $fields[] = array(
            "label" => 'Client: State',
            "type" => "select",
            "name" => "profilerlist_clientstate",
            "required" => false,
            "choices" => $field_settings,
            "pf_apifield" => "state",
        );
        
        $fields[] = array(
            "label" => 'Client: Zip/Postcode',
            "type" => "select",
            "name" => "profilerlist_clientpostcode",
            "required" => false,
            "choices" => $field_settings,
            "pf_apifield" => "postcode",
        );
        
        $fields[] = array(
            "label" => 'Client: Country',
            "type" => "select",
            "name" => "profilerlist_clientcountry",
            "required" => false,
            "choices" => $field_settings,
            "pf_apifield" => "country",
        );
        
        $fields[] = array(
            "label" => 'Client: Organisation',
            "type" => "select",
            "name" => "profilerlist_clientorganisation",
            "required" => false,
            "choices" => $field_settings,
            "pf_apifield" => "org",
        );
        
        $fields[] = array(
            "label" => 'Client: Home Phone',
            "type" => "select",
            "name" => "profilerlist_clientphoneah",
            "required" => false,
            "choices" => $field_settings,
            "pf_apifield" => "phoneAH",
            "auto_format" => "phone",
        );
        
        $fields[] = array(
            "label" => 'Client: Business Phone',
            "type" => "select",
            "name" => "profilerlist_clientphonebus",
            "required" => false,
            "choices" => $field_settings,
            "pf_apifield" => "phoneBus",
            "auto_format" => "phone",
        );
        
        $fields[] = array(
            "label" => 'Client: Mobile Phone',
            "type" => "select",
            "name" => "profilerlist_clientphonemobile",
            "required" => false,
            "choices" => $field_settings,
            "pf_apifield" => "phoneMobile",
            "auto_format" => "phone",
        );
        
        $fields[] = array(
            "label" => 'Client: Website',
            "type" => "select",
            "name" => "profilerlist_clientwebsite",
            "required" => false,
            "choices" => $field_settings,
            "pf_apifield" => "website",
        );

        return $fields;
        
    }

    public function process_feed_success($feed, $entry, $form, $pfResponse, $postData) {
        if(isset($pfResponse['dataArray']['success']) && $pfResponse['dataArray']['success'] === true) {
            // Store the Integration ID as meta so we can use it later
            if(isset($pfResponse['dataArray']['integrationId'])) {
                gform_add_meta($entry["id"], "profiler_integrationid", $pfResponse['dataArray']['integrationId'], $form['id']);
                gform_add_meta($entry["id"], "profiler_integration_guid", $pfResponse['dataArray']['integrationGuid'], $form['id']);
            }
        } else {
            // Profiler failed. Send the failure email.
            $this->sendFailureEmail($entry, $form, $pfResponse, $feed['meta']["profiler_erroremailaddress"]);
        }
    }

}

?>