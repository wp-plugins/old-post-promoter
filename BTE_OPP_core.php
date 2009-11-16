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
		update_option('bte_opp_last_update', time());
		bte_opp_promote_old_post();
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
	$post = get_post($oldest_post);
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
		
	$permalink = get_permalink($oldest_post);
	$shorturl = bte_opp_get_short_url($permalink);
	bte_opp_tweet($post->post_title." ".$shorturl." #OPP");
	
	//reping
	$services = get_settings('ping_sites');
	$services = preg_replace("|(\s)+|", '$1', $services);
	$services = trim($services);
	if ( '' != $services ) {
		set_time_limit(300);
		$services = explode("\n", $services);
		foreach ($services as $service) {
			bte_opp_sendXmlrpc($service,$permalink);
		}
	}
}

function bte_opp_get_short_url($url) {
	$shorturl = $url;
	$wppost = array();
	$wppost["site"] = get_option('siteurl');
	$wppost["url"] = $url;
	$f=new xmlrpcmsg('bte.shorturl',
		array(php_xmlrpc_encode($wppost))
	);
	$c=new xmlrpc_client(BTE_OPP_XMLRPC, BTE_OPP_XMLRPC_URI, 80);
	if (false) {
		$c->setDebug(1);
	}
	$r=&$c->send($f);
	if(!$r->faultCode()) {
		$sno=$r->value();
		if ($sno->kindOf()!="array") {
			$err="Found non-array as parameter 0";
		} else {
			for($i=0; $i<$sno->arraysize(); $i++)
			{
				$rec=$sno->arraymem($i);
				$shorturl = $rec->structmem("shorturl");
				if ($shorturl!=null) {
					$shorturl = $shorturl->scalarval();
				}	
			}		
		}
	} else {
		error_log("[".date('Y-m-d H:i:s')."][bte_opp.bte_opp_get_short_url] ".$post->guid." error code: ".htmlspecialchars($r->faultCode()));
		error_log("[".date('Y-m-d H:i:s')."][bte_opp.bte_opp_get_short_url] ".$post->guid." reason: ".htmlspecialchars($r->faultString()));
	}
	return $shorturl;
}


/**
 * A modified version of WP's ping functionality "weblog_ping" in functions.php
 * Uses correct extended Ping format and logs response from service.
 * @param string $server
 * @param string $path
 */
function bte_opp_sendXmlrpc($server, $permalink) {
	include_once (ABSPATH . WPINC . '/class-IXR.php');
	$path = '';
	// using a timeout of 3 seconds should be enough to cover slow servers
	$client = new IXR_Client($server, ((!strlen(trim($path)) || ('/' == $path)) ? false : $path));
	$client->timeout = 3;
	$client->useragent .= ' -- WordPress/OPP';

	// when set to true, this outputs debug messages by itself
	$client->debug = false;
	$home = trailingslashit(get_option('home'));
			
	///$this->_post_title = $this->_post_title.'###'.$check_url;///
	// the extendedPing format should be "blog name", "blog url", "permalink", and "feed url",
	// but it would seem as if the standard has been mixed up. It's therefore good to repeat the feed url.
	// $this->_post_type = 2 if new post and 3 if future post
	if ( $client->query('weblogUpdates.extendedPing', get_settings('blogname'), $home, $permalink, get_bloginfo('rss2_url')) ) { 
		//error_log($server." was successfully pinged (extended format)");
	} else {
		if ( $client->query('weblogUpdates.ping', get_settings('blogname'), $home) ) {
			//error_log($server." was successfully pinged");
		} else {
			//error_log($server." could not be pinged. Error message: \"".$client->error->message."\"");
		}
	}
}


function bte_opp_tweet($tweet) {
	$user = get_option('bte_opp_twitter_username');
	$pass = get_option('bte_opp_twitter_password');
	if (empty($user) 
		|| empty($pass) 
		|| empty($tweet)
	) {
		return;
	}
	//I guess I am supposed to do this with OAuth but that seems way to hard given the nature of plugins
	require_once(ABSPATH.WPINC.'/class-snoopy.php');
	$snoop = new Snoopy;
	$snoop->agent = 'Old Post Promoter http://www.blogtrafficexchange.com/old-post-promoter';
	$snoop->rawheaders = array(
		'X-Twitter-Client' => 'Old Post Promoter'
		, 'X-Twitter-Client-Version' => '1.7.1'
		, 'X-Twitter-Client-URL' => 'http://www.blogtrafficexchange.com/old-post-promoter.xml'
	);
	$snoop->user = $user;
	$snoop->pass = $pass;
	$snoop->submit(
		BTE_RT_API_POST_STATUS
		, array(
			'status' => $tweet
			, 'source' => 'Old Post Promoter'
		)
	);
	if (strpos($snoop->response_code, '200')) {
		return true;
	}
	return false;
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
	$dateline = '';
	if (isset($origPubDate) && $origPubDate!='') {
		if ($showPub || $givecredit) {
			$dateline.='<p id="bte_opp"><small>';
			if ($showPub) {
				$dateline.='Originally posted '.$origPubDate.'. ';
			}
			if ($givecredit) {
					$dateline.='Republished by  <a href="http://www.blogtrafficexchange.com/old-post-promoter">Blog Post Promoter</a>';
			}
			$dateline.='</small></p>';
		}
	}
	$atTop = get_option('bte_opp_at_top');
	if (isset($atTop) && $atTop) {
		$content = $dateline.$content;
	} else {
		$content = $content.$dateline;
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