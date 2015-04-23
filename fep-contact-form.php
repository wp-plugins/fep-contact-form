<?php
/*
Plugin Name: FEP Contact Form
Plugin URI: https://shamimbiplob.wordpress.com/contact-us/
Description: FEP Contact Form is a secure contact form to your WordPress site.This can be used with Front End PM or without.
Version: 3.2
Author: Shamim
Author URI: https://shamimbiplob.wordpress.com/contact-us/
Text Domain: fepcf
License: GPLv2 or later
*/
//DEFINE
global $wpdb;
define('FEPCF_PLUGIN_DIR',plugin_dir_path( __FILE__ ));
define('FEPCF_PLUGIN_URL',plugins_url().'/fep-contact-form/');

if ( !defined ('FEP_MESSAGES_TABLE' ) )
define('FEP_MESSAGES_TABLE',$wpdb->prefix.'fep_messages');

if ( !defined ('FEP_META_TABLE' ) )
define('FEP_META_TABLE',$wpdb->prefix.'fep_meta');

if ( !defined ('FEP_DB_VERSION' ) )
define('FEP_DB_VERSION', 3.1 );

if ( !defined ('FEP_META_VERSION' ) )
define('FEP_META_VERSION', 3.1);

require_once('functions.php');

	//ACTIVATE PLUGIN
	register_activation_hook(__FILE__ , 'fepcf_plugin_activate');
	register_activation_hook(__FILE__ , 'fepcf_activate_option_save');


	//ADD ACTIONS
	add_action('after_setup_theme', 'fepcf_include_require_files');
	add_action('plugins_loaded', 'fepcf_translation');
	add_action('wp_enqueue_scripts', 'fepcf_enqueue_scripts');
	add_action('admin_enqueue_scripts', 'fepcf_admin_enqueue_scripts');

