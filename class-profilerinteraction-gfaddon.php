<?php

class GFProfilerInteraction extends GFProfilerCommon {
    protected $_slug = "profiler-interaction-gf";
    protected $_title = "Profiler / Gravity Forms - Interaction Integration Feed";
    protected $_short_title = "Profiler Interactions";
    protected $formid;
    protected $form;
    protected $gateways;
    protected static $_instance = null;

    protected $api_type = "json";
    protected $api_domain = "profilersoftware.com";
    protected $apifield_endpoint = "/ProfilerAPI/RapidEndpoint/";
    protected $apifield_apikey = "apiuser";
    protected $apifield_apipass = "apipassword";
    protected $apifield_ipaddress = 'requestIPAddress';
    protected $apifield_formurl = 'pageURL';
    protected $gffield_legacyname = "interaction";
    protected $supports_custom_fields = true;
    protected $supports_mailinglists = true;

    public static function get_instance() {
        if (self::$_instance == null) {
            self::$_instance = new GFProfilerInteraction();
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
        $selectfields = self::$_instance->selectFields();

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
            "label" => 'Client: Title',
            "type" => "select",
            "name" => "profilerinteraction_clienttitle",
            "required" => false,
            "choices" => $field_settings,
            "pf_apifield" => "title",
        );

        $fields[] = array(
            "label" => 'Client: First Name',
            "type" => "select",
            "name" => "profilerinteraction_clientfname",
            "required" => false,
            "choices" => $field_settings,
            "pf_apifield" => "firstname",
        );

        $fields[] = array(
            "label" => 'Client: Last Name',
            "type" => "select",
            "name" => "profilerinteraction_clientlname",
            "required" => false,
            "choices" => $field_settings,
            "pf_apifield" => "surname",
        );

        $fields[] = array(
            "label" => 'Client: Email',
            "type" => "select",
            "name" => "profilerinteraction_clientemail",
            "required" => false,
            "choices" => $field_settings,
            "pf_apifield" => "email",
        );

        $fields[] = array(
            "label" => 'Client: Address',
            "type" => "select",
            "name" => "profilerinteraction_clientaddress",
            "required" => false,
            "choices" => $field_settings,
            "pf_apifield" => "address",
        );

        $fields[] = array(
            "label" => 'Client: City',
            "type" => "select",
            "name" => "profilerinteraction_clientcity",
            "required" => false,
            "choices" => $field_settings,
            "pf_apifield" => "suburb",
        );

        $fields[] = array(
            "label" => 'Client: State',
            "type" => "select",
            "name" => "profilerinteraction_clientstate",
            "required" => false,
            "choices" => $field_settings,
            "pf_apifield" => "state",
        );

        $fields[] = array(
            "label" => 'Client: Zip/Postcode',
            "type" => "select",
            "name" => "profilerinteraction_clientpostcode",
            "required" => false,
            "choices" => $field_settings,
            "pf_apifield" => "postcode",
        );

        $fields[] = array(
            "label" => 'Client: Country',
            "type" => "select",
            "name" => "profilerinteraction_clientcountry",
            "required" => false,
            "choices" => $field_settings,
            "pf_apifield" => "country",
        );

        $fields[] = array(
            "label" => 'Client: Organisation',
            "type" => "select",
            "name" => "profilerinteraction_clientorganisation",
            "required" => false,
            "choices" => $field_settings,
            "pf_apifield" => "org",
        );

        $fields[] = array(
            "label" => 'Client: Home Phone',
            "type" => "select",
            "name" => "profilerinteraction_clientphoneah",
            "required" => false,
            "choices" => $field_settings,
            "pf_apifield" => "phoneAH",
            "auto_format" => "phone",
        );

        $fields[] = array(
            "label" => 'Client: Business Phone',
            "type" => "select",
            "name" => "profilerinteraction_clientphonebus",
            "required" => false,
            "choices" => $field_settings,
            "pf_apifield" => "phoneBus",
            "auto_format" => "phone",
        );

        $fields[] = array(
            "label" => 'Client: Mobile Phone',
            "type" => "select",
            "name" => "profilerinteraction_clientphonemobile",
            "required" => false,
            "choices" => $field_settings,
            "pf_apifield" => "phoneMobile",
            "auto_format" => "phone",
        );

        $fields[] = array(
            "label" => 'Client: Website',
            "type" => "select",
            "name" => "profilerinteraction_clientwebsite",
            "required" => false,
            "choices" => $field_settings,
            "pf_apifield" => "website",
        );

        $fields[] = array(
            "label" => 'Client Acquisition Field',
            "type" => "select",
            "name" => "profilerinteraction_clientacquisitioncode",
            "required" => false,
            "tooltip" => "This field's value should match the Client Acquisition Codes setup within Profiler.",
            "choices" => $field_settings,
            "pf_apifield" => "clientAcquiredReason",
        );

        $fields[] = array(
            "label" => 'Client Tags',
            "type" => "text",
            "name" => "profilerinteraction_clienttags",
            "required" => false,
            "class" => "merge-tag-support",
            "tooltip" => "This field's value should match the Client Tags setup within Profiler. This list can be comma-separated for multiple tags. Use merge fields here to dynamically set tags.",
        );

        $fields[] = array(
            "label" => 'Interaction Text',
            "type" => "textarea",
            "name" => "profilerinteraction_interactiontext",
            "required" => true,
            "class" => "merge-tag-support",
            "tooltip" => "This is the text to be sent to Profiler as an Interaction. Protip: Include Gravity Forms Merge Fields in this textarea to accept user input.",
        );

        $fields[] = array(
            "label" => 'Interaction Type ID',
            "type" => "select",
            "name" => "profilerinteraction_interactiontype",
            "tooltip" => 'Select a Interaction Type ID/Code from Profiler',
            "required" => false,
            "choices" => array_merge($hiddenFields, $checkboxRadioFields, $selectfields),
            "pf_apifield" => "interactionType",
        );

        $fields[] = array(
            "label" => 'Confidential?',
            "type" => "select",
            "name" => "profilerinteraction_confidential",
            "tooltip" => 'Should this interaction be marked as Confidential in Profiler? Values must be Y or N.',
            "required" => false,
            "choices" => array_merge($yesno_options, $hiddenFields, $checkboxRadioFields, $selectfields),
            "pf_apifield" => "interactionConfidential",
        );

        $fields[] = array(
            "label" => 'Alert?',
            "type" => "select",
            "name" => "profilerinteraction_alert",
            "tooltip" => 'Should this interaction be marked as an Alert in Profiler? Values must be Y or N.',
            "required" => false,
            "choices" => array_merge($yesno_options, $hiddenFields, $checkboxRadioFields, $selectfields),
            "pf_apifield" => "interactionAlert",
        );

        return $fields;
        
    }

