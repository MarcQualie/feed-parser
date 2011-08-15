<?php

/*
 *
 *		Feed Parser Class
 *		by Marc Qualie
 *
 *		http://www.marcqualie.com/projects/php-feed-parser/
 *		https://github.com/MarcQualie/php-feed-parser
 *
 */

class FeedParser {
	
	private $cache;
	private $useragent			= 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/13.0.782.112 Safari/535.1';
	
	public $bandwidth			= 0;
	public $requests			= 0;
	
	// Init
	public function __construct () {
		if (!class_exists('Memcache')) return;
		$this->cache = new Memcache;
		if (!@$this->cache->connect('localhost', 11211)) $this->cache = false;
	}
	
	// Basic Internal Cache
	private function cache_get ($key) {
		if (!$this->cache) return false;
		return $this->cache->get($key);
	}
	private function cache_set ($key, $val) {
		if (!$this->cache) return false;
		return $this->cache->set($key, $val);
	}
	
	// Request Handler
	public function req ($url, $force = false) {
		$key = 'url-' . md5($url);
		$data = $this->cache_get($key);
		if (!$data || $force) {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_USERAGENT, $this->useragent);
			$html = curl_exec($ch);
			$info = curl_getinfo($ch);
			curl_close($ch);
			$data = array('info' => $info, 'html' => $html);
			$this->cache_set($key, $data);
			$this->requests++;
			$this->bandwidth += $info['size_download'];
		}
		return $data;
	}
	
	
	
	// Blog
	public function blogger ($user, $limit = 10) {
		$host = strpos($user, '.') !== false ? $user : "{$user}.blogspot.com";
		$url = "http://{$host}/feeds/posts/default?alt=json&max-results={$limit}";
		$r = $this->req($url);
		$j = json_decode($r['html'], true);
		if (!$j['feed']['id']) return array('id' => 0, 'url' => $url);
		$a = array();
		preg_match('/blog-([0-9]+)$/', $j['feed']['id']['$t'], $m);
		$a['id'] = $m[1];
		$a['url'] = $url;
		$a['title'] = $j['feed']['title']['$t'];
		$a['subtitle'] = $j['feed']['subtitle']['$t'];
		foreach ($j['feed']['entry'] as $e) {
			$f = array();
			preg_match('/post-([0-9]+)$/', $e['id']['$t'], $m);
			$f['id'] = $m[1];
			$f['title'] = $e['title']['$t'];
			$f['content'] = $e['content']['$t'];
			$f['published'] = strtotime($e['published']['$t']);
			$f['updated'] = strtotime($e['updated']['$t']);
			$f['author'] = array(
				'name'		=> $e['author'][0]['name']['$t'],
				'link'		=> $e['author'][0]['uri']['$t'],
				'email'		=> $e['author'][0]['email']['$t']
			);
			$f['labels'] = array();
			foreach ($e['category'] as $c) {
				$f['labels'][] = $c['term'];
			}
			foreach ($e['link'] as $l) {
				if ($l['title'] == $f['title']) $f['link'] = $l['href'];
			}
			$a['posts'][] = $f;
		}
		return $a;
	}
	
	// Wordpress
	public function wordpress ($url) {
		
	}
	
	// Twitter (Partial)
	public function twitter ($user, $limit = 10) {
		$url = "http://api.twitter.com/1/statuses/user_timeline.json?screen_name={$user}&count={$limit}";
		$r = $this->req($url);
		$j = json_decode($r['html'], true);
		return $j;
	}
	
	// LastFM
	public function lastfm ($user, $stream) {
		
	}
	
	
	
}