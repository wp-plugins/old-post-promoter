<?php
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
require_once('OldPostPromoter.php');
require_once('BTE_OPP_core.php');

function bte_opp_head_admin() {
	wp_enqueue_script('jquery-ui-tabs');
	$home = get_settings('siteurl');
	$base = '/'.end(explode('/', str_replace(array('\\','/BTE_OPP_admin.php'),array('/',''),__FILE__)));
	$stylesheet = $home.'/wp-content/plugins' . $base . '/css/old_post_promoter.css';
	echo('<link rel="stylesheet" href="' . $stylesheet . '" type="text/css" media="screen" />');
}

function bte_opp_options() {	 	
	$message = null;
	$message_updated = __("Old Post Promoter Options Updated.", 'bte_old_post_promoter');
	if (!empty($_POST['bte_opp_action'])) {
		$message = $message_updated;
		if (isset($_POST['bte_opp_twitter_username'])) {
			update_option('bte_opp_twitter_username',$_POST['bte_opp_twitter_username']);
		}
		if (isset($_POST['bte_opp_twitter_password'])) {
			update_option('bte_opp_twitter_password',$_POST['bte_opp_twitter_password']);
		}
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
		if (isset($_POST['bte_opp_at_top'])) {
			update_option('bte_opp_at_top',$_POST['bte_opp_at_top']);
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
	$atTop = get_option('bte_opp_at_top');
	if (!isset($atTop)) {
		$atTop = 0;
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
	$twitter_username = get_option('bte_opp_twitter_username');
	$twitter_password = get_option('bte_opp_twitter_password');
	
	print('
			<div class="wrap">
				<h2>'.__('Old Post Promoter by', 'OldPostPromoter').' <a href="http://www.blogtrafficexchange.com">Blog Traffic Exchange</a></h2>
				<form id="bte_opp" name="bte_oldpostpromoter" action="'.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=BTE_OPP_admin.php" method="post">
					<input type="hidden" name="bte_opp_action" value="bte_opp_update_settings" />
					<fieldset class="options">
						<div class="option">
							<label for="bte_opp_twitter_username">Enable Tweet on Promotion: '.__('Twitter Username', 'OldPostPromoter').'/'.__('Password', 'OldPostPromoter').':</label>
							<input type="text" size="25" name="bte_opp_twitter_username" id="bte_opp_twitter_username" value="'.$twitter_username.'" autocomplete="off" />
							<input type="password" size="25" name="bte_opp_twitter_password" id="bte_opp_twitter_password" value="'.$twitter_password.'" autocomplete="off" />
						</div>
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
									<option value="365" '.bte_opp_optionselected(365,$ageLimit).'>'.__('365 Days', 'OldPostPromoter').'</option>
									<option value="730" '.bte_opp_optionselected(730,$ageLimit).'>'.__('730 Days', 'OldPostPromoter').'</option>
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
							<label for="bte_opp_at_top">'.__('Show Original Publication Date At Top of Post? ', 'OldPostPromoter').'</label>
							<select name="bte_opp_at_top" id="bte_opp_at_top">
									<option value="1" '.bte_opp_optionselected(1,$atTop).'>'.__('Yes', 'OldPostPromoter').'</option>
									<option value="0" '.bte_opp_optionselected(0,$atTop).'>'.__('No', 'OldPostPromoter').'</option>
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
						<div class="option">
							<h4>Other Blog Traffic Exchange <a href="http://www.blogtrafficexchange.com/wordpress-plugins/">Wordpress Plugins</a></h4>
							<ul>
							<li><a href="http://www.blogtrafficexchange.com/related-websites/">Related Websites</a></li>
							<li><a href="http://www.blogtrafficexchange.com/related-tweets/">Related Tweets</a></li>
							<li><a href="http://www.blogtrafficexchange.com/wordpress-backup/">Wordpress Backup</a></li>
							<li><a href="http://www.blogtrafficexchange.com/blog-copyright/">Blog Copyright</a></li>
							<li><a href="http://www.blogtrafficexchange.com/old-post-promoter/">Old Post Promoter</a></li>
							<li><a href="http://www.blogtrafficexchange.com/related-posts/">Related Posts</a></li>
														</ul>
						</div>
				</form>' );

}

function bte_opp_optionselected($opValue, $value) {
	if($opValue==$value) {
		return 'selected="selected"';
	}
	return '';
}

function bte_opp_options_setup() {	
	add_options_page('OldPostPromoter', 'Old Post Promoter', 10, basename(__FILE__), 'bte_opp_options');
}

?>