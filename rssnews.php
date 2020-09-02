<?php
////////////////////////////////
// Load config file
////////////////////////////////
require_once( dirname(__FILE__).'/config/config.php');

////////////////////////////////
// Autoload libraries and also
// debug print
////////////////////////////////
require_once(dirname(__FILE__).'/utils/autoload.php'); // for debug call  debug($msg,$obj)
require_once(dirname(__FILE__).'/utils/utils.php'); // for debug call  debug($msg,$obj)
require ( dirname(__FILE__).'/libraries/simplepie/SimplePieAutoloader.php');
// always include Simplepie_Core as it defines constants which other SimplePie components
// assume will always be available.
require ( dirname(__FILE__).'/libraries/simplepie/SimplePie/Core.php');

///////////////////////////////////////////////
// Detect language
///////////////////////////////////////////////
if ($options->detect_language === 'user') {
	if (isset($_GET['l'])) {
		$detect_language = (int)$_GET['l'];
	} else {
		$detect_language = 1;
	}
} else {
	$detect_language = $options->detect_language;
}
///////////////////////////////////////////////////////
/// Debug will work only if 
//  $options->debug = true; in config.php is set 
//////////////////////////////////////////////////////
debug("",null,true);
if ($detect_language >= 2) {
	$language_codes = array('albanian' => 'sq','arabic' => 'ar','azeri' => 'az','bengali' => 'bn','bulgarian' => 'bg',
	'cebuano' => 'ceb', // ISO 639-2
	'croatian' => 'hr','czech' => 'cs','danish' => 'da','dutch' => 'nl','english' => 'en','estonian' => 'et','farsi' => 'fa','finnish' => 'fi','french' => 'fr','german' => 'de','hausa' => 'ha',
	'hawaiian' => 'haw', // ISO 639-2 
	'hindi' => 'hi','hungarian' => 'hu','icelandic' => 'is','indonesian' => 'id','italian' => 'it','kazakh' => 'kk','kyrgyz' => 'ky','latin' => 'la','latvian' => 'lv','lithuanian' => 'lt','macedonian' => 'mk','mongolian' => 'mn','nepali' => 'ne','norwegian' => 'no','pashto' => 'ps',
	'pidgin' => 'cpe', // ISO 639-2  
	'polish' => 'pl','portuguese' => 'pt','romanian' => 'ro','russian' => 'ru','serbian' => 'sr','slovak' => 'sk','slovene' => 'sl','somali' => 'so','spanish' => 'es','swahili' => 'sw','swedish' => 'sv','tagalog' => 'tl','turkish' => 'tr','ukrainian' => 'uk','urdu' => 'ur','uzbek' => 'uz','vietnamese' => 'vi','welsh' => 'cy');
}
$use_cld = extension_loaded('cld') && (version_compare(PHP_VERSION, '5.3.0') >= 0);


///////////////////////////////////////////////
// Check if valid key supplied
///////////////////////////////////////////////
$valid_key = false;
//if (isset($_GET['key']) && isset($_GET['hash']) && isset($options->api_keys[(int)$_GET['key']])) {
if (isset($fields['key']) && isset($fields['hash']) && isset($options->api_keys[(int)$fields['key']])) {
	//$valid_key = ($_GET['hash'] == sha1($options->api_keys[(int)$_GET['key']].$url));
    $valid_key = ($fields['hash'] == sha1($options->api_keys[(int)$fields['key']].$url));
}
//$key_index = ($valid_key) ? (int)$_GET['key'] : 0;
$key_index = ($valid_key) ? (int)$fields['key'] : 0;
if (!$valid_key && isset($options->key_required)) {
	die('A valid key must be supplied'); 
}
//if (!$valid_key && isset($_GET['key']) && $_GET['key'] != '') {
if (!$valid_key && isset($fields['key']) && $fields['key'] != '') {
	die('The entered key is invalid');
}

