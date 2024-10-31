<?php
/**
Playlist Ingest
Description: Accepts artist name and track title via URL and feeds the 
			 information into playlist.php for processing.
			 Takes URL encoded parameters artist and track, example:
			 http://www.test.com/wp-content/plugins/playlist-217/ingest.php?&artist=Weird%20Al%20Yankovic&track=Jurassic%20Park 

Author: Nikki Blight <nblight@nlb-creations.com>
Version: 1.0
Author URI: http://www.nlb-creations.com
*/ 
//*load the WordPress Environment so we can use the plugin's code
require('../../../wp-load.php');

if(isset($_GET['artist']) && isset($_GET['track'])) {
	return playlist_insert($_GET['artist'], $_GET['track']);	
}
else {
	return false;
}
?>