=== Playlist 217 ===
Contributors: kionae
Tags: playlist, music, radio, now playing
Requires at least: 3.1
Tested up to: 3.1.2
Stable tag: trunk

Playlist 217 allows a radio station to maintain a live "now playing" list of tracks played on their station.

== Description ==

Playlist 217 allows a radio station to maintain a live "now playing" list of tracks played on their station.  It accepts an artist name and track title via a URL, stores it, and when possible matches it to the appropriate album art for display.  Playlists can be display as "most recent" or from a specified date/time range.

== Installation ==

1. Upload `playlist-217.zip` to the `/wp-content/plugins/` directory and unzip.
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Under the new Albums menu, click on Playlist217 Settings, and add your AudioScrobbler API keys.  
4. Send track information to the plugin via the ingest.php script
	example: http://www.example.com/wp-content/plugins/playlist-217/ingest.php?&artist=Weird%20Al%20Yankovic&track=Jurassic%20Park 
5. See the output.php file in the zip archive for examples of template functions.

== Frequently Asked Questions ==

= What's the point of this plugin? =

It was originally written to take the place of similar code written for a custom CMS when the217.com was migrated to Wordpress.  At the time, the217.com was the online home of WPGU 107.1 FM, a commercial radio station that streamed live on the internet.  They needed a real-time now playing playlist on the site, and the217.com's CMS accommodated that need by working in conjunction with a Cocoa application that fed it GET requests containing the current artist and track (the Cocoa application, in turn, was fed by the Scott Studios software that ran the station).  When the decision was made to migrate, the site code responsible for this feature was reworked into a Wordpress plugin.

= I don't like the default album artwork for unmatched songs.  Can I change it? =

Sure.  Just replace the no-album-art.gif file in the playlist217 director with your own image file, keeping the same filename.

== Changelog ==

= 2.0.2 =
* Added an optional setting to restrict the ability to insert a new playlist entry to a single IP address.
* Fixed a formatting issue in the playlist_most_played() theme function.

= 2.0.1 =
* Removal of some debug code

= 2.0 =
* Upgraded the plugin to use the AudioScrobbler 2.0 service instead of the old 1.0 service.
* Added the ability to optionally import or update an artist's discography manually instead of relying on the ingest script to do it.
* Fiddled around with the menus in the backend so they're all grouped together.

= 1.5 =
* Fixed bug wherein songs that didn't match an artist were sometimes being rejected

= 1.0 =
* Initial release

== Upgrade Notice ==

= 2.0.2 =
* Security update.  Added an optional setting to restrict the ability to insert a new playlist entry to a single IP address.

= 2.0.1 =
* Debug code that could cause some issues was removed.

= 2.0 =
* This version gives site admins more backend options and makes managing your playlist easier.

= 1.5 =
* This version fixes a very annoying bug.
