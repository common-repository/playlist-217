<?php
/**
AudioScrobbler Component 
Description: Class for interacting with the REST services on last.fm
			 This class provides a programatic interface for interacting
			 with the rest services on last.fm.  Adapted from the217.com's 
			 original source code, based on code by Troy Stanger.
Author: Nikki Blight <nblight@nlb-creations.com>
 */
class AudioscrobblerComponent {
	
	var $url_base = "http://ws.audioscrobbler.com/2.0/";
	
	/*
	 * Artist Data 
	 */
	function topTracksForArtist($artist) {
		$url_form = $this->url_base.'?method=artist.gettoptracks&artist='.rawurlencode($artist).'&api_key='.get_option('playlist_api_key');
		return $this->processRequest($url_form);
	}
	
	function topAlbumsForArtist($artist) {
		$url_form = $this->url_base.'?method=artist.gettopalbums&artist='.rawurlencode($artist).'&api_key='.get_option('playlist_api_key');
		return $this->processRequest($url_form);

	}
	
	function topTagsForArtist($artist) {
		$url_form = $this->url_base.'?method=artist.gettoptags&artist='.rawurlencode($artist).'&api_key='.get_option('playlist_api_key');
		return $this->processRequest($url_form);

	}
	
	function getInfoForArtist($artist) {
		$url_form = $this->url_base.'?method=artist.getinfo&artist='.rawurlencode($artist).'&api_key='.get_option('playlist_api_key');
		return $this->processRequest($url_form);
	} 
	
	/*
	 * Album Data
	 */
	function artistAlbum($artist,$album) {
		$url_form = $this->url_base.'?method=album.getinfo&artist='.rawurlencode($artist).'&album='.rawurlencode($album).'&api_key='.get_option('playlist_api_key');
		return $this->processRequest($url_form);
	}
	
	/**
	 * Helper function to load the url of the requested lastfm information
	 * 
	 * This function takes the url of a valid lastfm REST request and returns 
	 * a simplexml object from the incoming xml.  This function will also optional
	 * cache the request.
	 * 
	 */
	private function processRequest($url, $use_cache = true) {	
		// Big Hack to make sure we aren't making too many requests in a row
		// Last.fm only allows 1 request per second
		sleep(2);
			
		//fix requests for non-existant files
		$headers = get_headers($url);
		if($headers[0] != "HTTP/1.0 404 Not Found") {
			$data = file_get_contents($url);
		}
		else {
			$data = null;	
		}

		$xml = simplexml_load_string($data);

		return $xml;
	}
	
}

?>