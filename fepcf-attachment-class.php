<?php

if (!class_exists('fepcf_attachment_class'))
{
  class fepcf_attachment_class
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
	add_action ('fepcf_admin_setting_form', array(&$this, 'settings'));
	add_action ('fepcf_action_admin_setting_before_save', array(&$this, 'settings_action'));
	add_action ('fepcf_display_after_content', array(&$this, 'display_attachment'));
	add_action ('fepcf_switch_download', array(&$this, 'download_file'));
	add_action ('fepcf_message_before_delete', array(&$this, 'delete_file'), 10, 2 );
	
	if ( '1' == fepcf_get_option('allow_attachment',0)) {
	add_action ('fepcf_message_form_after_content', array(&$this, 'attachment_fields'));
	add_action ('fepcf_action_message_before_send', array(&$this, 'check_upload'));
	add_action ('fepcf_action_message_after_send', array(&$this, 'upload_attachment'), 10, 2 );
		}
    }
	
	function settings() {
	echo "<tr><td><input type='checkbox' id='fep-attachment-checkbox' name='allow_attachment' value='1' ".checked(fepcf_get_option('allow_attachment',0), '1', false)." />".__("Allow to send attachment", 'fepcf')."<br /><small>".__("Set maximum size of attachment", 'fepcf')."</small></td><td class='fep-show-if-checked' style='display:none;'><input type='text' name='attachment_size' value='".fepcf_get_option('attachment_size','4MB')."' /><br /><small>".__("Use KB, MB or GB.(eg. 4MB)", 'fepcf')."</small></td></tr>";
	echo "<tr class='fep-show-if-checked' style='display:none;'><td>".__("Maximum Number of attachment?", 'fepcf')."<br /><small>".__("Set maximum number of attachment", 'fepcf')."</small></td><td><input type='text' name='attachment_no' value='".fepcf_get_option('attachment_no','4')."' /><br /><small>".__("0 = unlimited", 'fepcf')."</small></td></tr>";
	?>
	<script type="text/javascript">
	if(jQuery('#fep-attachment-checkbox').attr("checked")) {
                        jQuery('.fep-show-if-checked').show();
						}
	jQuery('#fep-attachment-checkbox').change( function() {
                    if(jQuery(this).attr("checked")) {
                        jQuery('.fep-show-if-checked').show();
                    } else {
                        jQuery('.fep-show-if-checked').hide();
                    }
                });
				</script>
				<?php
	}
	
	function settings_action ( $errors ) {
			if ( !ctype_digit($_POST['attachment_no']))
			$errors->add('invalid_att_no', __('You must enter a valid number as attachment number!', 'fepcf'));
			}
		

	function attachment_fields() {
	
	wp_enqueue_script( 'fepcf-attachment-script' );
       
            ?>
		<div id="fep_upload">
            <div id="p-0">
                <input type="file" name="fep_upload[]" /><a href="#" onclick="fepcf_remove_element('p-0'); return false;" class="fep-attachment-field"><?php echo __('Remove', 'fepcf') ; ?></a>
            </div>
        </div>
		<a id="fep-attachment-field-add" href="#" onclick="fepcf_add_new_file_field(); return false;"><?php echo __('Add new field', 'fepcf') ; ?></a>
		<div id="fep-attachment-note"></div>
		
            <?php
        }

function check_upload($errors) {
    $mime = get_allowed_mime_types();

    $size_limit = (int) wp_convert_hr_to_bytes(fepcf_get_option('attachment_size','4MB'));
    $fields = (int) fepcf_get_option('attachment_no', 4);

    for ($i = 0; $i < $fields; $i++) {
        $tmp_name = isset( $_FILES['fep_upload']['tmp_name'][$i] ) ? basename( $_FILES['fep_upload']['tmp_name'][$i] ) : '' ;
        $file_name = isset( $_FILES['fep_upload']['name'][$i] ) ? basename( $_FILES['fep_upload']['name'][$i] ) : '' ;

        //if file is uploaded
        if ( $tmp_name ) {
            $attach_type = wp_check_filetype( $file_name );
            $attach_size = $_FILES['fep_upload']['size'][$i];

            //check file size
            if ( $attach_size > $size_limit ) {
                $errors->add('AttachmentSize', sprintf(__( "Attachment (%s) file is too big", 'fepcf' ),$file_name));
            }

            //check file type
            if ( !in_array( $attach_type['type'], $mime ) ) {
                $errors->add('AttachmentType', sprintf(__( "Invalid attachment file type.Allowed Types are (%s)", 'fepcf' ),implode(',',$mime)));
            }
        } // if $filename
    }// endfor

    //return $errors;
}

