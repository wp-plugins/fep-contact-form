<?php

if ( !function_exists('fepcf_plugin_activate') ) :


    function fepcf_plugin_activate()
    {
      global $wpdb;

      $charset_collate = '';
      if( $wpdb->has_cap('collation'))
      {
        if(!empty($wpdb->charset))
          $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        if(!empty($wpdb->collate))
          $charset_collate .= " COLLATE $wpdb->collate";
      }
	  $installed_ver = get_option( "fep_db_version" );
	  $installed_meta_ver = get_option( "fep_meta_db_version" );

	if( $installed_ver < FEP_DB_VERSION || $wpdb->get_var("SHOW TABLES LIKE '".FEP_MESSAGES_TABLE."'") != FEP_MESSAGES_TABLE) {

      $sqlMsgs = 	"CREATE TABLE ".FEP_MESSAGES_TABLE." (
            id int(11) NOT NULL auto_increment,
            parent_id int(11) NOT NULL default '0',
            from_user int(11) NOT NULL default '0',
            to_user int(11) NOT NULL default '0',
            last_sender int(11) NOT NULL default '0',
            send_date datetime NOT NULL default '0000-00-00 00:00:00',
            last_date datetime NOT NULL default '0000-00-00 00:00:00',
            message_title varchar(255) NOT NULL,
            message_contents MEDIUMTEXT NOT NULL,
            status int(11) NOT NULL default '0',
            to_del int(11) NOT NULL default '0',
            from_del int(11) NOT NULL default '0',
            PRIMARY KEY (id))
            {$charset_collate};";

      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

      dbDelta($sqlMsgs);
	  update_option( "fep_db_version", FEP_DB_VERSION );
	  //var_dump('1');
	  }
	  
	  	if( $installed_meta_ver < FEP_META_VERSION || $wpdb->get_var("SHOW TABLES LIKE '".FEP_META_TABLE."'") != FEP_META_TABLE) {

      $sql_meta = 	"CREATE TABLE ".FEP_META_TABLE." (
            meta_id int(11) NOT NULL auto_increment,
            message_id int(11) NOT NULL default '0',
            field_name varchar(100) NOT NULL,
            field_value MEDIUMTEXT NOT NULL,
            PRIMARY KEY (meta_id),
			KEY (field_name))
            {$charset_collate};";

      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

      dbDelta($sql_meta);
	  update_option( "fep_meta_db_version", FEP_META_VERSION );
	  //var_dump('2');
	  }
	  //var_dump('3');
    }
	
endif;

function fepcf_activate_option_save()
	{
		$options = get_option('FEPCF_admin_options');
		
		if ( false === $options )
			{
				$options = array(
                          'fep_cf_bad' => 'ahole,anus,ash0le,ash0les,asholes,ass,Aazzhole,bassterds,bastard,bastards,bastardz,basterds,basterdz,Biatch,bitch,Blow Job,boffing,butthole,buttwipe,c0ck,c0cks,c0k,Carpet Muncher,cawk,cawks,Clit,cnts,cntz,cock,cockhead,cock-head,cocks,CockSucker,cock-sucker,crap,cum,cunt,cunts,cuntz,dick,dild0,dild0s,dildo,dildos,dilld0,dilld0s,dominatricks,dominatrics,dominatrix,dyke,enema,f u c k,f u c k e r,fag,fag1t,faget,fagg1t,faggit,faggot,fagit,fags,fagz,faig,faigs,fart,flipping the bird,fuck,Fudge Packer,fuk,g00k,gay,God-damned,h00r,h0ar,h0re,hells,hoar,hoor,hoore,jackoff,jap,japs,jerk-off,jisim,jiss,jizm,jizz,kunt,kunts,kuntz,Lesbian,Lezzian,Lipshits,Lipshitz,masochist,masokist,massterbait,masstrbait,masstrbate,masterbaiter,masterbate,masterbates,Motha Fucker,Motha Fuker,Motha Fukkah,Motha Fukker,Mother Fucker,Mother Fukah,Mother Fuker,Mother Fukkah,Mother Fukker,mother-fucker,Mutha Fucker,Fuker,Fukker,orgasim;,orgasm,orgasum,peeenus,peenus,peinus,pen1s,penas,penis,penus,penuus,Phuc,Phuck,Phuk,Phuker,Phukker,pusse,pussy,puuke,puuker,queer,qweir,recktum,rectum,screwing,semen,sex,Sh!t,sh1t,sh1ts,sh1tz,shit,shits,slut,tit,turd,va1jina,vag1na,vagiina,vagina,vaj1na,vajina,vullva,vulva,w0p,wh00r,wh0re,whore,xrated,xxx,b!+ch,blowjob,clit,arschloch,shit,b!tch,b17ch,b1tch,bastard,bi+ch,boiolas,buceta,c0ck,cawk,chink,cipa,clits,cock,cum,cunt,dildo,dirsa,ejakulate,fatass,fcuk,fux0r,hoer,hore,l3itch,l3i+ch,lesbian,masturbate,masterbat,masterbat3,motherfucker,pusse,scrotum,shemale,shi+,sh!+,smut,teets,boob,b00bs,w00se,jackoff,wank,whoar,dyke,shit,@$$,amcik,ayir,bi7ch,bollock,breasts,butt-pirate,Cock,cunt,d4mn,dike,foreskin,Fotze,Fu(,futkretzn,h0r,h4x0r,hell,helvete,hoer,honkey,jizz,lesbo,mamhoon,piss,poontsee,poop,porn,p0rn,pr0n,preteen,pula,pule,puta,puto,screw',
						  'fep_cf_efoot' => 'Please DO NOT reply to this email directly. Use our contact form instead.'
						  );
						  
						  
						add_option('FEPCF_admin_options', $options);
						
						}
					}

