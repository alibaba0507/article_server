<?php
////////////////////////////////
// Load config file
////////////////////////////////
require dirname(__FILE__).'/config/config.php';

////////////////////////////////
// Autoload libraries and also
// debug print
////////////////////////////////
require_once(dirname(__FILE__).'/utils/autoload.php'); // for debug call  debug($msg,$obj)
require_once(dirname(__FILE__).'/utils/utils.php'); // for debug call  debug($msg,$obj)
require dirname(__FILE__).'/libraries/simplepie/SimplePieAutoloader.php';
// always include Simplepie_Core as it defines constants which other SimplePie components
// assume will always be available.
require dirname(__FILE__).'/libraries/simplepie/SimplePie/Core.php';

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


///////////////////////////////////
/// Get PASS PARAMETERS
//////////////////////////////////
$list_urls = explode("\n",urldecode($fields['keyword']));//$_GET['keyword'].split("\n");
///////////////////////////////////////////////
// Link handling
///////////////////////////////////////////////
//if (isset($_GET['links']) && in_array($_GET['links'], array('preserve', 'footnotes', 'remove'))) {
if (isset($fields['links']) && in_array($fields['links'], array('preserve', 'footnotes', 'remove'))) {
	$links = $fields['links'];
} else {
	$links = 'preserve';
}
////////////////////////////////
// Debug mode?
// See the config file for debug options.
////////////////////////////////
$debug_mode = false;
/////////////////////////////////////
/// Delete log content
////////////////////////////////////
///////////////////////////////////////////////////////
/// Debug will work only if 
//  $options->debug = true; in config.php is set 
//////////////////////////////////////////////////////
debug("",null,true);
//////////////////////////////////
// Set up Content Extractor
//////////////////////////////////
$extractor = new ContentExtractor(dirname(__FILE__).'/site_config/custom', dirname(__FILE__).'/site_config/standard');
$extractor->debug = $debug_mode;
SiteConfig::$debug = $debug_mode;
SiteConfig::use_apc($options->apc);
$extractor->fingerprints = $options->fingerprints;
$extractor->allowedParsers = $options->allowed_parsers;


///////////////////////////////////////////
/// Create Dummy Feed 
////////////////////////////////////////////
class DummySingleItemFeed {
    public $item = array();
    public $title = "";
   // public $curl_options = array();
    function __construct(/*$title*/) { /*$this->title = $title;*/ }
   // public function set_curl_options(){$options}{$this->curl_options = $options;}
    public function get_title() { return 'Content extracted from user items'; }
    public function get_description() { return 'Content extracted from user items'; }
    //Return url path of current page
    public function get_link() { return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] 
                === 'on' ? "https" : "http") . "://" . 
          $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']; }
    public function get_language() { return false; }
    public function get_image_url() { return false; }
    public function add_item($item){$this->item[sizeof($this->item)] = $item;}
    public function get_items($start=0, $max=1) { return array_slice($this->item,0,sizeof($this->item));/*array(0=>$this->item);*/ }
}
class DummySingleItem {
		public $url;
        public $title;
       // public $item_date =  date("D M d, Y G:i");
        public $descr;
		function __construct($url) { $this->url = $url; }
		public function get_permalink() { return $this->url; }
		public function get_title() { return $this->title; }
        public function set_title($title){$this->title = $title;}
		public function get_date($format='') { return date("D M d, Y G:i"); }
        //public function set_date($date){$this->item_date = $date};
		public function get_author($key=0) { return null; }
		public function get_authors() { return null; }
		public function get_description() { return $this->descr; }
        public function set_description($descr) { $this->descr = $descr; }
		public function get_enclosure($key=0, $prefer=null) { return null; }
		public function get_enclosures() { return null; }
	}
$feed = new DummySingleItemFeed();

