<?php

class GFProfilerMembership extends GFProfilerDonate {
    protected $_slug = "profiler-membership-gf";
    protected $_title = "Profiler / Gravity Forms - Membership Integration Feed";
    protected $_short_title = "Profiler Membership";
    protected $formid;
    protected $form;
    protected $gateways;
    protected static $_instance = null;

    protected $apifield_endpoint = "/ProfilerAPI/Legacy/";
    protected $apifield_apikey = "apikey";
    protected $apifield_apipass = "apipass";
    protected $apifield_ipaddress = 'udf';
    protected $apifield_formurl = true;
    protected $gffield_legacyname = "membership";

    public static function get_instance() {
        if (self::$_instance == null) {
            self::$_instance = new GFProfilerMembership();
        }

        self::$_instance->form = self::$_instance->get_current_form();
        self::$_instance->formid = self::$_instance->form["id"];

        return self::$_instance;
    }

    public function init() {
        parent::init();
    }

    public function process_feed_custom($feed, $entry, $form, $postData, $fromValidatorProcessPFGateway = false, $forceSendCard = false) {
        // Processes the feed and prepares to send it to Profiler
        // This can either do a gateway payment, or just an integration

        $postData = parent::process_feed_custom($feed, $entry, $form, $postData, $fromValidatorProcessPFGateway, true);

        if($feed['meta']['profilerdonation_useasgateway'] == "true" && $fromValidatorProcessPFGateway == true) {
            $useAsGateway = true;

        } elseif($feed['meta']['profilerdonation_useasgateway'] !== "true" && $fromValidatorProcessPFGateway == true) {
            // This shouldn't happen. Let's catch it just in case.
            return false;

        } else {
            $useAsGateway = false;

        }

        if($useAsGateway != true) {
            // Profiler processes this payment
            $postData['datatype'] = "MEM";
        }

        $postData['pledgeamount'] = $postData['amount'];

        return $postData;

    }

}