/////////////////////////////////////
// Should we do XSS filtering?
/////////////////////////////////////
if ($options->xss_filter === 'user') {
	//$xss_filter = isset($_GET['xss']);
    $xss_filter = isset($fields['xss']);
} else {
	$xss_filter = $options->xss_filter;
}
//if (!$xss_filter && isset($_GET['xss'])) {
if (!$xss_filter && isset($fields['xss'])) {
	die('XSS filtering is disabled in config');
}
/////////////////////////////////////
// Check for valid format
// (stick to RSS (or RSS as JSON) for the time being)
/////////////////////////////////////
//if (isset($_GET['format']) && $_GET['format'] == 'json') {
if (isset($fields['format']) && $fields['format'] == 'json') {
	$format = 'json';
} else {
	$format = 'rss';
}
/////////////////////////////////////
// Check for JSONP
// Regex from https://gist.github.com/1217080
/////////////////////////////////////
$callback = null;
if ($format =='json' && /*isset($_GET['callback'])*/isset($fields['callback'])) {
	//$callback = trim($_GET['callback']);
    $callback = trim($fields['callback']);
	foreach (explode('.', $callback) as $_identifier) {
		if (!preg_match('/^[a-zA-Z_$][0-9a-zA-Z_$]*(?:\[(?:".+"|\'.+\'|\d+)\])*?$/', $_identifier)) {
			die('Invalid JSONP callback');
		}
	}
	debug("JSONP callback: $callback");
}


////////////////////////////////
// Check for feed URL
////////////////////////////////

$url = ($fields['url']).rawurlencode($fields['keyword']).$fields['end'];
$url = filter_var($url, FILTER_SANITIZE_URL);
debug(">>>>>>>>>>>>>>>>>>>>>>> URL>>>>>>>>>>>>[".$url."]>>>\n");

if (phpversion() === '5.2.13' || phpversion() === '5.3.2')
 {
     $test = filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED);
    // deal with bug http://bugs.php.net/51192 (present in PHP 5.2.13 and PHP 5.3.2)
    if ($test === false) {
        $test = filter_var(strtr($url, '-', '_'), FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED);
    }
    if ($test !== false && $test !== null && preg_match('!^https?://!', $url)) {
        // all okay
        unset($test);
    } else {
        die('Invalid URL supplied');
    }    
 }
  
///////////////////////////////////////////////
// Max entries
// see config.php to find these values
///////////////////////////////////////////////

if (isset($numbers))
{
    $max  =(int)$numbers;
}else if (isset($_GET['max'])) {
	$max = (int)$_GET['max'];
	if ($valid_key) {
		$max = min($max, $options->max_entries_with_key);
	} else {
		$max = min($max, $options->max_entries);
	}
} else {
	if ($valid_key) {
		$max = $options->default_entries_with_key;
	} else {
		$max = $options->default_entries;
	}
}  
$debug_mode = false;

///////////////////////////////////////////////
// Check if the request is explicitly for an HTML page
///////////////////////////////////////////////
//$html_only = (isset($_GET['html']) && ($_GET['html'] == '1' || $_GET['html'] == 'true'));
$html_only = (isset($fields['html']) && ($fields['html'] == '1' || $fields['html'] == 'true'));
//////////////////////////////////
// Set up Content Extractor
//////////////////////////////////
$extractor = new ContentExtractor(dirname(__FILE__).'/site_config/custom', dirname(__FILE__).'/site_config/standard');
$extractor->debug = $debug_mode;
SiteConfig::$debug = $debug_mode;
SiteConfig::use_apc($options->apc);
$extractor->fingerprints = $options->fingerprints;
$extractor->allowedParsers = $options->allowed_parsers;

////////////////////////////////
// Get RSS/Atom feed
////////////////////////////////
if (!$html_only) {
	debug('--------');
	debug("Attempting to process URL as feed");
	// Send user agent header showing PHP (prevents a HTML response from feedburner)
	//$http->userAgentDefault = HumbleHttpAgent::UA_PHP;
	// configure SimplePie HTTP extension class to use our HumbleHttpAgent instance
	//SimplePie_HumbleHttpAgent::set_agent($http);
	$feed = new SimplePie();
	// some feeds use the text/html content type - force_feed tells SimplePie to process anyway
	$feed->force_feed(true);
	//$feed->set_file_class('SimplePie_HumbleHttpAgent');
	//$feed->set_feed_url($url); // colons appearing in the URL's path get encoded
	$feed->feed_url = $url;
	$feed->set_autodiscovery_level(SIMPLEPIE_LOCATOR_NONE);
	$feed->set_timeout(20);
	$feed->enable_cache(false);
	$feed->set_stupidly_fast(true);
	$feed->enable_order_by_date(false); // we don't want to do anything to the feed
	$feed->set_url_replacements(array());
	// initialise the feed
	// the @ suppresses notices which on some servers causes a 500 internal server error
	$result = @$feed->init();
	//$feed->handle_content_type();
	//$feed->get_title();
	if ($result && (!is_array($feed->data) || count($feed->data) == 0)) {
		die('Sorry, no feed items found');
	}
	// from now on, we'll identify ourselves as a browser
	//$http->userAgentDefault = HumbleHttpAgent::UA_BROWSER;
    debug("------------ SimplePie errors ----------------\n",$feed->error);
     debug("------------ SimplePie errors (END) --------------------------------------\n");
}


