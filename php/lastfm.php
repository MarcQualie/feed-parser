<?php

/*
 *	
 *		Basic LastFM Data Dump
 *		This file will download your last.fm public data
 *
 */

header ('Content-type: text/plain');
include ('class.feedparser.php');
$feed = new FeedParser();

$limit		= 1;
$user		= 'marcqualie';
$stream		= 'user.getrecenttracks';
$data		= $feed->lastfm($user, $stream, $limit);

$fh = fopen ('lastfm.log', 'w');
  fwrite($fh, print_r($data, 1));
  fclose($fh);

echo "Downloaded " . count($data['posts']) . " posts\n";
echo "Bandwidth Usage: {$feed->bandwidth}\n";
echo "Requests: {$feed->requests}\n";
echo "\n";
if ($_SERVER['HTTP_USER_AGENT']) print_r($data);