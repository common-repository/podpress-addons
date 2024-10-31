<?php
/*
Plugin Name: EZ powerPress/podPress Addon Widget
Plugin URI: http://wordpress.ieonly.com/category/my-plugins/podpress-addons/
Author: Eli Scheetz
Author URI: http://wordpress.ieonly.com/
Contributors: scheeeli
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8VWNB5QEJ55TJ
Description: This plugin is an Addon to podPress that gives you a Widget to lists your podCasts and links to the popout player. I hope to also make it build a dropdown of mp3s in your uploads directory on the add/edit Post page for easy insertion.
Version: 1.5.09
*/

/*            ___
 *           /  /\     podPressADDONS Main Plugin File
 *          /  /:/     @package podPressADDONS
 *         /__/::\
 Copyright \__\/\:\__  © 2011-2015 Eli Scheetz (email: wordpress@ieonly.com)
 *            \  \:\/\
 *             \__\::/ This program is free software; you can redistribute it
 *     ___     /__/:/ and/or modify it under the terms of the GNU General Public
 *    /__/\   _\__\/ License as published by the Free Software Foundation;
 *    \  \:\ /  /\  either version 2 of the License, or (at your option) any
 *  ___\  \:\  /:/ later version.
 * /  /\\  \:\/:/
  /  /:/ \  \::/ This program is distributed in the hope that it will be useful,
 /  /:/_  \__\/ but WITHOUT ANY WARRANTY; without even the implied warranty
/__/:/ /\__    of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
\  \:\/:/ /\  See the GNU General Public License for more details.
 \  \::/ /:/
  \  \:\/:/ You should have received a copy of the GNU General Public License
 * \  \::/ with this program; if not, write to the Free Software Foundation,    
 *  \__\/ Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA        */

/* --TODO:
//replace the input on the Edit Post page in the admin with a dropdown.
//-- for now I have personally hacked my own podpress_admin_class.php file and replaced
				if(!empty($files) || $this->settings['enablePodangoIntegration']) {
//-- around line 805 with:
//--Dropdown HACK
				$files = scandir('../wp-content/uploads/mp3');
				if (is_array($files)) {
					echo '						<select name="podPressMedia['.$num.'][URI]" id="podPressMedia_'.$num.'_URI" onchange="if(this.value==\'!\') { podPress_customSelectVal(this, \'Specifiy URL.\'); } podPressMediaFiles['.$num.'][\'URI\'] = this.value; podPressDetectType('.$num.'); document.getElementById(\'podPressMedia_'.$num.'_title\').value = this.options[this.selectedIndex].text.replace(\'.mp3\', \'\').replace(/-/g, \' \'); document.getElementById(\'title\').value = this.options[this.selectedIndex].text.replace(\'.mp3\', \'\').replace(/-/g, \' \');">'."\n";
					echo '							<option value="!">'.__('Specify URL ...', 'podpress').'</option>'."\n";
					foreach($files as $MediaFile) {
						if('.'!=($MediaFile) && '..'!=($MediaFile)) {
							echo '								<option value="'.site_url().'/wp-content/uploads/mp3/'.$MediaFile.'"'.$xSelected.'>'.$MediaFile.'</option>'."\n";
						}
					}
					echo '						</select>';
					echo '							<input type="hidden" id="podPressMedia_'.$num.'_cleanURI" value="no" />'."\n";
				} elseif(!empty($files) || $this->settings['enablePodangoIntegration']) {
//--END HACK ***/
function podPressADDONS_install() {
	global $wp_version;
	if (version_compare($wp_version, "2.6", "<"))
		die("This Plugin requires WordPress version 2.6 or higher");
}
register_activation_hook(__FILE__,'podPressADDONS_install');

