<?php

//Display CLASS
if (!class_exists("fepcf_display_class"))
{
  class fepcf_display_class
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
	add_shortcode('fep-contact-form-admin', array(&$this, 'displayAll' )); //for FRONT END PM
	//add_action('admin_menu', array(&$this, 'MenuPage'), 99);
	//add_filter('plugin_action_links', array(&$this, 'add_settings_link'), 10, 2 );
    }


/******************************************MAIN DISPLAY BEGIN******************************************/

    //Display the proper contents
   function displayAll()
    {
      global $user_ID;
      if ($user_ID)
      {
	  

        //Add header
        $out = $this->Header();

        //Add Menu
        $out .= $this->Menu();
		
        //Start the guts of the display
		$switch = ( isset($_GET['fepcfaction'] ) && $_GET['fepcfaction'] ) ? $_GET['fepcfaction'] : 'messagebox';
		
        switch ($switch)
        {
		case has_action("fepcf_switch_{$switch}"):
			ob_start();
			do_action("fepcf_switch_{$switch}");
			$out .= ob_get_contents();
			ob_end_clean();
			break;
          case 'delete':
            $out .= $this->delete();
            break;
		case 'mycontactmgs':
		case 'contactmgs':
		case 'spam':
            $out .= $this->contact_message( $switch );
            break;
		case 'notspam':
			$out .= $this->notSpam();
            break;
		case 'emptyspam':
			$out .= $this->emptySpam();
            break;
		case 'viewcontact':
            $out .= $this->view();
            break;
          default: //Message box is shown by Default
            $out .= $this->contact_message();
            break;
        }

        //Add footer
        $out .= $this->Footer();
      }
      else
      {
        $out = "<div id='fep-error'>".__("You must be logged-in to view your message.", 'fepcf')."</div>";
      }
      return apply_filters('fepcf_admin_shortcode_output', $out);
    }
	
