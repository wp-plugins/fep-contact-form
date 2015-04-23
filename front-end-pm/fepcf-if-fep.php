<?php

if (!class_exists('fepcf_if_fep_class'))
{
  class fepcf_if_fep_class
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
			if ( function_exists('fep_plugin_activate')){
			add_action ('fep_menu_button', array(&$this, 'fep_mymessage'), 12);
			add_action ('fep_switch_mycontactmgs', array(&$this, 'contact_message'));
			add_action ('fep_menu_button', array(&$this, 'email_menu'), 15);
			add_action ('fep_switch_newemail', array(&$this, 'NewEmail'));
			if ( current_user_can('manage_options') ) {
			add_action ('fep_menu_button', array(&$this, 'fep_allmessage'), 13);
			add_action ('fep_menu_button', array(&$this, 'fep_spam'), 14);
			add_action ('fep_switch_contactmgs', array(&$this, 'contact_message'));
			add_action ('fep_switch_spam', array(&$this, 'contact_message'));
			add_action ('fep_switch_emptyspam', array(&$this, 'emptySpam'));
			add_action ('fep_switch_notspam', array(&$this, 'notSpam'));
			add_action ('fep_switch_delete', array(&$this, 'delete'));
			add_action ('fep_switch_viewcontact', array(&$this, 'view'));
				}
				
				
			}
			
    	}
		
		//Delete all spam messages
	function emptySpam()
    {
      echo fepcf_display_class::init()->emptySpam();
    }
	
	function notSpam()
    {
	 echo fepcf_display_class::init()->notSpam();
    }
	
	function delete()
    {
	echo fepcf_display_class::init()->delete();
    }
	
	// Send email to any email address
	function NewEmail(){
	fepcf_send_email_class::init()->NewEmail();
	}
	
		function contact_message($action = '', $title = '', $total_message = false, $messages = false )
{
	global $user_ID;

	  $token = fep_create_nonce('delete_contact_message');
	  
	  if ( !$action )
	  $action = ( isset( $_GET['fepaction']) && $_GET['fepaction'] )? $_GET['fepaction']: 'mycontactmgs';
	  
	  if ( !$title )
	  $title = __('Your Contact Messages', 'fepcf');
	  if ( $action == 'spam' )
	  $title = __('Spam Messages', 'fepcf');
	  if ( $action == 'contactmgs' )
	  $title = __('All Contact Messages', 'fepcf');
	  
	  $title = apply_filters('fepcf_message_headline', $title, $action );
	  
	  if( false === $total_message )
	  $total_message = fepcf_display_class::init()->total_message( $action );
	  
	  if( false === $messages )
	  $messages = fepcf_display_class::init()->messages( $action );
	  
	  $msgsOut = '';
      if ($total_message)
      {
			  $msgsOut .= "<p><strong>$title: ($total_message)</strong>";
			  if ( $action === 'spam' && current_user_can('manage_options'))
		$msgsOut .= "<a href='".fep_action_url('emptyspam')."&token=$token' onclick='return confirm(\"".__('Are you sure you want to delete all spam messages? This action CAN NOT be undone.', 'fepcf')."\");'>".__('Empty Spam Folder', 'fepcf')."</a> ";
		$msgsOut .= "</p>";
		
        $numPgs = $total_message / fepcf_get_option('messages_page',15);
        if ($numPgs > 1)
        {
          $msgsOut .= "<p><strong>".__("Page", 'fepcf').": </strong> ";
          for ($i = 0; $i < $numPgs; $i++)
            if ($_GET['feppage'] != $i){
			  $msgsOut .= "<a href='".fep_action_url($action)."&feppage=$i'>".($i+1)."</a> ";
            } else {
              $msgsOut .= "[<b>".($i+1)."</b>] ";}
          $msgsOut .= "</p>";
        }

        $msgsOut .= "<table><tr class='fep-head'>
        <th width='20%'>".__("Started By", 'fepcf')."</th>
		<th width='20%'>".__("To", 'fepcf')."</th>
        <th width='30%'>".__("Subject", 'fepcf')."</th></tr>";
        
		$a = 0;
        foreach ($messages as $msg)
        {
		$meta = fepcf_display_class::init()->meta( $msg->id );
		
          if ($msg->status == 5 || $msg->status == 7 )
            $status = "<font color='#FF0000'>".__("Unread", 'fepcf')."</font>";
          else
            $status = __("Read", 'fepcf');
			
			$status = apply_filters ('fepcf_filter_status_display', $status, $msg, $action );
			
		  $msgsOut .= "<tr class='fep-trodd".$a."'>";
		  $msgsOut .= "<td>" .$meta['from_name']. "<br/><small>".fepcf_format_date($msg->send_date)."</small></td>"; 
		  
		  $msgsOut .= "<td>" .fepcf_get_userdata( $msg->to_user, 'display_name', 'id' ). "<br/><small>".$meta['department']."</small></td>";
		  
		  $view_url = fep_action_url('viewcontact')."&id=$msg->id";
		  
		  $msgsOut .= "<td><a href='".apply_filters('fepcf_view_message_url', $view_url, $msg->id )."'>".fepcf_output_filter($msg->message_title,true)."</a><br/><small>".$status."</small></td>";
          $msgsOut .=  "</tr>";
		   //Alternate table colors
		  if ($a) $a = 0; else $a = 1;
        }
        $msgsOut .= "</table>";
		

        echo apply_filters('fepcf_messagebox', $msgsOut, $action);
      }
      else
      {
        echo "<div id='fep-error'>".sprintf(__("%s empty", 'fepcf'), $title )."</div>";
      }
	
}

