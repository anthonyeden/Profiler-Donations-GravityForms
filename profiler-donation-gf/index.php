<?php
/*
Plugin Name: Profiler Donations - Gravity Forms Add-On
Plugin URI: http://mediarealm.com.au/
Description: Integrates Gravity Forms with Profiler, enabling donation data to be sent directly to Profiler.
Version: 1.0.6
Author: Media Realm
Author URI: http://www.mediarealm.com.au/
*/

add_action('gform_loaded', array('ProfilerDonation_GF_Launch', 'ProfilerDonation_load'), 5);

class ProfilerDonation_GF_Launch {
    public static function ProfilerDonation_load() {
        
        if (!method_exists('GFForms', 'include_payment_addon_framework')) {
            return;
        }
        
        require_once('class-profilerdonate-gfaddon.php');
        GFAddOn::register('GFProfilerDonate');
        
        require_once('shortcodes.php');
        require_once('states_australia.php');
        require_once('cardprocess.php');
        
        if(isset($_POST['gform_submit'])) {
            // If we're receiving a Gravity Form submission, ensure the session is started
            // We use sessions to communicate with shortcodes
            
            if(!session_id()) {
                session_start();
            }
        }
    }
}