function messages( $action )
    {
      global $wpdb, $user_ID;
	  if (isset($_GET['feppage'])){
      $page = absint($_GET['feppage']);
	  }else{$page = 0;}
      $start = $page * fepcf_get_option('messages_page',15);
      $end = fepcf_get_option('messages_page',15); //status = 5/6 indicates that the msg is a contact message, 7/8 indicates that the msg is a spam :)
	  
	  $get_messages = '';
	  if ($action === 'contactmgs' && current_user_can('manage_options')){
	  $get_messages = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".FEP_MESSAGES_TABLE." WHERE status = 5 OR status = 6 ORDER BY send_date DESC LIMIT %d, %d", $start, $end));
	  } elseif ($action === 'spam' && current_user_can('manage_options')){
	  $get_messages = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".FEP_MESSAGES_TABLE." WHERE status = 7 OR status = 8 ORDER BY send_date DESC LIMIT %d, %d", $start, $end));
	  } else{
	  $get_messages = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".FEP_MESSAGES_TABLE." WHERE to_user = %d AND to_del = 0 AND (status = 5 OR status = 6) ORDER BY send_date DESC LIMIT %d, %d", $user_ID, $start, $end));
	  }

      return $get_messages;
    }

	function total_message( $action )
    {
      global $wpdb, $user_ID; //status = 5/6 indicates that the msg is a contact message, 7/8 indicates that the msg is a spam :)
	  
	  if ($action === 'contactmgs' && current_user_can('manage_options')){
	  $results = $wpdb->get_results("SELECT id FROM ".FEP_MESSAGES_TABLE." WHERE status = 5 OR status = 6");
	  } elseif ($action === 'spam' && current_user_can('manage_options')){
	  $results = $wpdb->get_results("SELECT id FROM ".FEP_MESSAGES_TABLE." WHERE status = 7 OR status = 8");
	  } else{
	  $results = $wpdb->get_results($wpdb->prepare("SELECT id FROM ".FEP_MESSAGES_TABLE." WHERE to_user = %d AND to_del = 0 AND (status = 5 OR status = 6)", $user_ID));
	  }
      return $wpdb->num_rows;
    }
	function getcontact_new()
    {
      global $wpdb; //status = 5 indicates that the msg is a new contact message :)
	  
	  $results = $wpdb->get_results("SELECT id FROM ".FEP_MESSAGES_TABLE." WHERE status = 5");
	  
	  if ($wpdb->num_rows){
	  	$newmgs = " (<font color='red'>";
		$newmgs .= $wpdb->num_rows;
		$newmgs .="</font>)";
		} else {
		$newmgs ="";}
		return $newmgs;
    }
	function getSpam_new()
    {
      global $wpdb; //status = 7 indicates that the msg is a new spam message :)
	  
	  $results = $wpdb->get_results("SELECT id FROM ".FEP_MESSAGES_TABLE." WHERE status = 7");
	  
	  if ($wpdb->num_rows){
	  	$newmgs = " (<font color='red'>";
		$newmgs .= $wpdb->num_rows;
		$newmgs .="</font>)";
		} else {
		$newmgs ="";}
		return $newmgs;
    }
	function mycontact_new()
    {
      global $wpdb, $user_ID; //status = 5 indicates that the msg is a new contact message :)
	  
	  $results = $wpdb->get_results($wpdb->prepare("SELECT id FROM ".FEP_MESSAGES_TABLE." WHERE to_user = %d AND status = 5 AND to_del = 0", $user_ID));
	  
	  if ($wpdb->num_rows){
	  	$newmgs = " (<font color='red'>";
		$newmgs .= $wpdb->num_rows;
		$newmgs .="</font>)";
		} else {
		$newmgs ="";}
		return $newmgs;
    }
	function notSpam()
    {
	global $wpdb;
	
	if (!fep_verify_nonce($_GET['token'], 'not_spam_message')){
	  return "<div id='fep-error'>".__("Invalid Token!", 'fepcf')."</div>";}
	  
	if (isset($_GET['id'])){$id = absint($_GET['id']);}else{ $id = 0; }
      $status = $wpdb->get_var($wpdb->prepare("SELECT status FROM ".FEP_MESSAGES_TABLE." WHERE id = %d", $id));
	  if ( $status == 7 && current_user_can('manage_options')){
	  $wpdb->query($wpdb->prepare("UPDATE ".FEP_MESSAGES_TABLE." SET status = 5 WHERE id = %d", $id));
	  } elseif ( $status == 8 && current_user_can('manage_options')){
	  $wpdb->query($wpdb->prepare("UPDATE ".FEP_MESSAGES_TABLE." SET status = 6 WHERE id = %d", $id));}
	  
	return "<div id='fep-success'>".__("Your message was successfully moved to Contact Message!", 'fepcf')."</div>";
	
	}
	
    function Header()
    {
      global $user_ID;

      $header = "<div id='fep-wrapper'>";
      $header .= "<div id='fep-header'>";
      $header .= get_avatar($user_ID, 55)."<p><strong>".__("Welcome", 'fepcf').": ". fepcf_get_userdata( $user_ID, 'display_name', 'id' ) ."</strong><br/>";
	  
	  ob_start();
	  do_action('fepcf_header_note', $user_ID);
	  $header .= ob_get_contents();
	  ob_end_clean();
      $header .= "</div>";
      return $header;
    }


    function Menu()
    {
      $menu = "<div id='fep-menu'>";
	  
	  ob_start();
	  do_action('fepcf_menu_button');
	  $menu .= ob_get_clean();
	  
	  $menu .="</div>";
      $menu .= "<div id='fep-content'>";
      return $menu;
    }
	

    function Footer()
    {
      $footer = '</div>'; //End content
	  
	  if(has_action('fepcf_footer_note')) {
      $footer .= "<div id='fep-footer'>";
	  ob_start();
	  do_action('fepcf_footer_note');
	  $footer .= ob_get_clean();
	  
      $footer .= '</div>'; }//End Footer
	  $footer .= '</div>'; //End main wrapper
      
      return $footer;
    }
	
	function contact_message($action = '', $title = '', $total_message = false, $messages = false )
{
	global $user_ID;

	  $token = fep_create_nonce('delete_contact_message');
	  
	  if ( !$action )
	  $action = ( isset( $_GET['fepcfaction']) && $_GET['fepcfaction'] )? $_GET['fepcfaction']: 'mycontactmgs';
	  
	  if ( !$title )
	  $title = __('Your Contact Messages', 'fepcf');
	  if ( $action == 'spam' )
	  $title = __('Spam Messages', 'fepcf');
	  
	  $title = apply_filters('fepcf_message_headline', $title, $action );
	  
	  if( false === $total_message )
	  $total_message = $this->total_message( $action );
	  
	  if( false === $messages )
	  $messages = $this->messages( $action );
	  
	  $msgsOut = '';
      if ($total_message)
      {
			  $msgsOut .= "<p><strong>$title: ($total_message)</strong>";
			  if ( $action === 'spam' && current_user_can('manage_options'))
		$msgsOut .= "<a href='".esc_url( add_query_arg(array('fepcfaction'=> 'emptyspam' ,'token'=> $token),fepcf_page_url()))."' onclick='return confirm(\"".__('Are you sure you want to delete all spam messages? This action CAN NOT be undone.', 'fepcf')."\");'>".__('Empty Spam Folder', 'fepcf')."</a> ";
		$msgsOut .= "</p>";
		
        $numPgs = $total_message / fepcf_get_option('messages_page',15);
        if ($numPgs > 1)
        {
          $msgsOut .= "<p><strong>".__("Page", 'fepcf').": </strong> ";
          for ($i = 0; $i < $numPgs; $i++)
            if ($_GET['feppage'] != $i){
			  $msgsOut .= "<a href='".esc_url( add_query_arg(array('fepcfaction'=> $action ,'feppage'=>$i),fepcf_page_url()))."'>".($i+1)."</a> ";
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
		$meta = $this->meta( $msg->id );
		
          if ($msg->status == 5 || $msg->status == 7 )
            $status = "<font color='#FF0000'>".__("Unread", 'fepcf')."</font>";
          else
            $status = __("Read", 'fepcf');
			
			$status = apply_filters ('fepcf_filter_status_display', $status, $msg, $action );
			
		  $msgsOut .= "<tr class='fep-trodd".$a."'>";
		  $msgsOut .= "<td>" .$meta['from_name']. "<br/><small>".fepcf_format_date($msg->send_date)."</small></td>"; 
		  
		  $msgsOut .= "<td>" .fepcf_get_userdata( $msg->to_user, 'display_name', 'id' ). "<br/><small>".$meta['department']."</small></td>";
		  
		  $view_url = esc_url( add_query_arg(array('fepcfaction'=>'viewcontact','id'=>$msg->id),fepcf_page_url()));
		  
		  $msgsOut .= "<td><a href='".apply_filters('fepcf_view_message_url', $view_url, $msg->id )."'>".fepcf_output_filter($msg->message_title,true)."</a><br/><small>".$status."</small></td>";
          $msgsOut .=  "</tr>";
		   //Alternate table colors
		  if ($a) $a = 0; else $a = 1;
        }
        $msgsOut .= "</table>";
		

        return apply_filters('fepcf_messagebox', $msgsOut, $action);
      }
      else
      {
        return "<div id='fep-error'>".sprintf(__("%s empty", 'fepcf'), $title )."</div>";
      }
	
}

function message( $message_id )
    {
      global $wpdb, $user_ID;
	  
      $get_message = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".FEP_MESSAGES_TABLE." WHERE id = %d", $message_id));

	  
      return $get_message;
    }
	
function meta( $message_id )
    {
      global $wpdb;
	  
	  $field_name = "'from_name','from_email','department','browser','referer','ip','Spam Points'";
	  
      $get_meta = $wpdb->get_results($wpdb->prepare("SELECT field_name,field_value FROM ".FEP_META_TABLE." WHERE message_id = %d AND field_name IN ($field_name)", $message_id));
	  //var_dump($get_meta);
	  $meta_array = array();
	  foreach ( $get_meta as $meta )
	  	{
			$meta_array[$meta->field_name] = $meta->field_value;
		}

	  //var_dump($meta_array);
      return $meta_array;
    }
	
function view()
    {
      global $wpdb, $user_ID;
	  
	  $id = (isset( $_GET['id']) && $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
      $message = $this->message( $id );
	  
	  if ( $message->to_user != $user_ID && !current_user_can('manage_options'))
	  return "<div id='fep-error'>".__("No Message Found!", 'fepcf')."</div>"; 
	  
	  if ($message->status == 5 && $user_ID == $message->to_user) {//Update only if the reader is the reciever 
        $wpdb->query($wpdb->prepare("UPDATE ".FEP_MESSAGES_TABLE." SET status = 6 WHERE id = %d", $message->id));
		} elseif ($message->status == 7 && $user_ID == $message->to_user) {//Update only if the reader is the reciever 
        $wpdb->query($wpdb->prepare("UPDATE ".FEP_MESSAGES_TABLE." SET status = 8 WHERE id = %d", $message->id));
		}
		
	  $meta = $this->meta( $id );
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
		$notspam_url = esc_url( add_query_arg(array('fepcfaction'=>'notspam','id'=>$message->id, 'token' => $token),fepcf_page_url()));
		
		$html .= "<br/><strong>".__("Not Spam?", 'fepcf').":</strong> <a href='".apply_filters('fepcf_notspam_url', $notspam_url, $message->id )."'>Not Spam</a>" ;
			}
		
		$token = fep_create_nonce('delete_contact_message');
		$del_url = esc_url( add_query_arg(array('fepcfaction'=>'delete','id'=>$message->id, 'token' => $token),fepcf_page_url()));
		
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
				$html .= ucwords($k) .': '.antispambot($v) .' <a href="'.esc_url( add_query_arg(array('fepcfaction'=>'newemail','id'=>$message->id, 'fep-send-email-subject' => fepcf_output_filter($message->message_title, true)),fepcf_page_url())).'">'.__('Send Email', 'fepcf').'</a><br />';
			elseif($k == 'referer')
				$html .= ucwords($k) .': '.make_clickable(esc_url($v)) .'<br />';
			else
				$html .= ucwords($k) .': '.esc_html($v) .'<br />';
			}
        
        $html .= "</td></tr></table>";
      } else {
	  	$html .= "<div id='fep-error'>".__("No Message Found!", 'fepcf')."</div>"; 
		}
		
		return $html;

    }
	
