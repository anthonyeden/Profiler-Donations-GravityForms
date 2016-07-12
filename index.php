<?php
/*
Plugin Name: Profiler Donations - Gravity Forms Add-On
Plugin URI: http://mediarealm.com.au/
Description: Integrates Gravity Forms with Profiler, enabling donation data to be sent directly to Profiler.
Version: 1.0.7

Author: Media Realm
Author URI: http://www.mediarealm.com.au/

License: GPL2

'Profiler Donation Gravity Forms' is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
'Profiler Donation Gravity Forms' is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with 'Profiler Donation Gravity Forms'. If not, see https://www.gnu.org/licenses/gpl-2.0.html.

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

// Code to update this plugin via GitHub
if (is_admin()) {
    require_once( 'BFIGitHubPluginUploader.php' );
    new BFIGitHubPluginUpdater(__FILE__, 'anthonyeden', "Profiler-Donations-GravityForms");
}