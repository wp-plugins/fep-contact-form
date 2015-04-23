	var $ = jQuery; //if you use google CDN version of jQuery comment this line.
    var fepcf_Index = 1;
    function fepcf_get_by_id(id) { return document.getElementById(id); }
    function fepcf_create_element(name) { return document.createElement(name); }
    function fepcf_remove_element(id) {
        var e = fepcf_get_by_id(id);
        e.parentNode.removeChild(e);
    }
    function fepcf_add_new_file_field() {
        var maximum = fepcf_attachment_script.maximum;
        var num_img = $('input[name="fep_upload[]"]').size() + $("a.delete").size();
        if((maximum!=0 && num_img<maximum) || maximum==0) {
            var id = 'p-' + fepcf_Index++;

            var i = fepcf_create_element('input');
            i.setAttribute('type', 'file');
            i.setAttribute('name', 'fep_upload[]');

            var a = fepcf_create_element('a');
			a.setAttribute('class', 'fep-attachment-field');
            a.setAttribute('href', '#');
            a.setAttribute('divid', id);
            a.onclick = function() { fepcf_remove_element(this.getAttribute('divid')); return false; }
            a.appendChild(document.createTextNode(fepcf_attachment_script.remove));

            var d = fepcf_create_element('div');
            d.setAttribute('id', id);
            d.setAttribute('style','padding: 4px 0;')

            d.appendChild(i);
            d.appendChild(a);

            fepcf_get_by_id('fep_upload').appendChild(d);

        } else {
            alert(fepcf_attachment_script.max_text+' '+fepcf_attachment_script.maximum);
        }
    }
    // Listener: automatically add new file field when the visible ones are full.
	// Listener: automatically hide file field when maximum field reached.
	function fepcf_listener() {
		fepcf_add_file_field();
		fepcf_hide_file_field();
	}
		
    setInterval("fepcf_listener()", 1000);
    /**
     * Timed: if there are no empty file fields, add new file field.
     */
    function fepcf_add_file_field() {
        var count = 0;
        $('input[name="fep_upload[]"]').each(function(index) {
            if ( $(this).val() == '' ) {
                count++;
            }
        });
        var maximum = fepcf_attachment_script.maximum;
        var num_img = $('input[name="fep_upload[]"]').size() + $("a.delete").size();
        if (count == 0 && (maximum==0 || (maximum!=0 && num_img<maximum))) {
            fepcf_add_new_file_field();
        }
    }
	function fepcf_hide_file_field() {
        var maximum = fepcf_attachment_script.maximum;
        var num_img = $('input[name="fep_upload[]"]').size() + $("a.delete").size();
        if (maximum!=0 && num_img>maximum-1) {
			//alert('maximum');
            $('#fep-attachment-field-add').hide();
			$('#fep-attachment-note').html(fepcf_attachment_script.max_text+' '+fepcf_attachment_script.maximum);
        } else {
			$('#fep-attachment-field-add').show();
			$('#fep-attachment-note').html('');
		}
    }