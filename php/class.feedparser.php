<?php

/*
 *
 *		Feed Parser Class
 *		by Marc Qualie
 *
 *		http://www.marcqualie.com/projects/feed-parser/
 *		https://github.com/MarcQualie/feed-parser
 *
 */

class FeedParser {
	
	public $version				= '0.1.1';
	private $useragent			= 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/13.0.782.112 Safari/535.1';
	
	public $bandwidth			= 0;
	public $requests			= 0;
	
	private $cache;
	private $configfile			= './config.conf';
	private $config				= array();
	
	
	
	// Init
	public function __construct ($cf = null) {
		if ($cf) $this->configfile = $cf;
		if (!class_exists('Memcache')) return;
		$this->cache = new Memcache;
		if (!@$this->cache->connect('localhost', 11211)) $this->cache = false;
		$this->parse_config_file();
	}
	private function parse_config_file () {
		if (!file_exists($this->configfile)) return;
		$file = file_get_contents($this->configfile);
		$data = explode("\n", str_replace("\r", '', $file));
		$ns = 'global';
		foreach ($data as $l) {
			if (strpos($l, '[') === 0) {
				preg_match ('/\[([a-z0-9]+)\]/', $l, $m);
				if ($m[1]) $ns = $m[1];
			} else {
				preg_match ('/([a-z0-9]+)[\s]+(.*)/', $l, $m);
				$key = $m[1];
				$val = trim($m[2]);
				if ($key) {
					$ns == 'global' ? ($this->config[$key] = $val) : ($this->config[$ns][$key] = $val);
				}
			}
		}
	}
	
	// Basic Internal Cache
	private function cache_get ($key) {
		return false;
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
	public function blogger ($user, $limit = 5) {
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
			$f['content'] = str_replace('&nbsp;', ' ', $f['content']);
			$f['content'] = preg_replace('/<div class="blogger-post-footer">(.*)<\/div>/U', '', $f['content']);
			list ($preview) = explode("<a name='more'></a>", $f['content']);
			while (substr($preview, -6) === '<br />') $preview = substr($preview, 0, strlen($preview) - 6);
			$f['preview'] = $preview;
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
	public function twitter ($user, $limit = 5) {
		$url = "http://api.twitter.com/1/statuses/user_timeline.json?screen_name={$user}&count={$limit}";
		$r = $this->req($url);
		$j = json_decode($r['html'], true);
		return $j;
	}
	
	// LastFM - http://ws.audioscrobbler.com/2.0/?format=json&method=user.getrecenttracks&user=rj&api_key=b25b959554ed76058ac220b7b2e0a026
	public function lastfm ($user, $stream = 'user.getrecenttracks', $limit = 5) {
		$streams = array('user.getrecenttracks', 'user.gettopartists');
		if (!in_array($stream, $streams)) return array('error' => 1, 'message' => 'Invalid method');
		$apikey = $this->config['lastfm']['apikey'];
		if (!$apikey) return array('error' => 1, 'message' => 'No API Key Specified');
		$url = "http://ws.audioscrobbler.com/2.0/?format=json&method={$stream}&user={$user}&limit={$limit}&api_key={$apikey}";
		$r = $this->req($url);
		$j = json_decode($r['html'], true);
		return $j;
	}
	
	
	
}