function delete()
    {
      global $wpdb, $user_ID;

      $delID = absint( $_GET['id'] );
	  
	  if (!fep_verify_nonce($_GET['token'], 'delete_contact_message')){
	  return "<div id='fep-error'>".__("Invalid Token!", 'fepcf')."</div>";}
	  
	  $message_to = $wpdb->get_var($wpdb->prepare("SELECT to_user FROM ".FEP_MESSAGES_TABLE." WHERE id = %d", $delID));
	  
	  if (!current_user_can('manage_options') && $message_to != $user_ID ){
	  return "<div id='fep-error'>".__("No permission!", 'fepcf')."</div>";}
	  

		$ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM ".FEP_MESSAGES_TABLE." WHERE id = %d OR parent_id = %d", $delID, $delID));
	  $id = implode(',',$ids);
	  
	  do_action ('fepcf_message_before_delete', $delID, $ids);
	  
          $wpdb->query($wpdb->prepare("DELETE FROM ".FEP_MESSAGES_TABLE." WHERE id = %d OR parent_id = %d", $delID, $delID));
		  $wpdb->query("DELETE FROM ".FEP_META_TABLE." WHERE message_id IN ({$id})");

		
		return "<div id='fep-success'>".__("Message was successfully deleted!", 'fepcf')."</div>";
    }
	
	//Delete all spam messages
	function emptySpam()
    {
      global $wpdb;
	  
	  if (!fep_verify_nonce($_GET['token'], 'delete_contact_message')){
	  return "<div id='fep-error'>".__("Invalid Token!", 'fepcf')."</div>";}
	  
	  if (!current_user_can('manage_options')){
	  return "<div id='fep-error'>".__("No permission!", 'fepcf')."</div>";}
	  
	  $ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM ".FEP_MESSAGES_TABLE." WHERE status = %d OR status = %d", 7, 8));
	  $id = implode(',',$ids);
	  
	  do_action ('fepcf_message_before_delete', 0, $ids);
	  
          $wpdb->query($wpdb->prepare("DELETE FROM ".FEP_MESSAGES_TABLE." WHERE status = %d OR status = %d", 7, 8));
		  $wpdb->query("DELETE FROM ".FEP_META_TABLE." WHERE message_id IN ({$id})");

		
		return "<div id='fep-success'>".__("All spam messages successfully deleted!", 'fepcf')."</div>";
    }
	

  } //END CLASS
} //ENDIF

add_action('wp_loaded', array(fepcf_display_class::init(), 'actions_filters'));	
?>