function fepcf_page_id() {

	global $wpdb;
	
	if ( false === ($id = get_transient('fepcf_page_id'))){
	
		$id = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE '%[fep-contact-form-admin]%' AND post_status = 'publish' AND post_type = 'page' LIMIT 1");
		
		if ($id)
		set_transient('fepcf_page_id', $id, 60*60*24);
		}
		
     return $id;
}

	
	function fepcf_page_url()
		{
			if ( fepcf_page_id() )
			return get_permalink(fepcf_page_id());
			else
			return 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
		}


if ( !function_exists('fepcf_get_option') ) :
	
function fepcf_get_option( $option, $default = '', $section = 'FEPCF_admin_options' ) {
	
    $options = get_option( $section );

    if ( isset( $options[$option] ) ) {
        return $options[$option];
    }

    return $default;
}
	
endif;

function fepcf_is_user_blocked( $login = '' ){
	global $user_login;
	if ( !$login && $user_login )
	$login = $user_login;
	
	if ($login){
	$wpusers = explode(',', str_replace(' ', '', strtolower(fepcf_get_option('have_permission'))));
	//var_dump($wpusers);
		if(in_array( $login, $wpusers))
		return true;
		} //User not logged in
	return false;
}

if ( !function_exists('fepcf_get_userdata') ) :

function fepcf_get_userdata($data, $need = 'ID', $type = 'login' )
		{
			if (!$data)
			return '';
		
			$type = strtolower($type);
			if ( !in_array($type, array ('id', 'slug', 'email', 'login' )))
			return '';
		
			$user = get_user_by( $type , $data);
			if ( $user && in_array($need, array('ID', 'user_login', 'display_name', 'user_email')))
			return $user->$need;
			else
			return '';
		}
endif;

function fepcf_time_delay($DeTime)
    {
		global $wpdb, $user_ID;
		$now = current_time('mysql');
		$Dtime = $DeTime * 60;
		$Prev = $wpdb->get_var($wpdb->prepare("SELECT send_date FROM ".FEP_MESSAGES_TABLE." WHERE from_user = %d ORDER BY send_date DESC LIMIT 1", $user_ID));
	  $diff = strtotime($now) - strtotime($Prev);
	  $diffr = $diff/60;
	  $next = strtotime($Prev) + $Dtime;
	  $Ntime = human_time_diff(strtotime($now),$next);
	   return array('diffr' => $diffr, 'time' => $Ntime);
    }

function fepcf_format_date($date)
    {
		$now = current_time('mysql');
      //return date('M d, h:i a', strtotime($date));
	  $formate = human_time_diff(strtotime($date),strtotime($now)).' '.__('ago', 'fepcf');
	  
	  return apply_filters( 'fepcf_formate_date', $formate, $date );
    }
	
	function fepcf_output_filter($string, $title = false)
    {
		$string = stripslashes($string);
		
	  if ($title) {
	  $html = apply_filters('fepcf_filter_display_title', $string);
	  } else {
	  $html = apply_filters('fepcf_filter_display_message', $string);
	  }
      return $html;
    }

