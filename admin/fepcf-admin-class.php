<?php

if (!class_exists('fepcf_admin_class'))
{
  class fepcf_admin_class
  {
	private static $instance;
	
	public static function init()
        {
            if(!self::$instance instanceof self) {
                self::$instance = new self;
            }
            return self::$instance;
        }
		
    function actions_filters()
    {
	add_action('admin_menu', array(&$this, 'MenuPage'));
	add_filter('plugin_action_links', array(&$this, 'add_settings_link'), 10, 2 );
    }



/******************************************ADMIN SETTINGS PAGE BEGIN******************************************/

    function MenuPage()
    {
	add_menu_page('FEP Contact Form', 'FEP Contact Form', 'manage_options', 'fepcf-admin-settings', array(&$this, 'admin_settings'),plugins_url( 'fep-contact-form/images/msgBox.gif' ));	
	
	add_submenu_page('fepcf-admin-settings', 'FEP Contact Form - ' .__('Settings','fepcf'), __('Settings','fepcf'), 'manage_options', 'fepcf-admin-settings', array(&$this, 'admin_settings'));
	
	add_submenu_page('fepcf-admin-settings', 'FEP Contact Form - ' .__('Department Settings','fepcf'), __('Department Settings','fepcf'), 'manage_options', 'fepcf-department-settings', array(&$this, 'department_settings'));
	
	
	add_submenu_page('fepcf-admin-settings', 'FEP Contact Form - ' .__('Instruction','fepcf'), __('Instruction','fepcf'), 'manage_options', 'fepcf-instruction', array(&$this, "InstructionPage"));
	
	//add_submenu_page('fepcf-admin-settings', 'FEP Contact Form - ' .__('Send Email','fepcf'), __('Send Email','fepcf'), 'manage_options', 'fepcf-send-email', array(&$this, "NewEmail"));
	
    }
	
	function department_settings()
    {
	  $url = 'https://shamimbiplob.wordpress.com/contact-us/';
	  $actionURL = admin_url( 'admin.php?page=fepcf-department-settings' );
	  $ReviewURL = 'https://wordpress.org/support/view/plugin-reviews/front-end-pm';
	  $capUrl = 'http://codex.wordpress.org/Roles_and_Capabilities';
	  
	  if(isset($_POST['fep-cf-department-save'])){ 
		$errors = $this->department_settings_action();
		if(count($errors->get_error_messages())>0){
			echo fepcf_error($errors);
		}
		else{
		echo'<div id="message" class="updated fade">' .__("Options successfully saved.", 'fepcf'). ' </div>';}}
		
	  $fepURL = admin_url( 'admin.php?page=fep-admin-settings' );
	  
	  $token = fep_create_nonce('department');
	  
	  wp_enqueue_script( 'fepcf-department-script' );
	  
	  $records = get_option('fep_cf_to_field');
      echo "<div id='poststuff'>

		<div id='post-body' class='metabox-holder columns-2'>

		<!-- main content -->
		<div id='post-body-content'>
		<div class='postbox'><div class='inside'>
          <h2>".__("FEP Contact Form Department Settings", "fep")."</h2>
		  <h5>".sprintf(__("If you like this plugin please <a href='%s' target='_blank'>Review in Wordpress.org</a> and give 5 star", "fep"),esc_url($ReviewURL))."</h5>
          <form method='post' action='$actionURL'>
	<table id='fepcf-options-table' class='widefat'>
	  <thead><tr><th>Department Name</th><th>Username</th><th>&nbsp;</th></tr></thead><tr><td><input type='button' class='fep_cf_add' value='Add More' /></td><td>(Username of person who will receive messages of that Department)</td><td></td></tr>";
	  if($records){
		 foreach($records as $key => $eachRecord){
		echo "	<tr>
				<td><input type='text'  pattern='.{3,}' required name='dp_name[]' value='".stripslashes($key)."'/></td>
				<td><input type='text' 	pattern='.{3,}' required name='dp_username[]' value='".$eachRecord."' /></td>                        
				<td><input type='button' class='fep_cf_del' value='Delete' /></td>
			</tr>";} } else { 
			echo "
			<tr>
				<td><input type='text'  pattern='.{3,}' required name='dp_name[]' value=''/></td>
				<td><input type='text'  pattern='.{3,}' required name='dp_username[]' value='' /></td>
				<td><input type='button' class='fep_cf_del' value='Delete' /></td>
			</tr>";
			} 
			echo "</table>";
			echo "<span><input class='button-primary' type='submit' name='fep-cf-department-save' value='".__("Save Options", "fep")."' /></span><input type='hidden' name='token' value='$token' />";
			echo "</form></div></div></div>
		  ". $this->fepcf_admin_sidebar(). "
		  </div></div>";
		
		}
	function department_settings_action()
    {
		if (isset($_POST['fep-cf-department-save'])){
		$errors = new WP_Error();
		
		if (!current_user_can('manage_options'))
			$errors->add('noPermission', __('No permission!', 'fepcf'));
			
		if ( !fep_verify_nonce($_POST['token'], 'department'))
			$errors->add('invalidToken', __('Sorry, your nonce did not verify!', 'fepcf'));
			
	  
	  $dp_name = str_replace(",", " ",$_POST['dp_name']); //make sure we don't get ,(comma) in department name
	  $dp_username = str_replace(array(',',' '),array('(comma)','(white-space)'),$_POST['dp_username']); //make sure we don't get (comma) and (white-space) in department username
	  foreach($dp_username as $wpuser){
		$wpuser = trim($wpuser);
			if(!username_exists($wpuser)){
			$errors->add('invalidUsername', sprintf(__('Username %s is invalid!', 'fepcf'), $wpuser));
				}
			}
			do_action('fepcf_department_settings_action', $errors);
			
		if(count($errors->get_error_codes())==0) {
	  $record = array_combine($dp_name , $dp_username);
	  
	  update_option('fep_cf_to_field', $record);
	  	}
		return $errors;
	  }
	}
    function admin_settings()
    {
	  $token = fep_create_nonce( 'fepcf-admin-settings' );
	  $url = 'https://shamimbiplob.wordpress.com/contact-us/';
	  $actionURL = admin_url( 'admin.php?page=fepcf-admin-settings' );
	  $ReviewURL = 'https://wordpress.org/support/view/plugin-reviews/front-end-pm';
	  
	  if(isset($_POST['fepcf-admin-settings-submit'])){ 
		$errors = $this->admin_settings_action();
		if(count($errors->get_error_messages())>0){
			echo fepcf_error($errors);
		}
		else{
		echo'<div id="message" class="updated fade">' .__("Options successfully saved.", 'fepcf'). ' </div>';}}
		echo "<div id='poststuff'>

		<div id='post-body' class='metabox-holder columns-2'>

		<!-- main content -->
		<div id='post-body-content'>
		<div class='postbox'><div class='inside'>
	  	  <h2>".__("FEP Contact Form Settings", 'fepcf')."</h2>
		  <h5>".sprintf(__("If you like this plugin please <a href='%s' target='_blank'>Review in Wordpress.org</a> and give 5 star", 'fepcf'),esc_url($ReviewURL))."</h5>
          <form method='post' action='$actionURL'>
          <table>
          <thead>
          <tr><th>".__("Setting", 'fepcf')."</th><th>".__("Value", 'fepcf')."</th></tr>
          </thead>
          <tr><td>".__("Messages to show per page", 'fepcf')."<br/><small>".__("Do not set this to 0!", 'fepcf')."</small></td><td><input type='text' name='messages_page' value='".fepcf_get_option('messages_page',15)."' /><br/> ".__("Default",'fepcf').": 15</td></tr>
		  <tr><td>".__("Custom CSS", 'fepcf')."<br /><small>".__("add or override", 'fepcf')."</small></td><td><TEXTAREA name='custom_css'>".trim(fepcf_get_option('custom_css'))."</TEXTAREA></td></tr>
		  
		  <tr><td>".__("Editor Type", 'fepcf')."<br /><small>".__("Admin alwayes have Wp Editor", 'fepcf')."</small></td><td><select name='editor_type'>
		  <option value='wp_editor' ".selected(fepcf_get_option('editor_type','teeny'), 'wp_editor',false).">Wp Editor</option>
		  <option value='teeny' ".selected(fepcf_get_option('editor_type','teeny'), 'teeny',false).">Wp Editor (Teeny)</option>
		  <option value='textarea' ".selected(fepcf_get_option('editor_type','teeny'), 'textarea',false).">Textarea</option></select></td></tr>";
		  
		  do_action('fepcf_admin_setting_form');
		  
		  echo "
		  <tr><td>".__("Block Username", 'fepcf')."<br /><small>".__("Separated by comma", 'fepcf')."</small></td><td><TEXTAREA name='have_permission'>".fepcf_get_option('have_permission')."</TEXTAREA></td></tr>
		  <tr><td>".__("Bad words", "fep")."<br /><small>".__("Separated by comma", "fep")."</small></td><td><TEXTAREA name='fep_cf_bad'>".fepcf_get_option('fep_cf_bad')."</TEXTAREA><br /><small>".__("It will match inside words, so \"press\" will match \"WordPress\"", "fep")."</small></td></tr>
		  <tr><td>".__("Email Footer", "fep")."<br /><small>".__("For sending email", "fep")."</small></td><td><TEXTAREA name='fep_cf_efoot'>".fepcf_get_option('fep_cf_efoot')."</TEXTAREA></td></tr>
		  <tr><td>".__("IP Blacklist", "fep")."<br /><small>".__("Separated by comma", "fep")."</small></td><td><TEXTAREA name='fep_ip_block'>".fepcf_get_option('fep_ip_block')."</TEXTAREA><br /><small>".__("You can use range and wildcard(e.g. 192.168.10-50.*)", "fep")."</small></td></tr>
		  <tr><td><input type='checkbox' name='email_blacklist_check' value='1' ".checked(fepcf_get_option('email_blacklist_check'), '1', false)." />".__("Email Blacklist", "cfp")."<br/><small>".__("Separated by comma.", "cfp")."</small></td><td><TEXTAREA name='email_blacklist'>".fepcf_get_option('email_blacklist')."</TEXTAREA><br /><small>".__("You can use wildcard. (e.g. *@badsite.com)", "fep")."</small></td></tr>
		  <tr><td><input type='checkbox' name='email_whitelist_check' value='1' ".checked(fepcf_get_option('email_whitelist_check'), '1', false)." />".__("Email Whitelist", "cfp")."<br/><small>".__("Separated by comma. (If both email blacklist and whitelist are checked, email whitelist will be used).", "cfp")."</small></td><td><TEXTAREA name='email_whitelist'>".fepcf_get_option('email_whitelist')."</TEXTAREA><br /><small>".__("You can use wildcard. (e.g. *@goodsite.com)", "fep")."</small></td></tr>
		  <tr><td>".__("Maximum points before mark as spam", "fep")."<br /></td><td><input type='text' name='fep_cf_point' value='".fepcf_get_option('fep_cf_point', 4)."' /><br /><small>".__("Default: 4", "fep")."</small></td></tr>
		  <tr><td>".__("Time delay between two messages send by a user via FEP Contact Form in minutes", "fep")."<br /></td><td><input type='text' name='cf_time_delay' value='".fepcf_get_option('cf_time_delay', 10)."' /><br /><small>".__("0 = No delay required", "fep")."</small></td></tr>
		  <tr><td colspan='2'><input type='checkbox' name='notify_email' value='1' ".checked(fepcf_get_option('notify_email'), '1', false)." /> ".__("Notify Department admin via email when new contact message send?", "fep")."</td></tr>
		  <tr><td colspan='2'><input type='checkbox' name='fep_cf_cap' value='1' ".checked(fepcf_get_option('fep_cf_cap'), '1', false)." /> ".__("Enable CAPTCHA?", "fep")."<br /><small>".__("Configure CAPTCHA below", "fep")."</small></td></tr>
		  <tr><td>".__("CAPTCHA Question", "fep")."<br /><small>".__("It will show on FEP Contact Form", "fep")."</small></td><td><input type='text' name='fep_cf_capqs' value='".fepcf_get_option('fep_cf_capqs')."' /></td></tr>
		  <tr><td>".__("CAPTCHA Answer", "fep")."<br /><small>".__("Have to be same answer to send contact message.", "fep")."</small></td><td><input type='text' name='fep_cf_capans' value='".fepcf_get_option('fep_cf_capans')."' /></td></tr>
		  <tr><td colspan='2'><input type='checkbox' name='fep_cf_logged' value='1' ".checked(fepcf_get_option('fep_cf_logged'), '1', false)." /> ".__("Require logged in to send contact message?", "fep")."</td></tr>
		  <tr><td colspan='2'><input type='checkbox' name='fep_cf_akismet' value='1' ".checked(fepcf_get_option('fep_cf_akismet'), '1', false)." /> ".__("Enable AKISMET check?", "fep")."<br /><small>".__("Need AKISMET plugin installed.", "fep")."</small></td></tr>
          <tr><td colspan='2'><input type='checkbox' name='hide_branding' value='1' ".checked(fepcf_get_option('hide_branding'), '1', false)." /> ".__("Hide Branding Footer?", 'fepcf')."</td></tr>
          <tr><td colspan='2'><span><input class='button-primary' type='submit' name='fepcf-admin-settings-submit' value='".__("Save Options", 'fepcf')."' /></span></td><td><input type='hidden' name='token' value='$token' /></td></tr>
          </table>
		  </form>
		  <ul>".sprintf(__("For paid support pleasse visit <a href='%s' target='_blank'>FEP Contact Form</a>", 'fepcf'),esc_url($url))."</ul>
          </div></div></div>
		  ". $this->fepcf_admin_sidebar(). "
		  </div></div>";
		  }

function fepcf_admin_sidebar()
	{
		return '<div id="postbox-container-1" class="postbox-container">


				<div class="postbox">
					<h3 class="hndle" style="text-align: center;">
						<span>'. __( "Plugin Author", "fepcf" ). '</span>
					</h3>

					<div class="inside">
						<div style="text-align: center; margin: auto">
						<strong>Shamim Hasan</strong><br />
						Know php, MySql, css, javascript, html. Expert in WordPress. <br /><br />
								
						You can hire for plugin customization, build custom plugin or any kind of wordpress job via <br> <a
								href="https://shamimbiplob.wordpress.com/contact-us/"><strong>Contact Form</strong></a>
					</div>
				</div>
			</div>
				</div>';
	}
		

    function admin_settings_action()
    {
      if (isset($_POST['fepcf-admin-settings-submit']))
      {
	  $errors = new WP_Error();
	  $options = $_POST;
	  
	  if( !current_user_can('manage_options'))
	  $errors->add('noPermission', __('No Permission!', 'fepcf'));
	  
	  if (!ctype_digit($options['messages_page']) || !ctype_digit($options['cf_time_delay']) || !ctype_digit($options['fep_cf_point']))
	  $errors->add('invalid_int', __('Message per page, Maximum points and Time delay fields support only positive numbers!', 'fepcf'));
	  
	  if ( !fep_verify_nonce($_POST['token'], 'fepcf-admin-settings'))
			$errors->add('invalidToken', __('Sorry, your nonce did not verify!', 'fepcf'));
	  
	  do_action('fepcf_action_admin_setting_before_save', $errors);
	  
	  $options = apply_filters('fepcf_filter_admin_setting_before_save',$options);
	  //var_dump($options);
		
		if (count($errors->get_error_codes())==0){
        update_option('FEPCF_admin_options', $options);
        }
		return $errors;
      }
      return false;
    }
	
	function InstructionPage()
	{
	$url = 'https://shamimbiplob.wordpress.com/contact-us/';
	echo '<div id="poststuff">

		<div id="post-body" class="metabox-holder columns-2">

		<!-- main content -->
		<div id="post-body-content">';
		
      echo 	"<div class='postbox'><div class='inside'>
          <h2>".__("FEP Contact Form Setup Instruction", 'fepcf')."</h2>
          <p><ul><li>".__("Create a new page/post.", 'fepcf')."</li>
          <li>".__("Paste following code for showing FEP Contact Form", 'fepcf')."<code>[fep-contact-form]</code></li>
		  <li>".__("Paste following code in a Page for showing FEP Contact Form Admin area", 'fepcf')."<code>[fep-contact-form-admin]</code></li>
          <li>".__("Publish the page/post.", 'fepcf')."</li><br />
		  <li>".sprintf(__("If you have <a href='%s' target='_blank'>Front End PM</a> installed you will find new admin section in Front End PM page in front end.", 'fepcf'),esc_url('https://wordpress.org/plugins/front-end-pm/'))."</li><br />
		  <li>".sprintf(__("For paid support pleasse visit <a href='%s' target='_blank'>FEP Contact Form</a>", 'fepcf'),esc_url($url))."</li>
          </ul></p></div></div></div>
		  ". $this->fepcf_admin_sidebar(). "
		  </div></div>";
		  }
	
	
function add_settings_link( $links, $file ) {
	//add settings link in plugins page
	$plugin_file = 'fep-contact-form/fep-contact-form.php';
	if ( $file == $plugin_file ) {
		$settings_link = '<a href="' . admin_url( 'admin.php?page=fepcf-admin-settings' ) . '">' .__( 'Settings', 'fepcf' ) . '</a>';
		array_unshift( $links, $settings_link );
	}
	return $links;
}
/******************************************ADMIN SETTINGS PAGE END******************************************/


  } //END CLASS
} //ENDIF

add_action('wp_loaded', array(fepcf_admin_class::init(), 'actions_filters'));
?>