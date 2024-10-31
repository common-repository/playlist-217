<?php 
/* This file contains examples of how to use and style this plugin's output functions in your theme */

require('../../../wp-load.php');
?>

<style type="text/css">
	.playlist {
		width: 300px;
		margin: auto;
		font-size: 9px;
	}
	.playlist-entry {
		clear: both;
		border: 1px solid #333333;
		width: 300px;
		height: 50px;
		margin-bottom: 10px;
	}
	.playlist-time {
		display: block;
		background-color: #a2a2a2;
	}
	.playlist-track {
		display: block;
	}
	.playlist-artist {
		display: block;
		background-color: #a2a2a2;
	}
	.playlist-album {
		display: block;
	}
	.playlist-coverart {
		float: left;
		padding-right: 10px;
	}
	img {
		width: 50px;
	}
	td, th {
		vertical-align: top;
	}
</style>

<table>
	<tr>
		<th>Most Recent</th>
		<th>Last Ten</th>
		<th>Most Played</th>
	</tr>
	<tr>
		<td>
			<?php
				//parameters are number of entries and format of date
				echo playlist_now_playing(1, 'F d, Y - g:i a');
			?>
		</td>
		<td>
			<?php
				//parameters are number of entries and format of date
				echo playlist_now_playing(10, 'F d, Y - g:i a');
			?>
		</td>
		<td>
			<?php
				//parameters are number of entries, date to start, date to end (may be left null to use current time), and whether or not to display the date(s)
				echo playlist_most_played(10, '2011-04-01 00:00:00', '2011-4-01 23:59:59', true);
			?>
		</td>
	</tr>
</table>