<?php
/**
 * @package Playlist_217
 * @version 2.0.1
 */
/*
Plugin Name: Playlist the217.com
Plugin URI: http://nlb-creations.com/2011/05/13/wordpress-plugin-playlist217/
Description: This plugin allows a web radio stream to insert entries into a playlist, via URL, in order to display a live "now playing" section on their website.
Author: Nikki Blight <nblight@nlb-creations.com>
Version: 2.0.2
Author URI: http://www.nlb-creations.com
*/

include_once dirname( __FILE__ ) . '/audioscrobbler.php';

register_activation_hook(__FILE__,'playlist_install');

add_action( 'init', 'playlist_create_post_types' );
add_action('init', 'playlist_create_taxonomy');
add_action('admin_menu', 'playlist_create_menu');
add_filter( 'plugin_action_links', 'playlist_action_links',10,2);

//create the playlist table required for storing the playlist
function playlist_install() {
   global $wpdb;

   $table_name = $wpdb->prefix . "playlist";
   if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
      
      $sql = "CREATE TABLE " . $table_name . " (
	  id int(11) NOT NULL AUTO_INCREMENT,
	  time int(11) DEFAULT '0' NOT NULL,
	  artist VARCHAR(255) NOT NULL,
	  track VARCHAR(255) NOT NULL,
	  artist_id int(11) NULL,
	  album_id int(11) NULL,
	  UNIQUE KEY id (id)
	);";

      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);
   }
}

//create a custom post type to hold album data
function playlist_create_post_types() {
	register_post_type( 'artistalbum',
		array(
			'labels' => array(
				'name' => __( 'Albums' ),
				'singular_name' => __( 'Album' ),
				'add_new' => __( 'Add Album'),
			),
			'public' => true,
			'taxonomies' => array('artists'),
			'supports' => array('title', 'thumbnail', 'custom-fields')
		)
	);


}

//create a taxonomy for the post type, which will store a list of artists
function playlist_create_taxonomy() {
	register_taxonomy('artists', 'artistalbum',
		array(
			'hierarchical' => true, 
			'label' => 'Artists', 
			'singular_label' => 'Artist',
			'public' => true,
			'show_tagcloud' => false,
			'query_var' => true,
			'rewrite' => array(
							'slug' => 'artist'
						)
		)
	);
}

//display a list of the most recently played tracks
function playlist_now_playing($limit = 10, $date_format = "m-d-Y g:i a") {
	global $wpdb;
	
	//fetch the requested number of playlist songs, and their album info
	$entries = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."playlist AS `songs` 
										ORDER BY `songs`.`time` DESC  
										LIMIT ".$limit);
	//generate the output
	$output = '<div class="playlist">';
	foreach($entries as $entry) {
		//fetch the album name and media file if they're available
		$album_name =  '';
		$coverart = '';
		if($entry->album_id != '' && $entry->album_id != 0) {
			$album = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."posts AS `posts` WHERE `posts`.`id` = ".$entry->album_id);
			if($album) {
				$album_name = $album->post_title;
			}
			
			$media = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."posts AS `posts` WHERE `posts`.`post_parent` = ".$entry->album_id." AND `posts`.`post_type` = 'attachment'");
			if($media) {
				$coverart = $media->guid;
			}
		}
		
		$output .= '<div class="playlist-entry">';
		
		$output .= '<div class="playlist-coverart">';
		if($coverart != '') {
			$output .= '<img src="'.$coverart.'" alt="'.str_replace('"', '&quot;', $album_name).'" class="playlist-album-cover" />';
		}
		else {
			$output .= '<img src="/wp-content/plugins/playlist217/no-album-art.gif" alt="'.str_replace('"', '&quot;', $entry->track).'" class="playlist-album-cover" />';			
		}
		$output .= '</div>';
		
		$output .= '<span class="playlist-time">'.playlist_time_fixed($entry->time,'mysql',$date_format).'</span> ';
		$output .= '<span class="playlist-track">'.$entry->track.'</span> ';
		$output .= '<span class="playlist-artist">'.$entry->artist.'</span> ';
		$output .= '<span class="playlist-album">'.$album_name.'</span>';
		$output .= '</div>';
	}
	$output .= '<div>';
	
	return $output;
}

