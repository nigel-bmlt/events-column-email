<?php
// Copyright (C) 2022 nigel.bmlt@gmail.com
// 
// This file is part of events-column-email.
// 
// events-column-email is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
// 
// events-column-email is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with events-column-email.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Plugin Name: Events Column Email
 * Plugin URI: Events Column Email
 * Description: Events Column Email
 * Version: 1.1
 * Requires at least: 5.2
 * Tested up to: 6.2
 * Author: @nigel-bmlt
 * Author URI: https://github.com/nigel-bmlt
 **/

if (!defined('ABSPATH')) exit; // die if being called directly

define('ECE_DEBUG',true);

if (!class_exists('ece_plugin')) {
    class ece_plugin
    {
        public function __construct()
        {
            // actions, shortcodes, menus and filters
            add_action('admin_menu', array(&$this, 'ece_menu_pages'));
            add_action('admin_enqueue_scripts', array(&$this, 'ece_admin_scripts'));
            add_action('admin_init',  array(&$this, 'ece_register_setting'));
            add_filter('plugin_action_links', array(&$this, 'ece_add_plugin_link'), 10, 2);
            add_filter( 'manage_tribe_events_posts_columns', array(&$this,'ece_filter_posts_columns') );
            add_filter( 'manage_tribe_events_posts_custom_column', array(&$this,'ece_filter_posts_custom_column'), 10,2 );
            add_action( 'admin_post_ece_post', array(&$this,'ece_post'));
            register_activation_hook(__FILE__, array(&$this, 'ece_install'));

        }


        public function ece_post() {
            error_log("got post postid {$_REQUEST['data']}");
            $postid = $_REQUEST['data'];
             
            $start_date = tribe_get_start_date($postid, true);
            $end_date = tribe_get_end_date($postid, true);
            $organizer = tribe_get_organizer($postid);
            $organizer_phone = tribe_get_organizer_phone($postid);
            $organizer_email = tribe_get_organizer_email($postid);
            $phone = tribe_get_phone($postid);
            $venue = tribe_get_venue($postid);
            $address= tribe_get_address($postid, true);
            $city = tribe_get_city($postid, true);
            $province = tribe_get_province($postid);
            $region = tribe_get_region($postid);
            $country = tribe_get_country($postid);

            $email = <<<EOT
Event details:
Start Date: {$start_date}
End Date: {$end_date}
Organizer: {$organizer}
Organizer Phone: {$organizer_phone}
Organizer Email: {$organizer_email}
Phone: {$phone}
Venue: {$venue}
Venue Address: {$address}
City: {$city}
Province: {$province}
Region: {$region}
Country: {$country}
EOT;
            $headers = array("From", get_option('ece_email_from_address'));
            wp_mail(get_option('ece_email_to_address'),'NA Requested Event Details',$email);
            $this->debug_log("sending email to ".get_option('ece_email_to_address'));
            $this->debug_log("sending email from ".get_option('ece_email_from_address'));
            $this->debug_log("email body: ");
            $this->debug_log($email);
        }

        public function ece_filter_posts_columns( $columns ) {
            $columns['email'] = 'Email';
            return $columns;
        }

        public function ece_filter_posts_custom_column( $column,$post_id ) {
            if($column === 'email')
            {
                echo '<button type="button" class="ece_email_button" data-adminurl="'.admin_url('admin-post.php').'" data-postid="'.$post_id.'">Email</button>';
                // echo '<a href="http://wordpress-php8-singlesite//wp-admin/admin-post.php?action=ece_post&data='.$post_id.'">Email</a>';
            }
        }

        public function debug_log($message)
        {
            if (ECE_DEBUG)
            {
                $out = print_r($message, true);
                error_log(debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] . ": " . $out);
            }
        }

        private function prevent_cache_enqueue_script($handle, $deps, $name)
        {

            $ret = wp_enqueue_script($handle, plugin_dir_url(__FILE__) . $name, $deps, filemtime(plugin_dir_path(__FILE__) . $name), true);
        }

        private function prevent_cache_enqueue_style($handle, $deps, $name)
        {

            $ret = wp_enqueue_style($handle, plugin_dir_url(__FILE__) . $name, $deps, filemtime(plugin_dir_path(__FILE__) . $name), 'all');
        }

        public function ece_admin_scripts($hook)
        {
            $this->debug_log("admin scripts");

            $this->debug_log($hook);

            $this->prevent_cache_enqueue_script('ecejs', array('jquery','wp-i18n'), 'js/ece_script.js');

            if ($hook != 'toplevel_page_ece-settings') {
                return;
            }

            switch ($hook) {

                case ('toplevel_page_ece-settings'):
                    // base css and scripts for this page
                    $this->prevent_cache_enqueue_style('ece-admin-css', false, 'css/admin_options.css');
                    $this->prevent_cache_enqueue_script('ece-admin-options-js', array('jquery','wp-i18n'), 'js/admin_options.js');

                    break;
            }
        }

        public function ece_menu_pages()
        {
            $toplevelslug = 'ece-settings';

            add_options_page('ece', 'ece', 'activate_plugins', $toplevelslug, array(
                &$this,
                'ece_plugin_page'
            ));
        }

        public function ece_plugin_page()
        {

            wp_nonce_field('wp_rest', '_wprestnonce');
            echo '<hr class="bmltwf-error-message">';
            echo '<form id="bmltwf_options_form" method="post" action="options.php">';
            echo '<h1>Events Column Email';
            
            settings_fields('ece-settings-group');
            do_settings_sections('ece-settings');
            
            submit_button();
            
            echo '</form></div>';        
        }

        public function ece_add_plugin_link($plugin_actions, $plugin_file)
        {

            $new_actions = array();
            if (basename(plugin_dir_path(__FILE__)) . '/events-column-email.php' === $plugin_file) {
                $new_actions['cl_settings'] = sprintf('<a href="%s">Settings</a>', esc_url(admin_url('admin.php?page=ece-settings')));
            }

            return array_merge($new_actions, $plugin_actions);
        }

        public function ece_register_setting()
        {

            if (!current_user_can('activate_plugins')) {
                return;
            }

            $this->debug_log("registering settings");

            register_setting(
                'ece-settings-group',
                'ece_email_from_address',
                array(
                    'type' => 'string',
                    'description' => 'From Address',
                    'sanitize_callback' => array(&$this, 'ece_email_from_address_sanitize_callback'),
                    'show_in_rest' => false,
                    'default' => 'example@example.com'
                )
            );

            register_setting(
                'ece-settings-group',
                'ece_email_to_address',
                array(
                    'type' => 'string',
                    'description' => 'To Address',
                    'sanitize_callback' => array(&$this, 'ece_email_to_address_sanitize_callback'),
                    'show_in_rest' => false,
                    'default' => 'example@example.com'
                )
            );

            add_settings_section(
                'ece-settings-section-id',
                '',
                '',
                'ece-settings'
            );

            add_settings_field(
                'ece_from_address',
                'From Address',
                array(&$this, 'ece_email_from_address_html'),
                'ece-settings',
                'ece-settings-section-id'
            );

            add_settings_field(
                'ece_bmlt_server_address',
                'To Address',
                array(&$this, 'ece_email_to_address_html'),
                'ece-settings',
                'ece-settings-section-id'
            );

        }

        public function ece_email_to_address_sanitize_callback($input)
        {
            $output = get_option('ece_email_from_address');
            $emails = explode(',',$input);
            foreach($emails as $i)
            {
                $sanitized_email = sanitize_email($i);
                if (!is_email($sanitized_email)) {
                    add_settings_error('ece_email_from_address', 'err', 'Invalid email address '.$i);
                    return $output;
                }
            }
            return implode(',',$emails);
        }

        public function ece_email_from_address_sanitize_callback($input)
        {
            $output = get_option('ece_email_to_address');
            $sanitized_email = sanitize_email($input);
            if (!is_email($sanitized_email)) {
                add_settings_error('ece_email_from_address', 'err','Invalid email from address.');
                return $output;
            }
            return $sanitized_email;
        }

        public function ece_email_from_address_html()
        {

            $from_address = get_option('ece_email_from_address');
            echo '<div class="ece_info_text">';
            echo '<br>';
            echo 'The sender (From:) address of the Events email';
            echo '<br><br>';
            echo '</div>';

            echo '<br><label for="ece_email_from_address"><b>From Address:</b></label><input id="ece_email_from_address" type="text" size="50" name="ece_email_from_address" value="' . esc_attr($from_address) . '"/>';
            echo '<br><br>';
        }

        public function ece_email_to_address_html()
        {

            $to_address = get_option('ece_email_to_address');
            echo '<div class="ece_info_text">';
            echo '<br>';
            echo 'The destination (To:) address of the Events email';
            echo '<br><br>';
            echo '</div>';

            echo '<br><label for="ece_email_to_address"><b>To Address:</b></label><input id="ece_email_to_address" type="text" size="50" name="ece_email_to_address" value="' . esc_attr($to_address) . '"/>';
            echo '<br><br>';
        }

        public function display_ece_admin_options_page()
        {
            include_once('admin/admin_options.php');
        }

        public function display_ece_admin_submissions_page()
        {
            include_once('admin/admin_submissions.php');
        }

        public function display_ece_admin_service_bodies_page()
        {
            include_once('admin/admin_service_bodies.php');
        }

        public function ece_install($networkwide)
        {
            global $wpdb;
            $this->debug_log("is_multisite = " . var_export(is_multisite(), true));
            $this->debug_log("is_plugin_active_for_network = " . var_export(is_plugin_active_for_network(__FILE__), true));
            $this->debug_log("networkwide = " . var_export($networkwide, true));
            if ((is_multisite()) && ($networkwide === true)) {
                // multi site and network activation, so iterate through all blogs
                $this->debug_log('Multisite Network Activation');
                $blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
                foreach ($blogids as $blog_id) {
                    $this->debug_log('Installing on blog id ' . $blog_id);
                    switch_to_blog($blog_id);
                    $this->ece_add_default_options();
                    restore_current_blog();
                }
            } else {
                $this->debug_log('Single Site Activation');
                $this->ece_add_default_options();
            }
        }

        private function ece_add_default_options()
        {
            // install all our default options (if they arent set already)
            add_option('ece_email_from_address', 'example@example.com');
            add_option('ece_email_to_address', 'example@example.com');

        }

    }

    $start_plugin = new ece_plugin();
}