////////////////////////////////////////////////////////////////////////////////
// Our given URL is not a feed, so let's create our own feed with a single item:
// the given URL. This basically treats all non-feed URLs as if they were
// single-item feeds.
////////////////////////////////////////////////////////////////////////////////
$isDummyFeed = false;
if ($html_only || !$result) {
	debug('--------');
	debug("Constructing a single-item feed from URL");
	$isDummyFeed = true;
	unset($feed, $result);
	// create single item dummy feed object
	class DummySingleItemFeed {
		public $item;
		function __construct($url) { $this->item = new DummySingleItem($url); }
		public function get_title() { return ''; }
		public function get_description() { return 'Content extracted from '.$this->item->url; }
		public function get_link() { return $this->item->url; }
		public function get_language() { return false; }
		public function get_image_url() { return false; }
		public function get_items($start=0, $max=1) { return array(0=>$this->item); }
	}
	class DummySingleItem {
		public $url;
		function __construct($url) { $this->url = $url; }
		public function get_permalink() { return $this->url; }
		public function get_title() { return ''; }
		public function get_date($format='') { return false; }
		public function get_author($key=0) { return null; }
		public function get_authors() { return null; }
		public function get_description() { return ''; }
		public function get_enclosure($key=0, $prefer=null) { return null; }
		public function get_enclosures() { return null; }
	}
	$feed = new DummySingleItemFeed($url);
}