//display a list of the most frequently play tracks and number of times played since a given date
function playlist_most_played($limit = 10, $start_date = "2011-01-01 00:00:00", $end_date = null, $include_date = false) {
	global $wpdb;
	
	//fetch the requested number of playlist songs, and their album info
	$query = "SELECT `artist`, `track`, `artist_id`, `album_id`, COUNT(*) AS `count` 
									FROM ".$wpdb->prefix."playlist 
									WHERE `time` >= UNIX_TIMESTAMP('".$start_date."') ";
	if($end_date) {
		$query .= "AND `time` <= UNIX_TIMESTAMP('".$end_date."') ";
	}
	$query .= "GROUP BY `track` 
				ORDER BY `count` DESC
				LIMIT ".$limit;
	
	$entries = $wpdb->get_results($query);
	
	//generate the output
	$output = '<div class="playlist">';
	if($include_date) {
		if($end_date == null) {
			$output .= '<h3>Since '.date('F d, Y', strtotime($start_date)).'</h3>';
		}
		else {
			$output .= '<h3>Between '.date('F d, Y g:i a', strtotime($start_date)).' and '.date('F d, Y g:i a', strtotime($end_date)).'</h3>';
		}
	}
	foreach($entries as $entry) {
		//fetch the album name and media file if they're available
		$album_name =  '';
		$coverart = '';
		if($entry->album_id != '' && $entry->album_id != 0) {
			$album = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."posts AS `posts` WHERE `posts`.`id` = ".$entry->album_id);
			if($album) {
				$album_name = $album->post_title;
			}
			
			$media = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."posts AS `posts` WHERE `posts`.`post_parent` = ".$entry->album_id." AND `posts`.`post_type` = 'attachment'");
			if($media) {
				$coverart = $media->guid;
			}
		}
		$output .= '<div class="playlist-entry">';
		$output .= '<div class="playlist-coverart">';
		if($coverart != '') {
			$output .= '<img src="'.$coverart.'" alt="'.str_replace('"', '&quot;', $album_name).'" class="playlist-album-cover" />';
		}
		else {
			$output .= '<img src="/wp-content/plugins/playlist217/no-album-art.gif" alt="'.str_replace('"', '&quot;', $entry->track).'" class="playlist-album-cover" />';			
		}
		$output .= '</div>';
		
		$output .= '<span class="playlist-count">Played '.$entry->count.' times</span> ';
		$output .= '<span class="playlist-track">'.$entry->track.'</span> ';
		$output .= '<span class="playlist-artist">'.$entry->artist.'</span> ';
		$output .= '<span class="playlist-album">'.$album_name.'</span>';
		$output .= '</div>';
	}
	$output .= '</div>';
	
	return $output;
}

/* Because Wordpress doesn't handle dates in a way that makes any sense whatsoever, 
 * and returns everything in UTC no matter what you do, we have to work around this.
 * This function is a modification of current_time_fixed() from the Wordpress Codex:
 * http://codex.wordpress.org/Function_Reference/current_time
 */
function playlist_time_fixed( $time, $type, $date_format = "Y-m-d H:m:i", $gmt = 0 ) {
	$t =  ( $gmt ) ? gmdate( $date_format ) : gmdate( $date_format, ( $time + ( get_option( 'gmt_offset' ) * 3600 ) ) );
	switch ( $type ) {
		case 'mysql':
			return $t;
			break;
		case 'timestamp':
			return strtotime($t);
			break;
	}
}