    public function process_feed_custom($feed, $entry, $form, $postData, $fromValidatorProcessPFGateway = false) {

        $postData['method'] = "interaction";
        $postData['datatype'] = "INT";
        $postData['clientname'] = $postData['firstname'] . " " . $postData['surname'];

        // Interaction Text
        $postData['comments'] = GFCommon::replace_variables($feed['meta']['profilerinteraction_interactiontext'], $form, $entry, false, true, false, 'text');
        $postData['comments'] = html_entity_decode($postData['comments']);
        
        // Only allow ASCII printable characters.
        // This is a work-around to the API endpoint not allowing some characters
        $postData['comments'] = preg_replace('/[^\x20-\x7E]/','', $postData['comments']);

        // Confidential? Y/N
        if(strtolower($feed['meta']['profilerinteraction_confidential']) == "true") {
            $confidential = "Y";
        } else if(strtolower($feed['meta']['profilerinteraction_confidential']) == "false") {
            $confidential = "N";
        } else {
            $confidential = strtolower($this->get_field_value($form, $entry, $feed['meta']['profilerinteraction_confidential']));
        }

        if($confidential === true || $confidential == "y" || $confidential == "Y" || $confidential == "true" || $confidential == "yes") {
            $confidential = "Y";
        } else {
            $confidential = "N";
        }

        $postData['interactionConfidential'] = $confidential;

        // Alert? Y/N
        if(strtolower($feed['meta']['profilerinteraction_alert']) == "true") {
            $alert = "Y";
        } else if(strtolower($feed['meta']['profilerinteraction_alert']) == "false") {
            $alert = "N";
        } else {
            $alert = strtolower($this->get_field_value($form, $entry, $feed['meta']['profilerinteraction_alert']));
        }

        if($alert === true || $alert == "y" || $alert == "Y" || $alert == "true" || $alert == "yes") {
            $alert  = "Y";
        } else {
            $alert = "N";
        }

        $postData['interactionAlert'] = $alert;

        // Client Tags
        if(!empty($feed['meta']['profilerinteraction_clienttags'])) {
            // Comma separated tags. Can be merge fields.
            $postData['clientTag'] = trim(GFCommon::replace_variables($feed['meta']['profilerinteraction_clienttags'], $form, $entry, false, true, false, 'text'));
        }

        return $postData;
    }

    public function process_feed_success($feed, $entry, $form, $pfResponse, $postData) {

        if(!isset($pfResponse['dataArray']['status']) || $pfResponse['dataArray']['status'] != "Pass") {
            // Profiler failed. Send the failure email.
            $this->sendFailureEmail($entry, $form, $pfResponse, $feed['meta']['profiler_erroremailaddress']);

        } else {
            // Store the Integration ID as meta so we can use it later
            if(isset($pfResponse['dataArray']['id']))
                gform_add_meta($entry["id"], "profiler_integrationid", $pfResponse['dataArray']['id'], $form['id']);
        }

    }

}
