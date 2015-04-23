<?php

if (!class_exists('fepcf_main_class'))
{
  class fepcf_main_class
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
	//ADD SHORTCODES
	add_shortcode('fep-contact-form', array(&$this, 'contact_form' )); //for FEP Contact Form
	//add_action('admin_menu', array(&$this, 'MenuPage'), 99);
	//add_filter('plugin_action_links', array(&$this, 'add_settings_link'), 10, 2 );
    }
	
	/******************************************CONTACT FORM BEGIN******************************************/
	
  function contact_form(){
	$html = '<h2>' . __('Send Message', 'fepcf') . '</h2>';
	if(isset($_POST['contact_message'])!=''){
		$errors = $this->checkContact();
		if(count($errors->get_error_messages())>0){
			$html .= fepcf_error($errors);
			$html .= $this->fep_contact_form();
		}
		else{
			$html .= '<div id="fep-success">' .__("Message successfully send.", "fep"). ' </div>';
		}
	}
	else{
		$html .= $this->fep_contact_form();
	}
	return $html;
}
	
	    function fep_contact_form()
    {
      global $user_ID, $user_login;
	  $token = fep_create_nonce('fepcf-message');
	  
		if (fepcf_is_user_blocked())
		{
		return '<div id="fep-error">' .__("You cannot send messages because you are blocked by administrator!", "fep"). ' </div>';
      }
      if (is_user_logged_in() || fepcf_get_option('fep_cf_logged') != '1')
      {
	  //get department names and usernames of those departments users
	  $records = get_option('fep_cf_to_field');
	  
	  $message_to = ( isset( $_REQUEST['message_to'] ) ) ? esc_html($_REQUEST['message_to']): '';
	  $message_from = ( isset( $_REQUEST['message_from'] ) ) ? esc_html($_REQUEST['message_from']): '';
	  $message_email = ( isset( $_REQUEST['message_email'] ) ) ? esc_html($_REQUEST['message_email']): '';
	  $message_title = ( isset( $_REQUEST['message_title'] ) ) ? esc_html($_REQUEST['message_title']): '';
	  $message_content = ( isset( $_REQUEST['message_content'] ) ) ? esc_textarea($_REQUEST['message_content']): '';
	
		$newMsg = "<form name='message' action='' method='post' enctype='multipart/form-data'>";
        $newMsg .= __("Department", "fep")."<font color='red'>*</font>: <br />";
		if($records){
		 foreach($records as $key=>$eachRecord){
		 if ( $eachRecord.','.stripslashes($key) == $message_to){$check='checked';} else {$check='';}
		$newMsg .="<label><input type='radio' name='message_to' value='$eachRecord,".stripslashes($key)."' $check/> ".stripslashes($key)."</label><br />";}
		} else {
		$newMsg .=__("Please add departments from FEP contact form settings in backend.","fep")."<br />";}
		
		$newMsg .= __("Name", "fep")."<font color='red'>*</font>: <br />";
		if (is_user_logged_in()) {
		$newMsg .= fepcf_get_userdata($user_login, 'display_name'). "<br />"; 
		} else {
		$newMsg .="<input type='text' name='message_from' placeholder='Type your Name' maxlength='65' value='$message_from' /><br/>";
		$newMsg .= __("Email", "fep")."<font color='red'>*</font>: <br />";
		$newMsg .="<input type='text' name='message_email' placeholder='Type your Email Address' maxlength='65' value='$message_email' /><br/>";
			}
		$newMsg .= "<div id='fep-hd'>".__("H Name", "fep").":";
		$newMsg .="<input type='text' name='name1' value='' /><br />";
		$newMsg .= __("H Email", "fep").":";
		$newMsg .="<input type='email' name='email1' value='' /></div>";
		
		$newMsg .="<noscript><input type='hidden' name='nojs' value='nojs' /></noscript>";
		
        $newMsg .= __("Subject", "fep")."<font color='red'>*</font>:<br/>";
		$newMsg .= "<input type='text' name='message_title' placeholder='Subject' maxlength='65' value='$message_title' /><br/>";
		ob_start();
		do_action('fepcf_message_form_before_content');
		echo __("Message", 'fepcf').":<br/>";
		if ('wp_editor' == fepcf_get_option('editor_type')){
		wp_editor( $message_content, 'message_content', array('teeny' => false, 'media_buttons' => false, 'textarea_rows' => 8) );
		} elseif ('teeny' == fepcf_get_option('editor_type','teeny')){ 
		wp_editor( $message_content, 'message_content', array('teeny' => true, 'media_buttons' => false, 'textarea_rows' => 8) );
		} else {
        echo  "<textarea name='message_content' placeholder='Message Content'>$message_content</textarea>"; }
		
		do_action('fepcf_message_form_after_content');
		$newMsg .= ob_get_contents();
		ob_end_clean();
		if (fepcf_get_option('fep_cf_cap') == '1')
      {
		$newMsg .= __("CAPTCHA question", "fep").":<br/>";
		$newMsg .= fepcf_get_option('fep_cf_capqs')."<br />";
		$newMsg .= __("CAPTCHA answer", "fep")."<font color='red'>*</font>:<br/>";
		$newMsg .= "<input type='text' name='cap_ans' autocomplete='off' value='' /><br/>";}
		$newMsg .= "<input type='hidden' name='token' value='$token' /><br/>
        <input type='submit' name='contact_message' value='".__("Send Message", "fep")."' />
        </form>";
		if(fepcf_get_option('hide_branding') != '1'){
	  	//$version = $fep->get_version();
        $newMsg .= "<div id='fep-footer'><a href='http://www.banglardokan.com/blog/recent/project/front-end-pm-2215/'>Front End PM</a></div>";}
        
        return $newMsg;
      }
      else
      {
        return '<div id="fep-error">' .__("Please log in to contact with us.", "fep"). ' </div>';
      }
    }
	
	function checkContact()
	{
	if (isset($_POST['contact_message'])){
		global $wpdb, $user_ID, $user_login, $current_user;
		get_currentuserinfo();
		$errors = new WP_Error();
		$message = $_POST;
		
		if ( !isset ($message['message_to']) || fepcf_is_user_blocked() ){
		if ( !isset ($message['message_to']))
		$errors->add('invalidTo', __('You must select a department.', 'fepcf'));
		if (fepcf_is_user_blocked())
		$errors->add('userBlocked', __('You cannot send messages because you are blocked by administrator!', 'fepcf'));
		
		return $errors;
		}
		
		$messageArrayTo = explode(',',$message['message_to']);
		
		$preTo = trim($messageArrayTo[0]);
		$message['to'] =  fepcf_get_userdata($preTo);
		$message['department'] = trim($messageArrayTo[1]);
		$message['send_date'] = current_time('mysql');
		$message['ip'] = $this->get_ip();
		$message['browser'] = isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( $_SERVER['HTTP_USER_AGENT'], 0, 254 ) : '';
		$message['referer'] = esc_url($_SERVER['HTTP_REFERER']);
		$message['status'] = 5; //ststus 5 for contact message, 7 for spam message
		
		if (is_user_logged_in()) {
		//$fromID = $user_ID;
		$message['fromID'] = $current_user->ID;
		$message['fromName'] = $current_user->display_name;
		$message['fromEmail'] = $current_user->user_email;
		} else {
		$message['fromID'] = 0;
		$message['fromName'] = sanitize_text_field($_POST['message_from']);
		$message['fromEmail'] = trim($_POST['message_email']);
		
		if (!$message['fromName'])
		  $errors->add('invalidName', __('You must enter your name.', 'fepcf'));
		if (!is_email($message['fromEmail']))
		  $errors->add('invalidEmail', __('You must enter your valid e-mail address.', 'fepcf'));
		
		}
		
        if (!$message['to'])
		  $errors->add('invalidTo', __('You must select a department.', 'fepcf'));
        if (!$message['message_title'])
		  $errors->add('invalidSub', __('You must enter subject.', 'fepcf'));
        if (!$message['message_content'])
		  $errors->add('invalidMgs', __('You must enter some messages.', 'fepcf'));
		  
	if (fepcf_get_option('fep_cf_cap') == '1')
      {
		if (!isset($_POST['cap_ans']) || $_POST['cap_ans'] != fepcf_get_option('fep_cf_capans') )
		  $errors->add('capCheck', __('CAPTCHA answer is incorrect.', 'fepcf'));}
		  
		 if (!isset($_POST['name1']) || !empty($_POST['name1']) || !isset($_POST['email1']) || !empty($_POST['email1']))
		  $errors->add('BotCheck', __('If you see "H Name" or "H Email" Field DO NOT fill those.Those for Bot check.', 'fepcf'));
		  
		 $Dtime = fepcf_get_option('cf_time_delay');
		 if (is_user_logged_in()) {
		 $timeDelay = fepcf_time_delay($Dtime);
	  if ($timeDelay['diffr'] < $Dtime && !current_user_can('manage_options'))
      {
	  $errors->add('TimeDelay', sprintf(__('Please wait at least more %s to send another message!', 'fepcf'), $timeDelay['time']));
      }} else {
	  //use nonce to check time delay for non logged in users
	  $nonce = wp_create_nonce('fep_cf_time_delay');
	  //get value exists
	  $transient = get_transient('fep_cf_'.$nonce);
	  $transient = absint($transient);
	  
	  
	  	if ( $transient && ($transient+($Dtime*60)) > time() )
		$errors->add('loggedOutDelay', sprintf(__('Please wait at least more %s to send another message!', 'fepcf'), human_time_diff(time(),$transient+(fepcf_get_option('cf_time_delay')*60))));
	
		  
		if ($this->isBot() !== false)
		  $errors->add('Bots', sprintf(__("No bots please! UA reported as: %s", "fep"), esc_attr($_SERVER['HTTP_USER_AGENT'] )));
		  
		  //check is ip blacklisted
		if ( $this->is_ip_blacklisted($message['ip']) !== false )
		$errors->add('ipBlock', sprintf(__("Your IP %s is Blacklisted for this website.", "fep"), $message['ip'] ));
		
		//check is email blacklisted
		if (fepcf_get_option('email_blacklist_check') == '1' && fepcf_get_option('email_whitelist_check') != '1' ){
		if ( $this->is_email_blacklisted($message['fromEmail']) !== false )
		$errors->add('emailBlock', sprintf(__("Your email %s is Blacklisted for this website.", "fep"), $message['fromEmail'] ));}
		
		//check is email whitelisted
		if (fepcf_get_option('email_whitelist_check') == '1' ){
		if ( $this->is_email_whitelisted($message['fromEmail']) == false )
		$errors->add('emailWhitelist', sprintf(__("Your email %s is not Whitelisted for this website.", "fep"), $message['fromEmail'] ));}
		}
		
		
		  
	// lets check a few things - not enough to trigger an error on their own, but worth assigning a spam score..
	// score quickly adds up therefore allowing genuine users with 'accidental' score through but cutting out real spam :)
	$points = (int)0;

	$badwords = explode(',', fepcf_get_option('fep_cf_bad'));
	$badwords = array_unique($badwords);

	foreach ($badwords as $badword) {
		$word = trim($badword);
		if ( stripos($message['fromName'], $word) !== false || stripos($message['message_title'], $word) !== false || stripos($message['message_content'], $word) !== false )
			$points += 2; }

	if (stripos($message['message_content'], "http://") !== false || stripos($message['message_content'], "www.") !== false)
		$points += 2;
	if (isset($_POST['nojs']))
		$points += 1;
	if (strlen($message['fromName']) < 3 || strlen($message['fromName']) > 20)
		$points += 1;
	if (strlen($message['message_title']) < 10 || strlen($message['message_title'] > 100))
		$points += 2;
	if (strlen($message['message_content']) < 15 || strlen($message['message_content'] > 1500))
		$points += 2;
	// end score assignments
	$message['points'] = $points;
	if ( $message['points'] > fepcf_get_option('fep_cf_point') )
	$errors->add('spamPoints', sprintf(__("Your message looks too much like spam, and could not be sent this time. [%d]", 'fepcf'), $message['points']));
	
	if ( fepcf_get_option('fep_cf_akismet') == '1' ) {
	// Check if Akismet is installed with the corresponding API key
if( function_exists( 'akismet_http_post' ))
{   
	$akwp_api_key = get_option('wordpress_api_key');
	//Check Akismet API key
	if (!empty($akwp_api_key)) {
    global $akismet_api_host, $akismet_api_port;

    // data package to be delivered to Akismet
    $data = array( 
        'comment_author'        => $message['fromName'],
        'comment_author_email'  => $message['fromEmail'],
        'comment_content'       => $message['message_content'],
        'user_ip'               => $message['ip'],
        'user_agent'            => $message['browser'],
        'referrer'              => $message['referer'],
        'blog'                  => get_bloginfo('wpurl'),
        'blog_lang'             => get_bloginfo('language'),
        'blog_charset'          => get_bloginfo('charset'),
        'permalink'             => $message['referer']
    );

    // construct the query string
    $query_string = http_build_query( $data );
    // post it to Akismet
    $response = akismet_http_post( $query_string, $akismet_api_host, '/1.1/comment-check', $akismet_api_port );
    // check the results        
    $result = ( is_array( $response ) && isset( $response[1] ) ) ? $response[1] : 'false';
	
	if ($result == true )
	$message['status'] = 7;
	} else {
	if (current_user_can('manage_options'))
	 print '<div id="fep-error">' .__("AKISMET KEY is not configured.", "fep"). ' </div>';
	}
} else {
if (current_user_can('manage_options'))
 print '<div id="fep-error">' .__("AKISMET plugin is not installed.", "fep"). ' </div>';
 }
 }
	  if ( !fep_verify_nonce($message['token'], 'fepcf-message'))
			$errors->add('invalidToken', __('Sorry, your nonce did not verify!', 'fepcf'));
	
		do_action('fepcf_action_message_before_send', $errors);
	  	$message = apply_filters('fepcf_filter_message_before_send', $message);
	  
		if(count($errors->get_error_codes())==0){
		 
		 $wpdb->insert( FEP_MESSAGES_TABLE, array( 'from_user' => $message['fromID'], 'to_user' => $message['to'], 'message_title' => $message['message_title'], 'message_contents' => $message['message_content'], 'status' => $message['status'], 'last_sender' => $message['fromID'], 'send_date' => $message['send_date'], 'last_date' => $message['send_date'] ), array( '%d', '%d', '%s', '%s', '%d', '%d', '%s', '%s' ));
		 
		$message_id = $wpdb->insert_id;
		if ($message_id) {
		$wpdb->query($wpdb->prepare('INSERT INTO '.FEP_META_TABLE.' (message_id, field_name, field_value) VALUES ( %d, "from_name", %s ),( %1$d, "from_email", %s ),( %1$d, "department", %s ),( %1$d, "browser", %s ),( %1$d, "referer", %s ),( %1$d, "ip", %s ),( %1$d, "Spam Points", %s )', $message_id, $message['fromName'], $message['fromEmail'], $message['department'], $message['browser'], $message['referer'], $message['ip'], $message['points']));
		
		do_action('fepcf_action_message_after_send', $message_id, $message);
		
		if ( !is_user_logged_in() && fepcf_get_option('cf_time_delay') !=0 ) {
		set_transient( 'fep_cf_'.$nonce, time(), fepcf_get_option('cf_time_delay') * 60 ); //set to check time delay for non logged in users
		//setcookie('fep_cf_send_time', time(), time()+(fepcf_get_option('cf_time_delay') * 60), COOKIEPATH, COOKIE_DOMAIN, false);
		}
		
		//if ( $message['status'] == 5 )
		//$this->sendDepartmentEmail($message_id, $message);
		} else {
		$errors->add('someWrong', __('Something wrong please try again!', 'fepcf'));}
	}
      
	return $errors;
      }
	
	}


	
		function get_ip() {
	// Function to get the client IP address
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    elseif(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    elseif(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    elseif(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    elseif(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return preg_replace( '/[^0-9a-fA-F:., ]/', '',$ipaddress );;
}

		function isBot() {
	$bots = array("Indy", "Blaiz", "Java", "libwww-perl", "Python", "OutfoxBot", "User-Agent", "PycURL", "AlphaServer", "T8Abot", "Syntryx", "WinHttp", "WebBandit", "nicebot", "Teoma", "alexa", "froogle", "inktomi", "looksmart", "URL_Spider_SQL", "Firefly", "NationalDirectory", "Ask Jeeves", "TECNOSEEK", "InfoSeek", "WebFindBot", "girafabot", "crawler", "www.galaxy.com", "Googlebot", "Scooter", "Slurp", "appie", "FAST", "WebBug", "Spade", "ZyBorg", "rabaz");

	foreach ($bots as $bot)
		if (stripos($_SERVER['HTTP_USER_AGENT'], $bot) !== false)
			return true;

	if (empty($_SERVER['HTTP_USER_AGENT']) || $_SERVER['HTTP_USER_AGENT'] == " ")
		return true;

	return false;
}

		function is_ip_blacklisted($ip) {
        $ipBlacklist = explode(',', fepcf_get_option('fep_ip_block'));
		  $ipBlacklist = array_unique($ipBlacklist);
		  
        $ip_blocks = explode(".", $ip);
        if(count($ip_blocks)==4) {
            foreach($ipBlacklist as $Blockip) {
			$Blockip = trim($Blockip);
                if($Blockip!='') {
                    $blocks = explode(".", $Blockip);
                    if(count($blocks)==4) {
                        $matched = true;
                        for($k=0;$k<4;$k++) {
                            if(preg_match('|([0-9]+)-([0-9]+)|', $blocks[$k], $match)) {
                                if($ip_blocks[$k]<$match[1] || $ip_blocks[$k]>$match[2]) {
                                    $matched = false;
                                    break;
                                }
                            } else if($blocks[$k]!="*" && $blocks[$k]!=$ip_blocks[$k]) {
                                $matched = false;
                                break;
                            }
                        }
                        if($matched) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }
	
	function is_email_blacklisted($email) {
        $emailBlacklist = explode(',', fepcf_get_option('email_blacklist'));
		  $emailBlacklist = array_unique($emailBlacklist);
		  
        $email = strtolower($email);
        foreach($emailBlacklist as $rule) {
            $rule = str_replace("*", ".*", str_replace(".", "\.", strtolower(trim($rule))));
            if($rule!='') {
                if(substr($rule,0,1)=="!") {
                    $rule = '|^((?'.$rule.').*)$|';
                } else {
                    $rule = '|^'.$rule.'$|';
                }
                if(preg_match($rule, $email)) {
                    return true;
                }
            }
        }
        return false;
    }
	
	function is_email_whitelisted($email) {
        $emailWhitelisted = explode(',', fepcf_get_option('email_whitelist'));
		  $emailWhitelisted = array_unique($emailWhitelisted);
		  
        $email = strtolower($email);
        foreach($emailWhitelisted as $rule) {
            $rule = str_replace("*", ".*", str_replace(".", "\.", strtolower(trim($rule))));
            if($rule!='') {
                if(substr($rule,0,1)=="!") {
                    $rule = '|^((?'.$rule.').*)$|';
                } else {
                    $rule = '|^'.$rule.'$|';
                }
                if(preg_match($rule, $email)) {
                    return true;
                }
            }
        }
        return false;
    }

  } //END CLASS
} //ENDIF

add_action('wp_loaded', array(fepcf_main_class::init(), 'actions_filters'));