//insert a playlist entry into the databasee
function playlist_insert($artist, $track) {
	global $wpdb;

	//check that the insertion request is comming from the allowed IP address (if it has been specified)
	$securityCheck = get_option('playlist_allowed_ip');
	if($securityCheck != '') {
		if($_SERVER['REMOTE_ADDR'] != $securityCheck) {
			return false;
		}
	}
	
	$artist_id = null;
	$album_id = null;
	//$now = current_time("timestamp");
	$now = time(); //time() always returns server time... mktime() gets overwritten
	//$now = strtotime(gmdate( "Y-m-d H:m:i" )); //mktime() gets overridden by Wordpress core
	
	//first, look to see if we have the track in the database
	
	//fix word ordering problems with "the"
	if(substr($artist, -5) == ", The") {
		$artist = "The ".str_replace(", The", "", $artist);
	}
	
	//if the artist comes in with the format "lastname, firstname", fix that.
	if(strstr($artist, ",")) {
		$snip = explode(",", $artist);
		$fixName = implode(" ", array_reverse($snip));
		
		$snipAgain = explode(" ", $fixName);
		if(empty($snipAgain[0])) {
			unset($snipAgain[0]);
		}
		$artist = implode(" ", $snipAgain);
	}
	
	//the artists and tracks sometimes come in with quotes and apostrophes escaped... not good for matching
	$find = array("\\\"", "\\'");
	$replace = array("\"", "'");
	$track = str_replace($find, $replace, $track);
	$artist = str_replace($find, $replace, $artist);
	
	//find the artist's taxonomy id
	$slug = sanitize_title_with_dashes($artist);
	$taxCheck = playlist_check_for_artist($artist);
	
	if($taxCheck) {
		//get a list of albums for that taxonomy
		$albumCheck = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."posts AS `posts` 
											JOIN ".$wpdb->prefix."term_relationships AS `tax` 
											ON `posts`.`ID` = `tax`.`object_id` 
											WHERE `tax`.`term_taxonomy_id` = '".$taxCheck->term_taxonomy_id."'");
		
		//get the track list for each album
		$albums = array();
		if(!empty($albumCheck)) {
			foreach($albumCheck as $album) {
				$trackCheck = $wpdb->get_results("SELECT `meta`.`meta_value` FROM ".$wpdb->prefix."posts AS `posts` 
											JOIN ".$wpdb->prefix."postmeta AS `meta` 
											ON `posts`.`ID` = `meta`.`post_id` 
											WHERE `meta`.`meta_key` = 'track' 
												AND `posts`.`ID` = ".$album->ID);
				
				$tracks = array();
				foreach($trackCheck as $addTrack) {
					$tracks[] = strval($addTrack->meta_value);
				}
				
				$albums[] = array('title' => $album->post_title, 'album_slug' => $album->post_name, 'album_id' => $album->ID, 'artist_id' => $taxCheck->term_taxonomy_id, 'tracks' => $tracks);
			}
		}
		
		//see if we can find the track we're looking for in the results
		foreach($albums as $album) {
			
			foreach($album['tracks'] as $checkTrack) {
				if($album_id != null) {
					continue; //if we've already found it, we can move on
				}
				
				$formatedTrack = strtolower($track);
				$formatedCheckTrack = str_replace($find, $replace, strtolower($checkTrack));
				
				if(stristr($formatedCheckTrack, $formatedTrack)) {
					$artist_id = $album['artist_id'];
					$album_id = $album['album_id'];
				}
			}
		}
	}
	else {
		//we'll need to create the artist record
		$newArtist = playlist_fetch_new_artist_data($artist);
		
		//try to match the entry to the new artist data
		if(isset($newArtist['albums'])) {
			foreach($newArtist['albums'] as $album) {
				foreach($album['tracks'] as $checkTrack) {
					if($album_id != null) {
						continue; //if we've already found it, we can move on
					}
					
					$checkTrack = str_replace($find, $replace, strtolower($checkTrack));
					
					if(stristr($checkTrack, strtolower($track))) {
						$artist_id = $newArtist['artist_id'];
						$album_id = $album['album_id'];
					}
				}
			}
		}
	}

	//insert the playlist entry
	$rows_affected = $wpdb->insert( $wpdb->prefix.'playlist', array('time' => $now, 'artist' => $artist, 'track' => $track, 'artist_id' => $artist_id, 'album_id' => $album_id) );
	
	return true;
}

//insert a new Artist record into the database
function playlist_fetch_new_artist_data($artist) {
	global $wpdb;
	$siteurl = get_option('siteurl');
	
	$newArtist = array();

	$info = new AudioscrobblerComponent();
	$artistData = $info->topAlbumsForArtist($artist);
	
	//if the artist has no albums we're pretty much done here
	if(empty($artistData->topalbums->album)) {
		return false;
	}
	
	$newArtist['name'] = $artist;
	$newArtist['albums'] = array();
	
	//if we found albums, let's process them into something useful
	$albums = array();
	$found = array();
	
	foreach($artistData->topalbums->album as $album) {
		
		$albumData = $info->artistAlbum($artist, strval($album->name));

		// If LastFM doesn't know anything about this album skip over it
		if (!$albumData) {
			continue;
		}	
		
		// If there aren't any tracks for this album, or it's a single it's useless
		if (count($albumData->album->tracks->track) <= 1) {
			continue;
		}
		
		//LastFM tends to have duplicates... let's weed those out and save ourselves some time.
		if(in_array(strtolower(strval($album->name)), $found)) {
			continue;
		}
		else {
			$found[] = strtolower(strval($album->name));
		}
		
		$tracks = array();
		foreach($albumData->album->tracks->track as $track) {
			$tracks[] = strval($track->name);
		}
		
		$newArtist['albums'][] = array(
									'name' => strval($album->name), 
									'coverart' => strval($albumData->album->image[3]), 
									'tracks' => $tracks
									);
	}
	
	//insert into the database
	foreach($newArtist['albums'] as $i => $album) {
		//check that the album doesn't already exist
		$post_slug = sanitize_title_with_dashes($newArtist['name']).'-'.sanitize_title_with_dashes($album['name']);
		$postCheck = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."posts AS `posts` WHERE `posts`.`post_name` = '".$post_slug."'");
		
		if($postCheck) {
			continue; //if the album is already there, we can jump to the next one on the list
		}
		
		//insert a new post for the album
		$albumdata = array();
		$albumdata = array(
						'post_author' => 1, 
						'post_date' => current_time('mysql'),
						'post_date_gmt' => current_time('mysql'),
						'post_title' => $album['name'], 
						'post_status' => 'publish',
						'comment_status' => 'closed',
						'ping_status' => 'closed',
						'post_type' => 'artistalbum',
						'post_name' => $post_slug,
						'post_modified' => current_time('mysql'),
						'post_modified_gmt' => current_time('mysql')
					);
		
		$rows_affected = $wpdb->insert( $wpdb->prefix.'posts', $albumdata );

		//get the id of the new post
		$albumID = mysql_insert_id();
		
		//create the artist taxonomy if it doesn't exist and add it to the post
		$slug = sanitize_title_with_dashes($newArtist['name']);
		$taxCheck = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."terms AS `terms` JOIN ".$wpdb->prefix."term_taxonomy AS `tax` ON `terms`.`term_id` = `tax`.`term_id` WHERE `terms`.`slug` = '".$slug."' AND `tax`.`taxonomy` = 'artists'");
		if(!$taxCheck) {	
			$rows_affected = $wpdb->insert( $wpdb->prefix.'terms', array('name' => $newArtist['name'], 'slug' => $slug, 'term_group' => 0) );
			$termID = mysql_insert_id();
			$rows_affected = $wpdb->insert( $wpdb->prefix.'term_taxonomy', array('term_id' => $termID, 'taxonomy' => 'artists', 'description' => '', 'parent' => 0, 'count' => 0) );
			$termTaxID = mysql_insert_id();
			$rows_affected = $wpdb->insert( $wpdb->prefix.'term_relationships', array('object_id' => $albumID, 'term_taxonomy_id' => $termTaxID, 'term_order' => 0) );
			$newArtist['artist_id'] = $termTaxID;
		}
		else {
			//if the artist already exists, just grab the appropriate IDs
			$termTaxCheck = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."term_taxonomy AS `termtax` WHERE `termtax`.`term_id` = '".$taxCheck->term_id."'");
			$rows_affected = $wpdb->insert( $wpdb->prefix.'term_relationships', array('object_id' => $albumID, 'term_taxonomy_id' => $termTaxCheck->term_taxonomy_id, 'term_order' => 0) );
			$newArtist['artist_id'] = $termTaxCheck->term_taxonomy_id;
		}
		
		//add the coverart
		if($album['coverart'] != '') {
			$artDir = 'wp-content/uploads/artistalbum/';
			
			if(!file_exists(ABSPATH.$artDir)) {
				mkdir(ABSPATH.$artDir);
			}
			
			$ext = array_pop(explode(".", $album['coverart']));
			$new_filename = 'albumart'.$albumID.".".$ext;
			
			copy($album['coverart'], ABSPATH.$artDir.$new_filename);
			
			$file_info = getimagesize(ABSPATH.$artDir.$new_filename);
			$artdata = array();
			$artdata = array(
						'post_author' => 1, 
						'post_date' => current_time('mysql'),
						'post_date_gmt' => current_time('mysql'),
						'post_title' => $album['name'].' cover', 
						'post_status' => 'inherit',
						'comment_status' => 'closed',
						'ping_status' => 'closed',
						'post_type' => 'artistalbum',
						'post_name' => sanitize_title_with_dashes($newArtist['name']).'-'.sanitize_title_with_dashes($album['name']).'-cover',
						'post_modified' => current_time('mysql'),
						'post_modified_gmt' => current_time('mysql'),
						'post_parent' => $albumID,
						'post_type' => 'attachment',
						'guid' => $siteurl.'/'.$artDir.$new_filename,
						'post_mime_type' => $file_info['mime']
					);
		
			$rows_affected = $wpdb->insert( $wpdb->prefix.'posts', $artdata );
			$artID = mysql_insert_id();
			$rows_affected = $wpdb->insert( $wpdb->prefix.'postmeta', array('post_id' => $albumID, 'meta_key' => '_thumbnail_id', 'meta_value' => $artID) );
			$rows_affected = $wpdb->insert( $wpdb->prefix.'postmeta', array('post_id' => $artID, 'meta_key' => '_wp_attached_file', 'meta_value' => 'artistalbum/'.$new_filename) );
		}
		
		//insert the track listing into post_meta
		foreach($album['tracks'] as $track) {
			$rows_affected = $wpdb->insert( $wpdb->prefix.'postmeta', array('post_id' => $albumID, 'meta_key' => 'track', 'meta_value' => $track) );
		}
		
		$newArtist['albums'][$i]['album_id'] = $albumID;
	}
	
	return $newArtist;
}

