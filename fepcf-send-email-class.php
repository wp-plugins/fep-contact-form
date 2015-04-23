<?php

if (!class_exists('fepcf_send_email_class'))
{
  class fepcf_send_email_class
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
			add_action ('fepcf_menu_button', array(&$this, 'email_menu'));
			add_action ('fepcf_switch_newemail', array(&$this, 'NewEmail'));
			add_action ('fepcf_action_message_after_send', array(&$this, 'sendDepartmentEmail'), 10, 2);
    	}

function email_menu() {
	 $class = 'fep-button';
	 if ( isset($_GET['fepcfaction']) && $_GET['fepcfaction'] == 'newemail')
	 $class = 'fep-button-active';
	 
	  echo "<a class='$class' href='".esc_url( add_query_arg(array('fepcfaction'=>'newemail'),fepcf_page_url()))."'>".__('New Email', 'fepcf').'</a>';
	  }
	  
/******************************************SEND EMAIL BEGIN******************************************/

// Send email to any email address
	function NewEmail(){
	$html = '<h2>' . __('Send Email', 'fepcf') . '</h2>';
	if(isset($_POST['fep-send-email'])!=''){
		$errors = $this->send_email_action();
		if(count($errors->get_error_messages())>0){
			$html .= fepcf_error($errors);
			$html .= $this->send_email();
		}
		else{
			$html .= '<div id="fep-success">' .__("Email successfully send.", "fepcf"). ' </div>';
		}
	}
	else{
		$html .= $this->send_email();
	}
	echo $html;
}

	function send_email() 
	{
	global $wpdb, $user_login;

$tocheck = get_option('fep_cf_to_field');
//permission check
if (in_array($user_login,$tocheck) || current_user_can('manage_options')){

$token = fep_create_nonce();
$Pto = ( isset( $_GET['to'] ) ) ? $_GET['to']: '';
if ( isset( $_GET['id'] ) )
$Pto = $wpdb->get_var($wpdb->prepare("SELECT field_value FROM ".FEP_META_TABLE." WHERE message_id = %d AND field_name = %s LIMIT 1",	 							$_GET['id'], 'from_email'));

if (is_email($Pto)){$to = $Pto;} else { $to = '';}
$to = ( isset( $_POST['fep-send-email-to'] ) ) ? $_POST['fep-send-email-to']: $to;
$domain_name =  preg_replace('/^www\./','',$_SERVER['SERVER_NAME']);
$from = 'noreply@'.$domain_name;
$subject = ( isset( $_REQUEST['fep-send-email-subject'] ) ) ? $_REQUEST['fep-send-email-subject']: '';
$message = ( isset( $_POST['fep-send-email-message'] ) ) ? $_POST['fep-send-email-message']: '';
$message2 = ( isset( $_POST['fep-send-email-message2'] ) ) ? $_POST['fep-send-email-message2']: fepcf_get_option('fep_cf_efoot');

$form =  "<p>
      <form name='fep-send-email' action='' method='post'>
      ".__("To", "fepcf").":*<br />
      <input type='text' name='fep-send-email-to' placeholder='Email Address' value='$to' /><br/>
	  ".__("From Name", "fepcf").":*<br />
      <input type='text' name='fep-send-email-from-name' value='".get_bloginfo('name')."' /><br/>
	  ".__("From Email", "fepcf").":*<br />
      <input type='text' name='fep-send-email-from' value='$from' /><br/>
	  ".__("Subject", "fepcf").":*<br />
      <input type='text' name='fep-send-email-subject' value='$subject' /><br/>
	  ".__("Message", "fepcf").":*<br />
      <textarea rows='10' cols='40' name='fep-send-email-message'>$message</textarea><br/>
	  ".__("Footer", "fepcf").":<br />
      <textarea name='fep-send-email-message2'>$message2</textarea><br/>
	  <input type='hidden' name='token' value='$token' /><br/>
      <input class='button-primary' type='submit' name='fep-send-email' value='".__("Send Email", "fepcf")."' />
      </form></p>";
	  
} else {
//does not have manage_options and department username
$form = "<div id='fep-error'>".__("Sorry, You do not have permission to send email!", "fepcf")."</div>";
	}
	return apply_filters('fepcf_email_form', $form);
}

