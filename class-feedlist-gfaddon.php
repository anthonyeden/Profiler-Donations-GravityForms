<?php

GFForms::include_addon_framework();

class GF_Profiler_FeedList extends GFAddOn {

    protected $_version = "1.0.0";
    protected $_min_gravityforms_version = "2.6.0";
    protected $_slug = 'gravityforms_profilerfeedlist';
    protected $_path = 'profiler-donations-gravityforms/class-feedlist-gfaddon.php';
    protected $_full_path = __FILE__;
    protected $_title = 'Gravity Forms Profiler Feed List';
    protected $_short_title = 'Profiler Feed List';
    protected $_capabilities = array( 'gravityforms_edit_settings' );
    protected $_capabilities_form_settings = 'gravityforms_edit_settings';
    protected $_capabilities_settings_page = 'gravityforms_edit_settings';

    private static $_instance = null;

    public static function get_instance() {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function init() {
        parent::init();
    }

    public function get_menu_icon() {
		//return 'dashicons-admin-generic';
        return plugins_url('icon-profiler.png', __FILE__);
	}

    public function plugin_settings_fields() {
        // Global settings for this plugin (API Connection Parameters, etc.)

        $sections = array(
            array(
                'title'  => 'Profiler Feeds',
                'description' => 'This section lists all the Profiler feeds on your site.',
                'capability' => 'gravityforms_edit_settings',
                'fields' => array(
                    array(
                        'name'       => 'feeds',
                        'label'      => 'Feeds',
                        'type'       => 'feeds',
                    ),
                ),
            ),
        );

        return $sections;
    }

    public function settings_feeds() {
        // Our Feeds Field type
        // Just renders HTML on the page

        $feeds = GFAPI::get_feeds();

        $feed_types = array(
            'profiler-donation-gf' => 'Donations',
            'profiler-events-gf' => 'Events',
            'profiler-interaction-gf' => 'Interactions',
            'profiler-lists-gf' => 'Mailing Lists (Advanced)',
            'profiler-listsbasic-gf' => 'Mailing Lists (Basic)',
            'profiler-membership-gf' => 'Membership',
            'profiler-postdonation-gf' => 'Post-Donate',
            'profiler-update-gf' => 'Update Details',
        );

        $legacy_field_names = array(
            'profiler-donation-gf' => 'donation',
            'profiler-events-gf' => '',
            'profiler-interaction-gf' => 'interaction',
            'profiler-lists-gf' => '',
            'profiler-listsbasic-gf' => '',
            'profiler-membership-gf' => 'donation',
            'profiler-postdonation-gf' => 'donation',
            'profiler-update-gf' => 'update',
        );

        $html = '<table class="widefat">
        <thead>
            <tr>
                <th>Form ID</th>
                <th>Form Name</th>
                <th>Feed ID</th>
                <th>Feed Name</th>
                <th>Feed Type</th>
                <th>Status</th>
                <th>Domain Name</th>
                <th>API Key</th>
            </tr>
        </thead>';

        $found_feeds = false;

        foreach($feeds as $feed) {

            if(strpos($feed['addon_slug'], "profiler-") === false) {
                continue;
            }

            $form = GFAPI::get_form($feed['form_id']);

            $found_feeds = true;

            $html .= '
            <tr>
                <td>'.esc_html($feed['form_id']).'</td>
                <td><a href="'.esc_url('admin.php?page=gf_edit_forms&id=' . $feed['form_id']).'">'.esc_html($form['title']).'</a></td>
                <td>'.esc_html($feed['id']).'</td>
                <td><a href="'.esc_url('admin.php?subview='.$feed['addon_slug'].'&page=gf_edit_forms&id=3&view=settings&fid=' . $feed['id']).'">'.esc_html($feed['meta']['feedName']).'</a></td>
                <td>'.esc_html($feed_types[$feed['addon_slug']]).'</td>
                <td>'.($feed['is_active'] == true ? 'Active' : 'Inactive').'</td>
                <td>'.esc_html($feed['meta']['profiler' . $legacy_field_names[$feed['addon_slug']] . '_instancedomainname']).'</td>
                <td>'.esc_html(substr($feed['meta']['profiler' . $legacy_field_names[$feed['addon_slug']] . '_apikey'], 0, 5) . '...').'</td>
            </tr>';
        }

        if($found_feeds === false) {
            $html .= '
            <tr>
                <td colspan="8">You do not have any Profiler Feeds. Please go to a form and create a feed.</td>
            </tr>';
        }

        $html .= '</table>';

        return $html;
    }

}