if ( !function_exists('fepcf_error') ) :

function fepcf_error($wp_error){
	if(!is_wp_error($wp_error)){
		return '';
	}
	if(count($wp_error->get_error_messages())==0){
		return '';
	}
	$errors = $wp_error->get_error_messages();
	if (is_admin())
	$html = '<div id="message" class="error">';
	else
	$html = '<div id="fep-wp-error">';
	foreach($errors as $error){
		$html .= '<strong>' . __('Error', 'fepcf') . ': </strong>'.esc_html($error).'<br />';
	}
	$html .= '</div>';
	return $html;
}
	
endif;

if ( !function_exists('fep_create_nonce') ) :
 /**
 * Creates a token usable in a form
 * return nonce with time
 * @return string
 */
	function fep_create_nonce($action = -1) {
   	 $time = time();
    	$nonce = wp_create_nonce($time.$action);
    return $nonce . '-' . $time;
	}	

endif;

if ( !function_exists('fep_verify_nonce') ) :
 /**
 * Check if a token is valid. Mark it as used
 * @param string $_nonce The token
 * @return bool
 */
	function fep_verify_nonce( $_nonce, $action = -1) {

    //Extract timestamp and nonce part of $_nonce
    $parts = explode( '-', $_nonce );
    $nonce = $parts[0]; // Original nonce generated by WordPress.
    $generated = $parts[1]; //Time when generated

    $nonce_life = 60*60; //We want these nonces to have a short lifespan
    $expire = (int) $generated + $nonce_life;
    $time = time(); //Current time
		// bad formatted onetime-nonce
	if ( empty( $nonce ) || empty( $generated ) )
		return false;

    //Verify the nonce part and check that it has not expired
    if( ! wp_verify_nonce( $nonce, $generated.$action ) || $time > $expire )
        return false;

    //Get used nonces
    $used_nonces = get_option('_fep_used_nonces');

    //Nonce already used.
    if( isset( $used_nonces[$nonce] ) )
        return false;

    foreach ($used_nonces as $nonces => $timestamp){
        if( $timestamp < $time ){
        //This nonce has expired, so we don't need to keep it any longer
        unset( $used_nonces[$nonces] );
		}
    }

    //Add nonce to used nonces and sort
    $used_nonces[$nonce] = $expire;
    asort( $used_nonces );
    update_option( '_fep_used_nonces',$used_nonces );
	return true;
}
endif;
	
