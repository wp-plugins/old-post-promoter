<?php
/*
Plugin Name: Old Post Promoter (by BTE)
Plugin URI: http://www.blogtrafficexchange.com/old-post-promoter
Description: Randomly choose an old post and reset the publication date to now.  The effect is to promote older posts by moving them back onto the front page and into the rss feed.  This plugin should only be used with data agnostic permalinks (permalink structures not containing dates). <a href="options-general.php?page=BTE_OPP_admin.php">Configuration options are here.</a>  "You down with OPP?  Yeah you know me!" 
Version: 1.7.8
Author: Blog Traffic Exchange
Author URI: http://www.blogtrafficexchange.com/
Donate: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=1777819
License: GNU GPL
*/
/*  Copyright 2008-2009  Blog Traffic Exchange (email : kevin@blogtrafficexchange.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
require_once('BTE_OPP_admin.php');
require_once('BTE_OPP_core.php');
if (!class_exists('xmlrpcmsg')) {
	require_once('lib/xmlrpc.inc');
}		

define ('BTE_RT_API_POST_STATUS', 'http://twitter.com/statuses/update.json');

define ('BTE_OPP_XMLRPC_URI', 'bteservice.com'); 
define ('BTE_OPP_XMLRPC', 'bte.php'); 

define ('BTE_OPP_1_HOUR', 60*60); 
define ('BTE_OPP_4_HOURS', 4*BTE_OPP_1_HOUR); 
define ('BTE_OPP_6_HOURS', 6*BTE_OPP_1_HOUR); 
define ('BTE_OPP_12_HOURS', 12*BTE_OPP_1_HOUR); 
define ('BTE_OPP_24_HOURS', 24*BTE_OPP_1_HOUR); 
define ('BTE_OPP_48_HOURS', 48*BTE_OPP_1_HOUR); 
define ('BTE_OPP_72_HOURS', 72*BTE_OPP_1_HOUR); 
define ('BTE_OPP_168_HOURS', 168*BTE_OPP_1_HOUR); 
define ('BTE_OPP_INTERVAL', BTE_OPP_12_HOURS); 
define ('BTE_OPP_INTERVAL_SLOP', BTE_OPP_4_HOURS); 
define ('BTE_OPP_AGE_LIMIT', 120); // 120 days
define ('BTE_OPP_OMIT_CATS', ""); 

register_activation_hook(__FILE__, 'bte_opp_activate');
register_deactivation_hook(__FILE__, 'bte_opp_deactivate');
add_action('init', 'bte_opp_old_post_promoter');
add_action('admin_menu', 'bte_opp_options_setup');
add_action('admin_head', 'bte_opp_head_admin');
add_filter('the_content', 'bte_opp_the_content');
add_filter('plugin_action_links', 'bte_opp_plugin_action_links', 10, 2);

function bte_opp_plugin_action_links($links, $file) {
	$plugin_file = basename(__FILE__);
	if (basename($file) == $plugin_file) {
		$settings_link = '<a href="options-general.php?page=BTE_OPP_admin.php">'.__('Settings', 'RelatedTweets').'</a>';
		array_unshift($links, $settings_link);
	}
	return $links;
}


function bte_opp_deactivate() {
	delete_option('bte_opp_give_credit');
}

function bte_opp_activate() {
	add_option('bte_opp_interval',BTE_OPP_INTERVAL);
	add_option('bte_opp_interval_slop',BTE_OPP_INTERVAL_SLOP);
	add_option('bte_opp_age_limit',BTE_OPP_AGE_LIMIT);
	add_option('bte_opp_omit_cats',BTE_OPP_OMIT_CATS);
	add_option('bte_opp_show_original_pubdate',1);	
	add_option('bte_opp_pos',0);	
	add_option('bte_opp_give_credit',1);	
	add_option('bte_opp_at_top',0);	
}
?>