////////////////////////////////////////////////////////////////
/// Add DummyItem with passed url link , need it for extracton
///////////////////////////////////////////////////////////////
for ($i = 0;$i < sizeof($list_urls);$i++)
{
    $list_urls[$i] = trim(preg_replace('/\s+/', '', $list_urls[$i]));
    $item = new DummySingleItem($list_urls[$i]);
    $item->set_title("Custom URL Extraction <a href=\"".$list_urls[$i]."\">".parse_url($list_urls[$i] , PHP_URL_PATH) . "</a>");
    $item->set_description(" USER Define URL ".$i);
    $feed->add_item($item);
}

debug(">>>>>>>>>>>>>>>> DummySingleItemFeed >>>>>>>>>>>> \n",$feed);
////////////////////////////////////////////
// Create full-text feed
////////////////////////////////////////////
 
//$output = new FeedWriter();
$output = new FeedWriter(RSS2_OBJ);
$output->setTitle($feed->get_title());
$output->setDescription($feed->get_description());
$output->setXsl('css/feed.xsl'); // Chrome uses this, most browsers ignore it
/*if ($valid_key && isset($_GET['pubsub'])) { // used only on fivefilters.org at the moment
	$output->addHub('http://fivefilters.superfeedr.com/');
	$output->addHub('http://pubsubhubbub.appspot.com/');
	$output->setSelf('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
}*/

$output->setLink($feed->get_link()); // Google Reader uses this for pulling in favicons
if ($img_url = $feed->get_image_url()) {
	$output->setImage($feed->get_title(), $feed->get_link(), $img_url);
}

////////////////////////////////////////////
// Loop through feed items
////////////////////////////////////////////
$items = $feed->get_items(0);

// Request all feed items in parallel (if supported)
$urls_sanitized = array();
$urls = array();
//file_put_contents('./log_'.date("j.n.Y").'.log', "---------------- FEED BEFORE ITEM LOOP ".count($items)." \n", //FILE_APPEND); 
//debug(">>>>>>>>>>>>>>>>>>>>> GET ITEMS >>>>>>>>>>>>>> \n",$items);
$requests = array();

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
    //$http->get($permalink);
}
// Next, make sure Requests can load internal classes
Requests::register_autoloader();
$options_req = array(
	'complete' => 'my_callback',
);
// Setup a callback
function my_callback(&$request, $id) {
	//var_dump($id, $request);
    global $requests, $output,$extractor,$item,$options,$links,$valid_key,$xss_filter,$detect_language,$language_codes,$feed;
    $item = $requests[(int)$id]['item'];
    $newitem = $output->createNewItem();
    $newitem->setTitle(htmlspecialchars_decode( $item->get_title()/*$requests[(int)$id]['title']*/));
    // TODO: Allow error codes - some sites return correct content with error status
	// e.g. prospectmagazine.co.uk returns 403
	//if ($permalink && ($response = $http->get($permalink, true)) && $response['status_code'] < 300) {
    // $headers = $request->headers;
	$response['headers'] = 'Content-Type:'.$request->headers['Content-Type'].'\n';
    $response['body'] = $request->body;
    $response['effective_url'] = $requests[$id]['url'];
    $response['referer'] = $request->headers['Referer'];
    $isDummyFeed = false;
    @include dirname(__FILE__).'/utils/processHTML.php';
    //$requests[(int)$id];
    debug("",null,true);
    debug(">>>>>>>>>>>>>>>> AFTER CALLBACK >>>>>>>>>>>\n",$output);
    //var_dump($response['headers']);
}
debug(">>>>>>>>>>>>>>>> BEFORE CALLBACK (request)>>>>>>>>>>>\n",$requests);
debug(">>>>>>>>>>>>>>>> BEFORE CALLBACK (options)>>>>>>>>>>>\n",$options_req);
// Send the request!
$responses = @Requests::request_multiple($requests, $options_req);

debug(">>>>>>>>>>>>>>>> AFTE CALLBACK (responses)>>>>>>>>>>>\n",$responses);
//debug(">>>>>>>>>>>>>>>> BEFORE CALLBACK (options)>>>>>>>>>>>\n",$options_req);
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
        debug("###########################  >>>>> ",$rss);
        
	}
	if (isset($callback)) echo ');';
}// end if (!$debug_mode)

?>