function playlist_update_artist_data($artist, $artist_id) {
	global $wpdb;
	
	$siteurl = get_option('siteurl');
	
	$newArtist = array();

	$info = new AudioscrobblerComponent();
	$artistData = $info->topAlbumsForArtist($artist);
	
	//if the artist has no albums we're pretty much done here
	if(empty($artistData->topalbums->album)) {
		return false;
	}
	
	$newArtist['name'] = $artist;
	$newArtist['albums'] = array();
	
	//if we found albums, let's process them into something useful
	$albums = array();
	$found = array();
	
	foreach($artistData->topalbums->album as $album) {
		
		$albumData = $info->artistAlbum($artist, strval($album->name));

		// If LastFM doesn't know anything about this album skip over it
		if (!$albumData) {
			continue;
		}	
		
		// If there aren't any tracks for this album, or it's a single it's useless
		if (count($albumData->album->tracks->track) <= 1) {
			continue;
		}
		
		//LastFM tends to have duplicates... let's weed those out and save ourselves some time.
		if(in_array(strtolower(strval($album->name)), $found)) {
			continue;
		}
		else {
			$found[] = strtolower(strval($album->name));
		}
		
		$tracks = array();
		foreach($albumData->album->tracks->track as $track) {
			$tracks[] = strval($track->name);
		}
		
		$newArtist['albums'][] = array(
									'name' => strval($album->name), 
									'coverart' => strval($albumData->album->image[3]), 
									'tracks' => $tracks
									);
	}
	
	$saved = array();
	//insert into the database
	foreach($newArtist['albums'] as $i => $album) {
		//check that the album doesn't already exist
		$post_slug = sanitize_title_with_dashes($newArtist['name']).'-'.sanitize_title_with_dashes($album['name']);
		$postCheck = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."posts AS `posts` WHERE `posts`.`post_name` = '".$post_slug."'");
		
		if($postCheck) {
			continue; //if the album is already there, we can jump to the next one on the list
		}
		
		//insert a new post for the album
		$albumdata = array();
		$albumdata = array(
						'post_author' => 1, 
						'post_date' => current_time('mysql'),
						'post_date_gmt' => current_time('mysql'),
						'post_title' => $album['name'], 
						'post_status' => 'publish',
						'comment_status' => 'closed',
						'ping_status' => 'closed',
						'post_type' => 'artistalbum',
						'post_name' => $post_slug,
						'post_modified' => current_time('mysql'),
						'post_modified_gmt' => current_time('mysql')
					);
		
		$rows_affected = $wpdb->insert( $wpdb->prefix.'posts', $albumdata );

		//get the id of the new post
		$albumID = mysql_insert_id();
		
		//add the artist			
		$rows_affected = $wpdb->insert( $wpdb->prefix.'term_relationships', array('object_id' => $albumID, 'term_taxonomy_id' => $artist_id, 'term_order' => 0) );
		$newArtist['artist_id'] = $artist_id;
		
		//add the coverart
		if($album['coverart'] != '') {
			$artDir = 'wp-content/uploads/artistalbum/';
			
			if(!file_exists(ABSPATH.$artDir)) {
				mkdir(ABSPATH.$artDir);
			}
			
			$ext = array_pop(explode(".", $album['coverart']));
			$new_filename = 'albumart'.$albumID.".".$ext;
			
			copy($album['coverart'], ABSPATH.$artDir.$new_filename);
			
			$file_info = getimagesize(ABSPATH.$artDir.$new_filename);
			$artdata = array();
			$artdata = array(
						'post_author' => 1, 
						'post_date' => current_time('mysql'),
						'post_date_gmt' => current_time('mysql'),
						'post_title' => $album['name'].' cover', 
						'post_status' => 'inherit',
						'comment_status' => 'closed',
						'ping_status' => 'closed',
						'post_type' => 'artistalbum',
						'post_name' => sanitize_title_with_dashes($newArtist['name']).'-'.sanitize_title_with_dashes($album['name']).'-cover',
						'post_modified' => current_time('mysql'),
						'post_modified_gmt' => current_time('mysql'),
						'post_parent' => $albumID,
						'post_type' => 'attachment',
						'guid' => $siteurl.'/'.$artDir.$new_filename,
						'post_mime_type' => $file_info['mime']
					);
		
			$rows_affected = $wpdb->insert( $wpdb->prefix.'posts', $artdata );
			$artID = mysql_insert_id();
			$rows_affected = $wpdb->insert( $wpdb->prefix.'postmeta', array('post_id' => $albumID, 'meta_key' => '_thumbnail_id', 'meta_value' => $artID) );
			$rows_affected = $wpdb->insert( $wpdb->prefix.'postmeta', array('post_id' => $artID, 'meta_key' => '_wp_attached_file', 'meta_value' => 'artistalbum/'.$new_filename) );
		}
		
		//insert the track listing into post_meta
		foreach($album['tracks'] as $track) {
			$rows_affected = $wpdb->insert( $wpdb->prefix.'postmeta', array('post_id' => $albumID, 'meta_key' => 'track', 'meta_value' => $track) );
		}
		
		$saved[] = $album['name'];
	}
	
	return $saved;
}