function view()
    {
      global $wpdb, $user_ID;
	  
	  $id = (isset( $_GET['id']) && $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
      $message = fepcf_display_class::init()->message( $id );
	  
	  if ( $message->to_user != $user_ID && !current_user_can('manage_options')){
	  echo "<div id='fep-error'>".__("No Message Found!", 'fepcf')."</div>";
	  return; 
	  	}
	  
	  if ($message->status == 5 && $user_ID == $message->to_user) {//Update only if the reader is the reciever 
        $wpdb->query($wpdb->prepare("UPDATE ".FEP_MESSAGES_TABLE." SET status = 6 WHERE id = %d", $message->id));
		} elseif ($message->status == 7 && $user_ID == $message->to_user) {//Update only if the reader is the reciever 
        $wpdb->query($wpdb->prepare("UPDATE ".FEP_MESSAGES_TABLE." SET status = 8 WHERE id = %d", $message->id));
		}
		
	  $meta = fepcf_display_class::init()->meta( $id );
       $html = '';
      if ( $message ) //Just viewing announcements
      {
	   $user_type = ( $message->from_user == 0 ) ? 'Unregistered' : 'Registered' ;
	   
        $html .= "<table>";
		
        $html .= "<tr class='fep-trodd1'><td class='fep-pmtext'><strong>".__("Subject", 'fepcf').":</strong> ".fepcf_output_filter($message->message_title, true).
          "<br/><strong>".__("Date", 'fepcf').":</strong> ".fepcf_format_date($message->send_date);
		$html .= "<br/><strong>".__("Sent by", 'fepcf').":</strong> ".$meta['from_name']. " ($user_type)" ;
		
		if ( $message->status == 7 || $message->status == 8 ) {
		$token = fep_create_nonce('not_spam_message');
		$notspam_url = fep_action_url('notspam')."&id=$message->id&token=$token";
		
		$html .= "<br/><strong>".__("Not Spam?", 'fepcf').":</strong> <a href='".apply_filters('fepcf_notspam_url', $notspam_url, $message->id )."'>Not Spam</a>" ;
			}
		
		$token = fep_create_nonce('delete_contact_message');
		$del_url = fep_action_url('delete')."&id=$message->id&token=$token";
		
		$html .= "<br/><strong>".__("Delete", 'fepcf').":</strong> <a href='".apply_filters('fepcf_delete_message_url', $del_url, $message->id )."'>Delete</a>"  ;
		  
		   	ob_start();
		  	do_action ('fepcf_display_after_subject', $message->id );
		  	$html .= ob_get_contents();
			ob_end_clean();
		  
        $html .= "</td></tr>";
		  
        $html .= "<tr class='fep-trodd0'><td class='fep-pmtext'><strong>".__("Message", 'fepcf').":</strong><br/>".fepcf_output_filter($message->message_contents);
		
			ob_start();
		  	do_action ('fepcf_display_after_content', $message->id );
		  	$html .= ob_get_contents();
			ob_end_clean();
			
		$html .= '<hr />';
		foreach ( $meta as $k => $v )
			{
			if ($k == 'from_email')
				$html .= ucwords($k) .": ".antispambot($v) ." <a href='".fep_action_url('newemail')."&id=$message->id&fep-send-email-subject=".fepcf_output_filter($message->message_title, true)."'>".__('Send Email', 'fepcf')."</a><br />";
			elseif($k == 'referer')
				$html .= ucwords($k) .': '.make_clickable(esc_url($v)) .'<br />';
			else
				$html .= ucwords($k) .': '.esc_html($v) .'<br />';
			}
        
        $html .= "</td></tr></table>";
      } else {
	  	$html .= "<div id='fep-error'>".__("No Message Found!", 'fepcf')."</div>"; 
		}
		
		echo $html;

    }
		
		function fep_mymessage() {
	 $class = 'fep-button';
	 if ( is_page( fep_page_id() ) && isset($_GET['fepaction']) && $_GET['fepaction'] == 'mycontactmgs')
	 $class = 'fep-button-active';
	 
	  echo "<a class='$class' href='".fep_action_url('mycontactmgs')."'>".sprintf(__('My Contact Message%s', 'fepcf'), fepcf_display_class::init()->mycontact_new()).'</a>';
	  }
	  
	  function fep_allmessage() {
	 $class = 'fep-button';
	 if ( is_page( fep_page_id() ) && isset($_GET['fepaction']) && $_GET['fepaction'] == 'contactmgs')
	 $class = 'fep-button-active';
	 
	  echo "<a class='$class' href='".fep_action_url('contactmgs')."'>".sprintf(__('All Contact Message%s', 'fepcf'), fepcf_display_class::init()->getcontact_new()).'</a>';
	  }
	  
	  function fep_spam() {
	 $class = 'fep-button';
	 if ( is_page( fep_page_id() ) && isset($_GET['fepaction']) && $_GET['fepaction'] == 'spam')
	 $class = 'fep-button-active';
	 
	  echo "<a class='$class' href='".fep_action_url('spam')."'>".sprintf(__('Spam Message%s', 'fepcf'), fepcf_display_class::init()->getSpam_new()).'</a>';
	  }
	  
	  function email_menu() {
	 $class = 'fep-button';
	 if ( is_page( fep_page_id() ) && isset($_GET['fepaction']) && $_GET['fepaction'] == 'newemail')
	 $class = 'fep-button-active';
	 
	  echo "<a class='$class' href='".fep_action_url('newemail')."'>".__('New Email', 'fepcf').'</a>';
	  }

	
  } //END CLASS
} //ENDIF

add_action('wp_loaded', array(fepcf_if_fep_class::init(), 'actions_filters'));
?>