function fepcf_translation()
	{
	//SETUP TEXT DOMAIN FOR TRANSLATIONS
	load_plugin_textdomain('fepcf', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
	
function fepcf_enqueue_scripts()
    {
	if ( !wp_style_is ( 'fep-style' ) )
	wp_enqueue_style( 'fep-style', FEPCF_PLUGIN_URL . 'style/style.css' );
	$custom_css = trim(fepcf_get_option('custom_css'));
	wp_add_inline_style( 'fep-style', $custom_css );

	
	wp_register_script( 'fepcf-attachment-script', FEPCF_PLUGIN_URL . 'js/attachment.js', array( 'jquery' ), '3.1', true );
	wp_localize_script( 'fepcf-attachment-script', 'fepcf_attachment_script', 
			array( 
				'remove' => esc_js(__('Remove', 'fepcf')),
				'maximum' => esc_js( fepcf_get_option('attachment_no', 4) ),
				'max_text' => esc_js(__('Maximum file allowed', 'fepcf'))
				
			) 
		);
    }

function fepcf_admin_enqueue_scripts()
    {
		wp_register_script( 'fepcf-department-script', FEPCF_PLUGIN_URL . 'js/department.js', array( 'jquery' ), '3.1', true );
		
	}
function fepcf_include_require_files() 
	{
	if ( is_admin() ) 
		{
			$fep_files = array(
							'admin' => 'admin/fepcf-admin-class.php'
							);
										
		} else {
			$fep_files = array(
							'main' => 'fepcf-main-class.php'
							,'menu' => 'fepcf-menu-class.php'
							,'display' => 'fepcf-display-class.php'
							,'email' => 'fepcf-send-email-class.php'
							);
				}
	//$fep_files['widgets'] = 'fep-widgets.php';
	//$fep_files['functions'] = 'functions.php';
	$fep_files['attachment'] = 'fepcf-attachment-class.php';
	$fep_files['if_fep'] = 'front-end-pm/fepcf-if-fep.php';
					
	$fep_files = apply_filters('fepcf_include_files', $fep_files );
	
	foreach ( $fep_files as $fep_file ) {
	require_once ( $fep_file );
		}
	}

function fepcf_backticker_encode($text) {
	$text = $text[1];
    //$text = stripslashes($text); //already done
    $text = str_replace('&amp;lt;', '&lt;', $text);
    $text = str_replace('&amp;gt;', '&gt;', $text);
	$text = htmlspecialchars($text, ENT_QUOTES);
	$text = preg_replace("|\n+|", "\n", $text);
	$text = nl2br($text);
    $text = str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;', $text);
	$text = preg_replace("/^ /", '&nbsp;', $text);
    $text = preg_replace("/(?<=&nbsp;| |\n) /", '&nbsp;', $text);
    
    return "<code>$text</code>";
}
	
function fepcf_backticker_display_code($text) {
    $text = preg_replace_callback("|`(.*?)`|", "fepcf_backticker_encode", $text);
    $text = str_replace('<code></code>', '`', $text);
    return $text;
}
add_filter('fepcf_filter_display_message', 'fepcf_backticker_display_code', 5);

function fepcf_message_filter_content($html) {
    $html = apply_filters('comment_text', $html);
    return $html;
}
add_filter( 'fepcf_filter_display_message', 'fepcf_message_filter_content' );

function fepcf_message_filter_title($html) {
    $html = apply_filters('the_title', $html);
    return $html;
}
add_filter( 'fepcf_filter_display_title', 'fepcf_message_filter_title' );

add_action('template_redirect', 'fepcf_download_file');
function fepcf_download_file()
		{
		if ( !isset($_GET['fepcfaction']) || $_GET['fepcfaction'] != 'download')
		return;
		
			global $wpdb, $user_ID;
			$errors = new WP_Error();
	$id = absint($_GET['id']);

	if ( !fep_verify_nonce($_GET['token'], 'download') )
	$errors->add('invalidToken',__('Invalid token', 'fepcf'));

	$msgsMeta = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".FEP_META_TABLE." WHERE meta_id = %d", $id));
	if (!$msgsMeta)
	$errors->add('noAttachment',__('No attachment found', 'fepcf'));

	$message_id = $msgsMeta->message_id;

	$unserialized_file = maybe_unserialize( $msgsMeta->field_value );
		  
	if ( $msgsMeta->field_name != 'attachment' || !$unserialized_file['type'] || !$unserialized_file['url'] || !$unserialized_file['file'] )
	$errors->add('invalidAttachment',__('Invalid Attachment', 'fepcf'));

		$attachment_type = $unserialized_file['type'];
		$attachment_url = $unserialized_file['url'];
		$attachment_path = $unserialized_file['file'];
		$attachment_name = basename($attachment_url);

	$msgsInfo = $wpdb->get_row($wpdb->prepare("SELECT from_user, to_user, status FROM ".FEP_MESSAGES_TABLE." WHERE id = %d", $message_id));

	if (!$msgsInfo)
	$errors->add('messageDeleted',__('Message already deleted', 'fepcf'));

	if ( $msgsInfo->from_user != $user_ID && $msgsInfo->to_user != $user_ID && $msgsInfo->status != 2 && !current_user_can('manage_options') )
	$errors->add('noPermission',__('No permission', 'fepcf'));

	if(!file_exists($attachment_path)){
	$wpdb->query($wpdb->prepare("DELETE FROM ".FEP_META_TABLE." WHERE meta_id = %d", $id));
	$errors->add('attachmentDeleted',__('Attachment already deleted', 'fepcf'));
	}
	
	if(count($errors->get_error_messages())>0){
	echo fepcf_error($errors);
	echo "<a href='".esc_url( add_query_arg(array('fepcfaction'=>'viewcontact','id'=>$message_id),fepcf_page_url()))."'>".__('Go Back', 'fepcf')."</a>";
	
	} else {
	
		header("Content-Description: File Transfer");
		header("Content-Transfer-Encoding: binary");
		header("Content-Type: $attachment_type", true, 200);
		header("Content-Disposition: attachment; filename=\"$attachment_name\"");
		header("Content-Length: " . filesize($attachment_path));
		nocache_headers();
		
		//clean all levels of output buffering
		while (ob_get_level()) {
    		ob_end_clean();
		}
		
		readfile($attachment_path);
		
			}
			exit;
		}

