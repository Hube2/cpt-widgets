	
	function blunt_tax_widget_change_tax(tax_field, term_field, title_field, hierarchical_field) {
		var $tax_select = document.getElementById(tax_field);
		var $tax = $tax_select[$tax_select.selectedIndex].value;
		//alert($tax);
		jQuery.post(
			blunt_tax_widget._ajax_url, {
					'action': 'blunt_tax_widget_change_tax',
					'taxonomy': $tax,
					'nonce': blunt_tax_widget._nonce
				},
			function (data) {
				if (!data) {
					document.getElementById(term_field+'-p').style.display = 'none';
					return;
				}
				if (data.length) {
					document.getElementById(term_field+'-p').style.display = 'block';
				} else {
					document.getElementById(term_field+'-p').style.display = 'none';
				}
				//alert(data);
				jQuery('#'+term_field).empty();
				var count = data.length;
				for (i=0; i<count; i++) {
					var $item = '<option value="'+data[i]['value']+'"'+
															'>'+data[i]['label']+'</option>';
					jQuery('#'+term_field).append($item);
				} // end for
			}, 'json'
		);
		jQuery.post(
			blunt_tax_widget._ajax_url, {
					'action': 'blunt_tax_widget_change_tax_title',
					'taxonomy': $tax,
					'nonce': blunt_tax_widget._nonce
				},
			function (data) {
				document.getElementById(title_field).value = data;
			}
		);
		jQuery.post(
			blunt_tax_widget._ajax_url, {
					'action': 'blunt_tax_widget_change_is_hierarchical',
					'taxonomy': $tax,
					'nonce': blunt_tax_widget._nonce
				},
			function (data) {
				if (!data) {
					document.getElementById(hierarchical_field).checked = false;
					document.getElementById(hierarchical_field+'-container').style.display = 'none';
				} else {
					document.getElementById(hierarchical_field+'-container').style.display = 'inline';
				}
			}, 'json'
		);
	} // end function blunt_tax_widget_change_tax