function send_email_action() 
{
		
if (isset($_POST['fep-send-email'])){
		$errors = new WP_Error();
		
$to = $_POST['fep-send-email-to'];
$name = esc_attr($_POST['fep-send-email-from-name']);
$from = $_POST['fep-send-email-from'];
$subject = esc_attr($_POST['fep-send-email-subject']);
$message1 = esc_textarea($_POST['fep-send-email-message']);
$message2 = esc_textarea($_POST['fep-send-email-message2']); 
// message lines should not exceed 70 characters (PHP rule), so wrap it
$message = wordwrap($message1, 70);
$message .= "\r\n\r\n";
$message .= wordwrap($message2, 70);

$tocheck = get_option('fep_cf_to_field');
//permission check
if ( !in_array($user_login,$tocheck) && !current_user_can('manage_options'))
$errors->add('noPermission', __("Sorry, You do not have permission to send email!", 'fepcf'));

if (!is_email($to) ) 
$errors->add('noEmail', __("Please enter a valid email address in \"To\" field!", 'fepcf'));
	  if (!$name ) 
$errors->add('noName', __("Please enter a valid Name in \"From Name\" field!", 'fepcf'));
	  if (!is_email($from) ) 
$errors->add('invalidEmail', __("Please enter a valid email address in \"From Email\" field!", 'fepcf'));
	  if (!$subject )
$errors->add('noSubject', __("Please your message Subject in \"Subject\" field!", 'fepcf'));
	  if (!$message1 ) 
$errors->add('noMessage', __("Please enter your message in \"Message\" field!", 'fepcf'));
	  
	  $headers = "MIME-Version: 1.0\r\n" .
          "From: ".$name." "."<".$from.">\r\n" .
          "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\r\n";
		
		
		if ( !fep_verify_nonce($_POST['token'], 'fepcf-message'))
			$errors->add('invalidToken', __('Sorry, your nonce did not verify!', 'fepcf'));

	  
	  do_action( 'fepcf_before_send_email', $errors );

		if(count($errors->get_error_codes())==0){
		  
	  $fepEmail= wp_mail($to, $subject, $message, $headers);
	  if ( !$fepEmail ) {
	  $errors->add('SomeError', __('Something wrong please try again!', 'fepcf'));
	  	}
	  }
	  
	  return $errors;
	  
}
	}
	
/******************************************SEND EMAIL END******************************************/
	
	function sendDepartmentEmail( $message_id, $mgs )
    {
      $notify = fepcf_get_option('notify_email');
      if ($notify == '1')
      {
        $sendername = get_bloginfo("name");
        $sendermail = get_bloginfo("admin_email");
        $headers = "MIME-Version: 1.0\r\n" .
          "From: ".$sendername." "."<".$sendermail.">\r\n" . 
          "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\r\n";
		$subject =  get_bloginfo("name").': '.__('New Contact Message', 'fepcf');
		$message = __('You have received a new contact message in', 'fepcf'). "\r\n";
		$message .= get_bloginfo("name")."\r\n";
		$message .= sprintf(__("From: %s", 'fepcf'), $mgs['fromName']). "\r\n";
		$message .= sprintf(__("Subject: %s", 'fepcf'), $mgs['message_title'] ). "\r\n";
		// message lines should not exceed 70 characters (PHP rule), so wrap content
		$message .= sprintf(__("Message: %s", 'fepcf'), wordwrap($mgs['message_content'], 70)). "\r\n";
		$message .= sprintf(__("Referrer: %s", 'fepcf'), $mgs['referer'] ). "\r\n";
		$message .= __('Please Click the following link to view full Message.', 'fepcf')."\r\n";
		if ( function_exists('fep_action_url'))
		$message .= fep_action_url('mycontactmgs')."\r\n";
		else
		$message .= esc_url( add_query_arg(array('fepcfaction'=>'mycontactmgs'),fepcf_page_url()))."\r\n";
        $mailTo = fep_get_userdata( $mgs['to'], 'user_email', 'id');
		
		//wp_mail($mailTo, $subject, $message, $headers); // uncomment this line if you want blog name in message from, comment following line
        wp_mail($mailTo, $subject, $message);
      }
    }
	
  } //END CLASS
} //ENDIF

add_action('wp_loaded', array(fepcf_send_email_class::init(), 'actions_filters'));
?>