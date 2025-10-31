<?php
// This is a legacy file to support credit card field processing in Gravity Forms for Profiler Donations.
// It works around some historic issues with how Gravity Forms's JS sends the credit card number field.
// Now days, this shouldn't be used - we recommend using Stripe or another modern gateway that handles card processing client-side.

function profilerdonation_creditcardfilter($field_content, $field) {
    if ($field->type == 'creditcard') {
        $field_content = str_replace(".1' id", ".1' class='gf_processcardnumber' id", $field_content);
    }
    
    return $field_content;
}

function profilerdonation_cardprocessscript($form) {
    // If Gravity Forms has a 'creditcard' field, we need to enqueue our card processing script
    $has_creditcard = false;
    foreach($form['fields'] as $field) {
        if($field->type == 'creditcard') {
            $has_creditcard = true;
            break;
        }
    }

    if(!$has_creditcard) {
        return;
    }

    wp_enqueue_script('profilerdonation_cardprocessscript', trailingslashit(plugin_dir_url(__FILE__)).'cardprocess.js');
}

add_filter('gform_field_content', 'profilerdonation_creditcardfilter', 10, 2);
add_action('gform_enqueue_scripts', 'profilerdonation_cardprocessscript', 10, 2);