////////////////////////////////////////////
// Create full-text feed
////////////////////////////////////////////
$output = new FeedWriter(RSS2_OBJ);
$output->setTitle($feed->get_title());
$output->setDescription($feed->get_description());
$output->setXsl('css/feed.xsl'); // Chrome uses this, most browsers ignore it
if ($valid_key && isset($_GET['pubsub'])) { // used only on fivefilters.org at the moment
	$output->addHub('http://fivefilters.superfeedr.com/');
	$output->addHub('http://pubsubhubbub.appspot.com/');
	$output->setSelf('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
}
$output->setLink($feed->get_link()); // Google Reader uses this for pulling in favicons
if ($img_url = $feed->get_image_url()) {
	$output->setImage($feed->get_title(), $feed->get_link(), $img_url);
}

////////////////////////////////////////////
// Loop through feed items
////////////////////////////////////////////
$items = $feed->get_items(0, $max);	
// Request all feed items in parallel (if supported)
$urls_sanitized = array();
$urls = array();
$count = 0;
foreach ($items as $key => $item) {
	$permalink = htmlspecialchars_decode($item->get_permalink());
	// Colons in URL path segments get encoded by SimplePie, yet some sites expect them unencoded
	$permalink = str_replace('%3A', ':', $permalink);
	// validateUrl() strips non-ascii characters
	// simplepie already sanitizes URLs so let's not do it again here.
	//$permalink = $http->validateUrl($permalink);
	if ($permalink) {
		$urls_sanitized[] = $permalink;
	}
	$urls[$key] = $permalink;
    $requests[] = array('url'=>$permalink,
                     'item' => $item,
                    'headers' => array('Accept' => 'text/html'),
                    
                    'useragent' =>'Mozilla/5.0 (Windows; U; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 2.0.50727)'
                            );
    $count++;
    if ($count >=$numbers )
        break;
}

// Next, make sure Requests can load internal classes
Requests::register_autoloader();
$options_req = array(
	'complete' => 'my_callback',
);
$exclude_on_fail = $options->exclude_items_on_fail;
// Setup a callback
function my_callback(&$request, $id) {
	//var_dump($id, $request);
    global $requests, $output,$extractor,$item,$options,$links,$valid_key,$xss_filter,$detect_language,$language_codes,$feed,$exclude_on_fail;
    $item = $requests[(int)$id]['item'];
    $newitem = $output->createNewItem();
    $newitem->setTitle(htmlspecialchars_decode( $item->get_title()/*$requests[(int)$id]['title']*/));
    // TODO: Allow error codes - some sites return correct content with error status
	// e.g. prospectmagazine.co.uk returns 403
	//if ($permalink && ($response = $http->get($permalink, true)) && $response['status_code'] < 300) {
    // $headers = $request->headers;
    if (isset($request) && isset($request->body))
    {
        if (isset($request->headers) && isset($request->headers['Content-Type']))
        {
            $response['headers'] = 'Content-Type:'.$request->headers['Content-Type'].'\n';
        }
        /*else
        {
             $response['headers'] = ""
        }*/
        $response['body'] = $request->body;
        $response['effective_url'] = $requests[$id]['url'];
        $response['referer'] = $request->headers['Referer'];
        $isDummyFeed = false;
        @include dirname(__FILE__).'/utils/processHTML.php';
    }
    //$requests[(int)$id];
    //debug("",null,true);
    debug(">>>>>>>>>>>>>>>> AFTER CALLBACK >>>>>>>>>>>\n");
    //var_dump($response['headers']);
}
debug(">>>>>>>>>>>>>>>> BEFORE CALLBACK (request)>>>>>>>>>>>\n");
debug(">>>>>>>>>>>>>>>> BEFORE CALLBACK (options)>>>>>>>>>>>\n");
if (!isset($requests))
    return;
// Send the request!
$responses = @Requests::request_multiple($requests, $options_req);

// Note: the response from the above call will be an associative array matching
// $requests with the response data, however we've already handled it in
// my_callback() anyway!
//
// If you don't believe me, uncomment this:
 //var_dump($responses);
 
 if (!$debug_mode) {
	if (isset($callback)) echo "$callback("; // if $callback is set, $format also == 'json'
	if (isset($format) and $format == 'json') $output->setFormat((isset($callback)) ? JSON : JSONP);
	$add_to_cache = $options->caching;
	// is smart cache mode enabled?
	if ($add_to_cache && $options->apc && $options->smart_cache) {
		// yes, so only cache if this is the second request for this URL
		$add_to_cache = ($apc_cache_hits >= 2);
		// purge cache
		if ($options->cache_cleanup > 0) {
			if (rand(1, $options->cache_cleanup) == 1) {
				// apc purge code adapted from from http://www.thimbleopensource.com/tutorials-snippets/php-apc-expunge-script
				$_apc_data = apc_cache_info('user');
				foreach ($_apc_data['cache_list'] as $_apc_item) {
				  if ($_apc_item['ttl'] > 0 && ($_apc_item['ttl'] + $_apc_item['creation_time'] < time())) {
					apc_delete($_apc_item['info']);
				  }
				}
			}
		}
	}
     //file_put_contents('./log_'.date("j.n.Y").'.log', "---------------- CACHE MODE ".dump_str($add_to_cache)." \n", FILE_APPEND); 
	if ($add_to_cache) {
		ob_start();
		$output->genarateFeed();
		$output = ob_get_contents();
		ob_end_clean();
		if ($html_only && $item_count == 0) {
			// do not cache - in case of temporary server glitch at source URL
		} else {
			$cache = get_cache();
			if ($add_to_cache) $cache->save($output, $cache_id);
		}
        //file_put_contents('./log_'.date("j.n.Y").'.log', "---------------- FEED BEFORE END ".dump_str($output)." \n", FILE_APPEND); 
		echo $output;
	} else {
		//file_put_contents('./log_'.date("j.n.Y").'.log', "---------------- END OF FEED yahoo news -------------  \n", FILE_APPEND); 
       // debug("###########################  >>>>> ",$output);
        $rss=$output->genarateFeed();
        debug("###########################  >>>>> ");
        
	}
	if (isset($callback)) echo ');';
}// end if (!$debug_mode)
    
?>