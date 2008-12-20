<?php
/*
Plugin Name: Old Post Promoter (OPP)
Plugin URI: http://www.blogtrafficexchange.com/old-post-promoter
Description: Randomly choose an old post and reset the publication date to now.  The effect is to promote older posts by moving them back onto the front page and into the rss feed.  This plugin should only be used with data agnostic permalinks (permalink structures not containing dates). <a href="options-general.php?page=OldPostPromoter.php">Configuration options are here.</a>  "You down with OPP?  Yeah you know me!" 
Version: 1.2.2
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

function bte_opp_head_admin() {
	wp_enqueue_script('jquery-ui-tabs');
	$home = get_settings('siteurl');
	$base = '/'.end(explode('/', str_replace(array('\\','/OldPostPromoter.php'),array('/',''),__FILE__)));
	$stylesheet = $home.'/wp-content/plugins' . $base . '/css/old_post_promoter.css';
	echo('<link rel="stylesheet" href="' . $stylesheet . '" type="text/css" media="screen" />');
}

function bte_opp_options() {	 	
	$message = null;
	$message_updated = __("Old Post Promoter Options Updated.", 'bte_old_post_promoter');
	if (!empty($_POST['bte_opp_action'])) {
		$message = $message_updated;
		if (isset($_POST['bte_opp_interval'])) {
			update_option('bte_opp_interval',$_POST['bte_opp_interval']);
		}
		if (isset($_POST['bte_opp_interval_slop'])) {
			update_option('bte_opp_interval_slop',$_POST['bte_opp_interval_slop']);
		}
		if (isset($_POST['bte_opp_age_limit'])) {
			update_option('bte_opp_age_limit',$_POST['bte_opp_age_limit']);
		}
		if (isset($_POST['bte_opp_show_original_pubdate'])) {
			update_option('bte_opp_show_original_pubdate',$_POST['bte_opp_show_original_pubdate']);
		}
		if (isset($_POST['bte_opp_give_credit'])) {
			update_option('bte_opp_give_credit',$_POST['bte_opp_give_credit']);
		}
		if (isset($_POST['bte_opp_pos'])) {
			update_option('bte_opp_pos',$_POST['bte_opp_pos']);
		}
		if (isset($_POST['post_category'])) {
			update_option('bte_opp_omit_cats',implode(',',$_POST['post_category']));
		}
		else {
			update_option('bte_opp_omit_cats','');			
		}
		
		print('
			<div id="message" class="updated fade">
				<p>'.__('OPP Options Updated.', 'OldPostPromoter').'</p>
			</div>');
	}
	$omitCats = get_option('bte_opp_omit_cats');
	if (!isset($omitCats)) {
		$omitCats = BTE_OPP_OMIT_CATS;
	}
	$ageLimit = get_option('bte_opp_age_limit');
	if (!isset($ageLimit)) {
		$ageLimit = BTE_OPP_AGE_LIMIT;
	}
	$showPub = get_option('bte_opp_show_original_pubdate');
	if (!isset($showPub)) {
		$showPub = 1;
	}
	$bte_opp_give_credit = get_option('bte_opp_give_credit');
	if (!isset($bte_opp_give_credit)) {
		$bte_opp_give_credit = 1;
	}
	$bte_opp_pos = get_option('bte_opp_pos');
	if (!isset($bte_opp_pos)) {
		$bte_opp_pos = 1;
	}
	$interval = get_option('bte_opp_interval');		
	if (!(isset($interval) && is_numeric($interval))) {
		$interval = BTE_OPP_INTERVAL;
	}
	$slop = get_option('bte_opp_interval_slop');		
	if (!(isset($slop) && is_numeric($slop))) {
		$slop = BTE_OPP_INTERVAL_SLOP;
	}
	
	print('
			<div class="wrap">
				<h2>'.__('Old Post Promoter', 'OldPostPromoter').'</h2>
				<form id="bte_opp" name="bte_oldpostpromoter" action="'.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=OldPostPromoter.php" method="post">
					<input type="hidden" name="bte_opp_action" value="bte_opp_update_settings" />
					<fieldset class="options">
						<div class="option">
							<label for="bte_opp_interval">'.__('Minimum Interval Between Old Post Promotions: ', 'OldPostPromoter').'</label>
							<select name="bte_opp_interval" id="bte_opp_interval">
									<option value="'.BTE_OPP_1_HOUR.'" '.bte_opp_optionselected(BTE_OPP_1_HOUR,$interval).'>'.__('1 Hour', 'OldPostPromoter').'</option>
									<option value="'.BTE_OPP_4_HOURS.'" '.bte_opp_optionselected(BTE_OPP_4_HOURS,$interval).'>'.__('4 Hours', 'OldPostPromoter').'</option>
									<option value="'.BTE_OPP_6_HOURS.'" '.bte_opp_optionselected(BTE_OPP_6_HOURS,$interval).'>'.__('6 Hours', 'OldPostPromoter').'</option>
									<option value="'.BTE_OPP_12_HOURS.'" '.bte_opp_optionselected(BTE_OPP_12_HOURS,$interval).'>'.__('12 Hours', 'OldPostPromoter').'</option>
									<option value="'.BTE_OPP_24_HOURS.'" '.bte_opp_optionselected(BTE_OPP_24_HOURS,$interval).'>'.__('24 Hours (1 day)', 'OldPostPromoter').'</option>
									<option value="'.BTE_OPP_48_HOURS.'" '.bte_opp_optionselected(BTE_OPP_48_HOURS,$interval).'>'.__('48 Hours (2 days)', 'OldPostPromoter').'</option>
									<option value="'.BTE_OPP_72_HOURS.'" '.bte_opp_optionselected(BTE_OPP_72_HOURS,$interval).'>'.__('72 Hours (3 days)', 'OldPostPromoter').'</option>
									<option value="'.BTE_OPP_168_HOURS.'" '.bte_opp_optionselected(BTE_OPP_168_HOURS,$interval).'>'.__('168 Hours (7 days)', 'OldPostPromoter').'</option>
							</select>
						</div>
						<div class="option">
							<label for="bte_opp_interval_slop">'.__('Randomness Interval (added to minimum interval): ', 'OldPostPromoter').'</label>
							<select name="bte_opp_interval_slop" id="bte_opp_interval_slop">
									<option value="'.BTE_OPP_1_HOUR.'" '.bte_opp_optionselected(BTE_OPP_1_HOUR,$slop).'>'.__('Upto 1 Hour', 'OldPostPromoter').'</option>
									<option value="'.BTE_OPP_4_HOURS.'" '.bte_opp_optionselected(BTE_OPP_4_HOURS,$slop).'>'.__('Upto 4 Hours', 'OldPostPromoter').'</option>
									<option value="'.BTE_OPP_6_HOURS.'" '.bte_opp_optionselected(BTE_OPP_6_HOURS,$slop).'>'.__('Upto 6 Hours', 'OldPostPromoter').'</option>
									<option value="'.BTE_OPP_12_HOURS.'" '.bte_opp_optionselected(BTE_OPP_12_HOURS,$slop).'>'.__('Upto 12 Hours', 'OldPostPromoter').'</option>
									<option value="'.BTE_OPP_24_HOURS.'" '.bte_opp_optionselected(BTE_OPP_24_HOURS,$slop).'>'.__('Upto 24 Hours (1 day)', 'OldPostPromoter').'</option>
							</select>
						</div>
						<div class="option">
							<label for="bte_opp_age_limit">'.__('Post Age Before Eligible for Promotion: ', 'OldPostPromoter').'</label>
							<select name="bte_opp_age_limit" id="bte_opp_age_limit">
									<option value="30" '.bte_opp_optionselected(30,$ageLimit).'>'.__('30 Days', 'OldPostPromoter').'</option>
									<option value="60" '.bte_opp_optionselected(60,$ageLimit).'>'.__('60 Days', 'OldPostPromoter').'</option>
									<option value="90" '.bte_opp_optionselected(90,$ageLimit).'>'.__('90 Days', 'OldPostPromoter').'</option>
									<option value="120" '.bte_opp_optionselected(120,$ageLimit).'>'.__('120 Days', 'OldPostPromoter').'</option>
									<option value="240" '.bte_opp_optionselected(240,$ageLimit).'>'.__('240 Days', 'OldPostPromoter').'</option>
							</select>
						</div>
						<div class="option">
							<label for="bte_opp_pos">'.__('Promote post to position (choosing the 2nd position will leave the most recent post in place): ', 'OldPostPromoter').'</label>
							<select name="bte_opp_pos" id="bte_opp_pos">
									<option value="1" '.bte_opp_optionselected(1,$bte_opp_pos).'>'.__('1st Position', 'OldPostPromoter').'</option>
									<option value="2" '.bte_opp_optionselected(2,$bte_opp_pos).'>'.__('2nd Position', 'OldPostPromoter').'</option>
							</select>
						</div>
						<div class="option">
							<label for="bte_opp_show_original_pubdate">'.__('Show Original Publication Date at Post End? ', 'OldPostPromoter').'</label>
							<select name="bte_opp_show_original_pubdate" id="bte_opp_show_original_pubdate">
									<option value="1" '.bte_opp_optionselected(1,$showPub).'>'.__('Yes', 'OldPostPromoter').'</option>
									<option value="0" '.bte_opp_optionselected(0,$showPub).'>'.__('No', 'OldPostPromoter').'</option>
							</select>
						</div>
						<div class="option">
							<label for="bte_opp_give_credit">'.__('Give OPP Credit with Link? ', 'OldPostPromoter').'</label>
							<select name="bte_opp_give_credit" id="bte_opp_give_credit">
									<option value="1" '.bte_opp_optionselected(1,$bte_opp_give_credit).'>'.__('Yes', 'OldPostPromoter').'</option>
									<option value="0" '.bte_opp_optionselected(0,$bte_opp_give_credit).'>'.__('No', 'OldPostPromoter').'</option>
							</select>
						</div>
							<ul id="category-tabs"> 
        						<li class="ui-tabs-selected"><a href="#categories-all" 
									tabindex="3">'.__('Categories to Omit from Promotion: ', 'OldPostPromoter').'</a></li> 
							</ul> 
						    	<div id="categories-all" class="ui-tabs-panel"> 
						    		<ul id="categorychecklist" class="list:category categorychecklist form-no-clear">
								');
	wp_category_checklist(0, 0, explode(',',$omitCats));
	print('				    		</ul>
								<div>
					</fieldset>
					<p class="submit">
						<input type="submit" name="submit" value="'.__('Update OPP Options', 'OldPostPromoter').'" />
					</p>
				</form>' );

}

function bte_opp_optionselected($opValue, $value) {
	if($opValue==$value) {
		return 'selected="selected"';
	}
	return '';
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
}

function bte_opp_options_setup() {	
	add_options_page('OPP', 'OPP', 10, basename(__FILE__), 'bte_opp_options');
}

function bte_opp_old_post_promoter () {
	if (bte_opp_update_time()) {
		bte_opp_promote_old_post();
		update_option('bte_opp_last_update', time());
	}
}

function bte_opp_promote_old_post () {
	global $wpdb;
	$omitCats = get_option('bte_opp_omit_cats');
	$ageLimit = get_option('bte_opp_age_limit');
	if (!isset($omitCats)) {
		$omitCats = BTE_OPP_OMIT_CATS;
	}
	if (!isset($ageLimit)) {
		$ageLimit = BTE_OPP_AGE_LIMIT;
	}
	$sql = "SELECT ID
            FROM $wpdb->posts
            WHERE post_type = 'post'
                  AND post_status = 'publish'
                  AND post_date < curdate( ) - INTERVAL ".$ageLimit." DAY 
                  ";
    if ($omitCats!='') {
    	$sql = $sql."AND NOT(ID IN (SELECT tr.object_id 
                                    FROM $wpdb->terms  t 
                                          inner join $wpdb->term_taxonomy tax on t.term_id=tax.term_id and tax.taxonomy='category' 
                                          inner join $wpdb->term_relationships tr on tr.term_taxonomy_id=tax.term_taxonomy_id 
                                    WHERE t.term_id IN (".$omitCats.")))";
    }            
    $sql = $sql."            
            ORDER BY RAND() 
            LIMIT 1 ";
	$oldest_post = $wpdb->get_var($sql);   
	if (isset($oldest_post)) {
		bte_opp_update_old_post($oldest_post);
	}
}

function bte_opp_update_old_post($oldest_post) {
	global $wpdb;
	$origPubDate = get_post_meta($oldest_post, 'bte_opp_original_pub_date', true); 
	if (!(isset($origPubDate) && $origPubDate!='')) {
	    $sql = "SELECT post_date from ".$wpdb->posts." WHERE ID = '$oldest_post'";
		$origPubDate=$wpdb->get_var($sql);
		add_post_meta($oldest_post, 'bte_opp_original_pub_date', $origPubDate);
		$origPubDate = get_post_meta($oldest_post, 'bte_opp_original_pub_date', true); 
	}
	$bte_opp_pos = get_option('bte_opp_pos');
	if (!isset($bte_opp_pos)) {
		$bte_opp_pos = 0;
	}
	if ($bte_opp_pos==1) {
		$new_time = date('Y-m-d H:i:s');
		$gmt_time = get_gmt_from_date($new_time);
	} else {
		$lastposts = get_posts('numberposts=1&offset=1');
		foreach ($lastposts as $lastpost) {
			$post_date = strtotime($lastpost->post_date);
			$new_time = date('Y-m-d H:i:s',mktime(date("H",$post_date),date("i",$post_date),date("s",$post_date)+1,date("m",$post_date),date("d",$post_date),date("Y",$post_date)));
			$gmt_time = get_gmt_from_date($new_time);
		}
	}
	$sql = "UPDATE $wpdb->posts SET post_date = '$new_time',post_date_gmt = '$gmt_time',post_modified = '$new_time',post_modified_gmt = '$gmt_time' WHERE ID = '$oldest_post'";		
	$wpdb->query($sql);
	if (function_exists('wp_cache_flush')) {
		wp_cache_flush();
	}		
}

function bte_opp_the_content($content) {
	global $post;
	$showPub = get_option('bte_opp_show_original_pubdate');
	if (!isset($showPub)) {
		$showPub = 1;
	}
	$givecredit = get_option('bte_opp_give_credit');
	if (!isset($givecredit)) {
		$givecredit = 1;
	}
	$origPubDate = get_post_meta($post->ID, 'bte_opp_original_pub_date', true);
	if (isset($origPubDate) && $origPubDate!='') {
		if ($showPub || $givecredit) {
			$content.='<p id="bte_opp"><small>';
			if ($showPub) {
				$content.='Originally posted '.$origPubDate.'. ';
			}
			if ($givecredit) {
					$content.='Republished by  <a href="http://www.blogtrafficexchange.com/old-post-promoter">Old Post Promoter</a>';
			}
			$content.='</small></p>';
		}
	}
	return $content;
}

function bte_opp_update_time () {
	$last = get_option('bte_opp_last_update');		
	$interval = get_option('bte_opp_interval');		
	if (!(isset($interval) && is_numeric($interval))) {
		$interval = BTE_OPP_INTERVAL;
	}
	$slop = get_option('bte_opp_interval_slop');		
	if (!(isset($slop) && is_numeric($slop))) {
		$slop = BTE_OPP_INTERVAL_SLOP;
	}
	if (false === $last) {
		$ret = 1;
	} else if (is_numeric($last)) { 
		$ret = ( (time() - $last) > ($interval+rand(0,$slop)));
	}
	return $ret;
}