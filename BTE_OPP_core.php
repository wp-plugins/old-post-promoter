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
?>