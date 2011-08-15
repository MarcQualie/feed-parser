<?php

/*
 *	
 *		Basic Blogger Data Dump
 *		This file will download your blogger feed and save to log file
 *
 */

include ('class.feedparser.php');
$feed = new FeedParser();

$limit		= 1;
$user		= 'blog.marcqualie.com';
$data		= $feed->blogger($user, $limit);

$fh = fopen ('blogger.log', 'w');
  fwrite($fh, print_r($data, 1));
  fclose($fh);

echo "Downloaded " . count($data['posts']) . " posts\n";
echo "Bandwidth Usage: {$feed->bandwidth}\n";
echo "Requests: {$feed->requests}\n";
echo "\n";