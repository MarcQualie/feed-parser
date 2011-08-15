<?php

/*
 *
 *		Simple Example of how to use Feed Parser Class
 *		by Marc Qualie
 *
 */

include 'class.feedparser.php';
$feed = new FeedParser();

// Two different ways of getting blogger feeds, both end up in at the same url
$blog1 	= $feed->blogger('blog.marcqualie.com', 1);
$blog2	= $feed->blogger('marcqualie', 1);

// Twitter via Username
$tweets	= $feed->twitter('marcqualie', 1);

// Output
header ('Content-type: text/plain');
echo "Bandwidth: {$feed->bandwidth}\n";
echo "Requests: {$feed->requests}\n";
echo "\n";

// Dump
$output  = 'blog1  = ' . print_r($blog1, 1) . "\n";
$output .= 'blog2  = ' . print_r($blog2, 1) . "\n";
$output .= 'tweets = ' . print_r($tweets, 1) . "\n";
$fh = fopen ('example.log', 'w');
  fwrite($fh, $output);
  fclose($fh);