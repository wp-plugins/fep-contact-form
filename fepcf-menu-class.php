<?php

if (!class_exists('fepcf_menu_class'))
{
  class fepcf_menu_class
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
			add_action ('fepcf_menu_button', array(&$this, 'mymessage'));
			if ( current_user_can('manage_options') ) {
			add_action ('fepcf_menu_button', array(&$this, 'allmessage'));
			add_action ('fepcf_menu_button', array(&$this, 'spam'));
			}
    	}

	function mymessage() {
	 $class = 'fep-button';
	 if ( isset($_GET['fepcfaction']) && $_GET['fepcfaction'] == 'mycontactmgs')
	 $class = 'fep-button-active';
	 
	  echo "<a class='$class' href='".esc_url( add_query_arg(array('fepcfaction'=>'mycontactmgs'),fepcf_page_url()))."'>".sprintf(__('My Contact Message%s', 'fepcf'), fepcf_display_class::init()->mycontact_new()).'</a>';
	  }
	  
	 function allmessage() {
	 $class = 'fep-button';
	 if ( isset($_GET['fepcfaction']) && $_GET['fepcfaction'] == 'contactmgs')
	 $class = 'fep-button-active';
	 
	  echo "<a class='$class' href='".esc_url( add_query_arg(array('fepcfaction'=>'contactmgs'),fepcf_page_url()))."'>".sprintf(__('All Contact Message%s', 'fepcf'), fepcf_display_class::init()->getcontact_new()).'</a>';
	  }
	  
	  function spam() {
	 $class = 'fep-button';
	 if ( isset($_GET['fepcfaction']) && $_GET['fepcfaction'] == 'spam')
	 $class = 'fep-button-active';
	 
	  echo "<a class='$class' href='".esc_url( add_query_arg(array('fepcfaction'=>'spam'),fepcf_page_url()))."'>".sprintf(__('Spam Message%s', 'fepcf'), fepcf_display_class::init()->getSpam_new()).'</a>';
	  }

	
	
  } //END CLASS
} //ENDIF

add_action('wp_loaded', array(fepcf_menu_class::init(), 'actions_filters'));
?>