function upload_attachment( $message_id, $message ) {
    if ( !isset( $_FILES['fep_upload'] ) ) {
        return false;
    }
	add_filter('upload_dir', array(&$this, 'upload_dir'));
	
    $fields = (int) fepcf_get_option('attachment_no', 4);

    for ($i = 0; $i < $fields; $i++) {
        $tmp_name = isset( $_FILES['fep_upload']['tmp_name'][$i] ) ? basename( $_FILES['fep_upload']['tmp_name'][$i] ) : '' ;

        //if ( $file_name ) {
            if ( $tmp_name ) {
                $upload = array(
                    'name' => $_FILES['fep_upload']['name'][$i],
                    'type' => $_FILES['fep_upload']['type'][$i],
                    'tmp_name' => $_FILES['fep_upload']['tmp_name'][$i],
                    'error' => $_FILES['fep_upload']['error'][$i],
                    'size' => $_FILES['fep_upload']['size'][$i]
                );

                $this->upload_file( $upload, $message_id);
            }//file exists
        }// end for
    //}
	remove_filter('upload_dir', array(&$this, 'upload_dir'));
}

	function upload_dir($upload) {
	/* Append year/month folders if that option is set */
		$subdir = '';
        if ( get_option( 'uploads_use_yearmonth_folders' ) ) {
                $time = current_time( 'mysql' );

            $y = substr( $time, 0, 4 );
            $m = substr( $time, 5, 2 );

            $subdir = "/$y/$m";    
        }
	$upload['subdir']	= '/fep-contact-form' . $subdir;
	$upload['path']		= $upload['basedir'] . $upload['subdir'];
	$upload['url']		= $upload['baseurl'] . $upload['subdir'];
	return $upload;
	}

/**
 * Generic function to upload a file
 *
 * @since 0.8
 * @param string $field_name file input field name
 * @return bool|int attachment id on success, bool false instead
 */
function upload_file( $upload_data, $message_id ) {
	global $wpdb;
	if ( ! function_exists( 'wp_handle_upload' ) ) require_once( ABSPATH . 'wp-admin/includes/file.php' );
	
    $movefile = wp_handle_upload( $upload_data, array('test_form' => false) );

    if ($message_id && $movefile['type']&& $movefile['url'] && $movefile['file']) {
	
		$serialized_file = maybe_serialize( $movefile );
		
		$result = $wpdb->insert( FEP_META_TABLE, array( 'message_id' => $message_id, 'field_name' => 'attachment','field_value' => $serialized_file ), array ( '%d', '%s', '%s' ));
		
		if ( $result )
        return true;
    }

    return false;
}

	function display_attachment($message_id) {
	global $wpdb;
	$token = fep_create_nonce('download');
	
	$attachment = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".FEP_META_TABLE." WHERE message_id = %d AND field_name = %s",$message_id, 'attachment'));
	
	if ($attachment) {
		  echo "<hr /><strong>" . __("Attachment", 'fepcf') . ":</strong><br />";
		  foreach ($attachment as $meta){
		  
		  $unserialized_file = maybe_unserialize( $meta->field_value );
		  
		if ( $unserialized_file['type'] && $unserialized_file['url'] && $unserialized_file['file'] ) {
		$attachment_id = $meta->meta_id; 
		
		echo "<a href='".esc_url( add_query_arg(array('fepcfaction'=>'download', 'id' => $attachment_id, 'token' => $token ),fepcf_page_url()))."' title='Download ". basename($unserialized_file['url'])."'>". basename($unserialized_file['url'])."</a><br />"; } 
				} 
			}
		}
		
		function delete_file( $delID, $ids ) {
	global $wpdb;
	
	$id = implode(',',$ids);
	  $results = $wpdb->get_col($wpdb->prepare("SELECT field_value FROM ".FEP_META_TABLE." WHERE field_name = %s AND message_id IN ({$id})", 'attachment' ));
	  foreach ($results as $result){
	  	$unserialized_file = maybe_unserialize( $result );
		if ( $unserialized_file['file'] )
		unlink($unserialized_file['file']);
		}
		
		if ( $result )
        return true;
		return false;
    }
	
	function delete_announcement_file( $delID ) {
	global $wpdb;
	
	  $results = $wpdb->get_col($wpdb->prepare("SELECT field_value FROM ".FEP_META_TABLE." WHERE field_name = %s AND message_id = %d", 'attachment', $delID ));
	  foreach ($results as $result){
	  	$unserialized_file = maybe_unserialize( $result );
		if ( $unserialized_file['file'] )
		unlink($unserialized_file['file']);
		}
		
		if ( $result )
        return true;
		return false;
    }

	
	
	
  } //END CLASS
} //ENDIF

add_action('wp_loaded', array(fepcf_attachment_class::init(), 'actions_filters'));
?>