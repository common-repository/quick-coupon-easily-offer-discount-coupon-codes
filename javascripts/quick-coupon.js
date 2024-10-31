var quickcouponwp = {
	submit: function(post_id)
	{
		new Ajax.Request(location.href, {
			method: 'post',
			parameters: 'wpcc_get_options=1&post_id=' + post_id + '&wpcc_dcode=' + $('wpcc_dcode' + post_id).value,
			onSuccess: function(transport)
			{
				var opts = transport.responseText.evalJSON()
				
				// create paypal form
				$('wpcc_form' + post_id).setAttribute("action", "https://www.paypal.com/cgi-bin/webscr")
				$('wpcc_business' + post_id).value = opts.business
				$('wpcc_amount' + post_id).value = opts.amount
				$('wpcc_return' + post_id).value = opts.url
				$('wpcc_item_name' + post_id).value = opts.item_name
				$('wpcc_currency_code' + post_id).value = opts.currency_code
				
				// submit the form
				$('wpcc_form' + post_id).submit()
			}
		})
		
		return false
	}
}