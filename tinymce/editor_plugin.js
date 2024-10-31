(function()
{
	// Load plugin specific language pack
	// tinymce.PluginManager.requireLangPack('quickcouponwp')
	
	tinymce.create('tinymce.plugins.quickcouponwp',
	{
		/**
		 * Initializes the plugin, this will be executed after the plugin has been created.
		 * This call is done before the editor instance has finished it's initialization so use the onInit event
		 * of the editor instance to intercept that event.
		 *
		 * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
		 * @param {string} url Absolute URL to where the plugin is located.
		 */
		init: function(ed, url)
		{
			// Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('mceExample');
			ed.addCommand('mcequickcouponwp', function()
			{
				post_id = tinymce.DOM.get('post_ID').value;
				
				tb_show('Quick Coupon WP', tinymce.documentBaseURL + '?wpcc_iframe=true&post_id='+post_id+'&TB_iframe=true');
				
				tinymce.DOM.setStyle( ['TB_overlay','TB_window','TB_load'], 'z-index', '999999' );
			})
			
			// Register WP Discount Creator button
			ed.addButton('quickcouponwp',
			{
				title : 'Quick Coupon WP',
				cmd : 'mcequickcouponwp',
				image : url + '/images/wpcc_icon.png'
			})
			
			// Add a node change handler, selects the button in the UI when a image is selected
			ed.onNodeChange.add(function(ed, cm, n)
			{
				cm.setActive('quickcouponwp', n.nodeName == 'IMG');
			});
		},
		
		/**
		 * Returns information about the plugin as a name/value array.
		 * The current keys are longname, author, authorurl, infourl and version.
		 *
		 * @return {Object} Name/value array containing information about the plugin.
		 */
		getInfo : function()
		{
			return {
				longname : 'Quick Coupon WP',
				author : 'Some author',
				authorurl : 'http://tinymce.moxiecode.com',
				infourl : 'http://wiki.moxiecode.com/index.php/TinyMCE:Plugins/example',
				version : "1.0"
			}
		}
	});
		
	// Register plugin
	tinymce.PluginManager.add('quickcouponwp', tinymce.plugins.quickcouponwp);
})()