function playlist_check_for_artist($artist) {
	global $wpdb;
	
	//find the artist's taxonomy id
	$taxCheck = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."terms AS `terms` 
									JOIN ".$wpdb->prefix."term_taxonomy AS `tax` 
									ON `terms`.`term_id` = `tax`.`term_id` 
									WHERE `terms`.`name` = '".$artist."' 
										AND `tax`.`taxonomy` = 'artists' 
										LIMIT 1");
	
	//if an exact match doesn't work, let's give things a little more leeway
	if(!$taxCheck) {
		$taxCheck = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."terms AS `terms` 
									JOIN ".$wpdb->prefix."term_taxonomy AS `tax` 
									ON `terms`.`term_id` = `tax`.`term_id` 
									WHERE `terms`.`name` LIKE '%".$artist."%' 
										AND `tax`.`taxonomy` = 'artists' 
										LIMIT 1");
		if($taxCheck) {
			return $taxCheck;
		}
		else {
			return false;
		}
	}
	else {
		return $taxCheck;
	}
	
}

//admin function to automatically import or update data from Last.FM
function playlist_options_page() {
	
	// Process the form submission
	if (isset($_POST['wpms_action']) && $_POST['wpms_action'] == __('Submit', 'playlist') && isset($_POST['artist'])) {
		$artist = $_POST['artist'];

		$taxCheck = playlist_check_for_artist($artist);
		
		if(!$taxCheck) {
			$result = playlist_fetch_new_artist_data($artist);
		}
		else {
			$result = playlist_update_artist_data($artist, $taxCheck->term_taxonomy_id);
			//$result = $taxCheck;
		}
		// Output the response
		?>
		<div><p><strong><?php _e('Processing Artist...', 'playlist'); ?></strong></p></div>
		<div><p><strong><?php _e('Artist Processed', 'playlist'); ?></strong></p>
		<p><?php _e('The result was:', 'playlist'); ?></p>
		<pre><?php var_dump($result); ?></pre>
		</div>
		<?php

	}
	
	?>
	<div class="wrap">
	<h2><?php _e('Update Artist Information', 'playlist'); ?></h2>
	<form method="POST">
	<fieldset class="options">
	<legend><?php _e('Attempt to Add/Update an artist from Last.FM', 'playlist'); ?></legend>
	<table class="optiontable">
	<tr valign="top">
	<th scope="row"><?php _e('Artist Name', 'playlist'); ?> </th>
	<td><p><input name="artist" type="text" id="artist" value="" size="40" class="code" /><br />
	<?php _e('Enter the name of the artist you wish to add or update.  This process may take a few minutes to complete.', 'playlist'); ?></p></td>
	</tr>
	</table>
	<script type="text/javascript">		
				function toggle( targetId ){
					if (document.getElementById){
						target = document.getElementById( targetId );
						if (target.style.display == "none"){
							target.style.display = "block";
							document.getElementById('submit').style.display = "none";
						}
					}
				}
			</script>
			<p class="submit"><input type="submit" class="button-primary" name="wpms_action" id="submit" value="<?php _e('Submit', 'playlist'); ?>" onClick="toggle('message')" /></p>
			<div id="message" style="display: none;">Processing...</div>

	</fieldset>
	</form>
	
	</div>
	<?php
}