class podPressADDONS_Widget_Class extends WP_Widget {
	function __construct() {
		parent::__construct('podPressADDONS-Widget', __('List podCasts'), array('classname' => 'podPressADDONS_Widget_Class', 'description' => __('A podPress Addon Widget - Adds links to the pop-out player for each of your podCasts')));
	}
	function widget($args, $instance) {
		global $podPressADDONS_SQL_SELECT, $wp_query, $posts, $post, $podPress, $podPressTemplateData;
		$LIs = '';
		extract($args);
		if (!$instance['title'])
			$instance['title'] = "podCasts";
		if (!$instance['cat'])
			$instance['cat'] = "";
		if (!$instance['popout'])
			$instance['popout'] = "yes";
		if (!is_numeric($instance['number']))
			$instance['number'] = 50;
		if (isset($instance['popout']) && $instance['popout'] == "yes" && ($instance['number'] > 0)) {
			$li=1;
			$myposts = query_posts('showposts='.$instance['number'].$instance['cat']);
//			print_r($myposts);
			if ( have_posts() ) {
				while ( have_posts() ) {
					the_post();
					$enclosure_data = get_post_meta($post->ID, 'enclosure', true);
					if ($enclosure_data) {
						@list($EnclosureURL, $EnclosureSize, $EnclosureType, $Serialized) = @explode("\n", $enclosure_data);
						$powerPressDownloadlinks = the_title('<a href="#powerPressPlayer" class="powerpress_playinpopup_'.$EnclosureType.'" onclick="window.open(\'?powerpress_pinw='.$post->ID.'-podcast\', \'podcast-'.$post->ID.'\', \'width=360,height=24,toolbar=0\'); return false;">', '</a>', false);
						$LIs .= '<li class="powerPressADDONS-Link">'.$powerPressDownloadlinks."</li>\n";
						$li++;
					} elseif ( $post->podPressMedia ) {
						foreach ( $post->podPressMedia as $podCast) {
							$podPressDownloadlinks = '<a href="#podPressPlayerSpace_'.$GLOBALS['podPressPlayer'].'" class="podpress_playinpopup podpress_playinpopup_'.$podCast['type'].'" onclick="javascript:podPressPopupPlayer(\''.$GLOBALS['podPressPlayer'].'\', \''.js_escape($podPress->convertPodcastFileNameToValidWebPath($podCast['URI'])).'\', 290, 24, \''.js_escape(get_bloginfo('name')).'\', \''.$post->ID.'\', \''.js_escape($podCast['title']).'\', \''.js_escape($podCast['artist']).'\'); return false;">'.__($podCast['title'], 'podpress').'</a>';
							$LIs .= '<li class="podPressADDONS-Link">'.$podPressDownloadlinks."</li>\n";
							$li++;
						}
					}
					if ($li > $instance['number'])
						break;
				}
				wp_reset_query();
			}
		}
		if (strlen($LIs) > 0)
			echo $before_widget.$before_title.$instance["title"].$after_title."<ul style=\"margin: 0;\">\n".$LIs."</ul>\n".$after_widget;
	}
	function flush_widget_cache() {
		wp_cache_delete('podPressADDONS_Widget_Class', 'widget');
	}
	function update($new, $old) {
		$instance = $old;
		$instance['title'] = strip_tags($new['title']);
		$instance['number'] = (int) $new['number'];
		$instance['popout'] = strip_tags($new['popout']);
		$instance['cat'] = strip_tags($new['cat']);
		return $instance;
	}
	function form( $instance ) {
		$title = isset($instance['title']) ? esc_attr($instance['title']) : '';
		$number = isset($instance['number']) ? absint($instance['number']) : 50;
		$popout = isset($instance['popout']) ? esc_attr($instance['popout']) : 'yes';
		$cat = isset($instance['cat']) ? ($instance['cat']) : '';
		$cat_opts = '<option value="">No</option>';
		$categories = get_categories();
		foreach ($categories as $category)
			$cat_opts .= '<option value="&cat='.$category->term_id.'"'.($category->term_id.''==substr($cat, 5)?" selected":"").'>'.$category->name.'</option>';
		echo '<p><label for="'.$this->get_field_id('title').'">'.__('Alternate Widget Title').':</label>
		<input type="text" name="'.$this->get_field_name('title').'" id="'.$this->get_field_id('title').'" value="'.$title.'" /></p>
		<p><label for="'.$this->get_field_id('popout').'">'.__('Limit linst to Category').':</label>
		<select name="'.$this->get_field_name('cat').'" id="'.$this->get_field_id('cat').'">'.$cat_opts.'</select></p>
		<p><label for="'.$this->get_field_id('number').'">Number of podCasts to Display:</label>
		<input type="text" size="2" name="'.$this->get_field_name('number').'" id="'.$this->get_field_id('number').'" value="'.$number.'" /></p>';
	}
}
add_action('widgets_init', create_function('', 'return register_widget("podPressADDONS_Widget_Class");'));