//settings pages, because we need to store an Audioscrobbler API key
function playlist_settings_page() {
?>
<div class="wrap">
<h2>Playlist217 Settings</h2>

<form method="post" action="options.php">
    <?php settings_fields( 'playlist-settings-group' ); ?>
    <?php do_settings_sections( 'playlist-settings-group' ); ?>
    <h3>AudioScrobbler</h3>
	<p>In order to retrieve album data from Last.FM, you must register for an <a href="http://www.last.fm/api/account">Audioscrobbler API key</a>.</p>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">Audioscrobbler API Key</th>
        <td><input type="text" name="playlist_api_key" value="<?php echo get_option('playlist_api_key'); ?>" /></td>
        </tr>
         
        <tr valign="top">
        <th scope="row">Audioscrobbler Secret Key</th>
        <td><input type="text" name="playlist_secret_key" value="<?php echo get_option('playlist_secret_key'); ?>" /></td>
        </tr>
    </table>
    
    <h3>Security</h3>
    <p>Allow playlist submissions only from this IP address:</p>
    <input type="text" name="playlist_allowed_ip" value="<?php echo get_option('playlist_allowed_ip'); ?>" />
    <br />
    *use this if you are concerned about people outside your site submitting songs to your playlist
    
    <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>

</form>
</div>
<?php
}

//create the menus for the two admin pages
function playlist_create_menu() {

	//add_menu_page('Playlist217 Settings', 'Playlist217 Settings', 'administrator', __FILE__, 'playlist_settings_page',plugins_url('/images/icon.png', __FILE__));
	add_submenu_page('edit.php?post_type=artistalbum', 'Playlist217: Auto-Import', 'Auto-Import Albums', 'administrator', 'playlist_options/'.basename(__file__), 'playlist_options_page');
	add_submenu_page('edit.php?post_type=artistalbum', 'Playlist217: Settings', 'Playlist217 Settings', 'administrator', 'playlist_settings/'.basename(__file__), 'playlist_settings_page');

	//call register settings function
	add_action( 'admin_init', 'register_mysettings' );
}

//register our custom settings
function register_mysettings() {
	register_setting( 'playlist-settings-group', 'playlist_api_key' );
	register_setting( 'playlist-settings-group', 'playlist_secret_key' );
	register_setting( 'playlist-settings-group', 'playlist_allowed_ip' );
}

function playlist_action_links( $links, $file ) {
	if ( $file != plugin_basename( __FILE__ ))
		return $links;

	$settings_link = '<a href="edit.php?post_type=artistalbum&page=playlist_settings/playlist.php">' . __( 'Settings', 'playlist217' ) . '</a>';

	array_unshift( $links, $settings_link );

	return $links;
}

?>