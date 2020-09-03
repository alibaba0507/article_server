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
///////////////////////////////////////////////////////
/// Debug will work only if 
//  $options->debug = true; in config.php is set 
//////////////////////////////////////////////////////
debug("",null,true);
///////////////////////////////////////////////
// Link handling
///////////////////////////////////////////////
//if (isset($_GET['links']) && in_array($_GET['links'], array('preserve', 'footnotes', 'remove'))) {
if (isset($fields['links']) && in_array($fields['links'], array('preserve', 'footnotes', 'remove'))) {
	$links = $fields['links'];
} else {
	$links = 'preserve';
}
///////////////////////////////////
/// Get PASS PARAMETERS
//////////////////////////////////
$txt = urldecode($fields['spin']); //$_POST['spin']);
debug(">>>>>>>>>>>> LOAD TXT >>>>>>>>>>>>>>\n");
$txt = closeHTMLtags($txt);
//$txt = removeHTMLTag($txt,'script');
debug(">>>>>>>>>>>> CLEAN SCRIPT TXT >>>>>>>>>>>>>>\n");
//debug(">>>>>>>>>>>> LOAD TXT >>>>>>>>>>>>>>\n",$txt);


///////////////////////////////////////////////////
//// Disable wornings for DOMDocument class
/////////////////////////////////////////////////
libxml_use_internal_errors(true);


////////////////////////////////
// Debug mode?
// See the config file for debug options.
////////////////////////////////
$debug_mode = false;
//////////////////////////////////
// Set up Content Extractor
//////////////////////////////////
$extractor = new ContentExtractor(dirname(__FILE__).'/site_config/custom', dirname(__FILE__).'/site_config/standard');
$extractor->debug = $debug_mode;
SiteConfig::$debug = $debug_mode;
SiteConfig::use_apc($options->apc);
$extractor->fingerprints = $options->fingerprints;
$extractor->allowedParsers = $options->allowed_parsers;
$effective_url = null;
$extract_result = $extractor->process($txt, $effective_url);
$readability = $extractor->readability;
$content_block = ($extract_result) ? $extractor->getContent() : null;			
$title = ($extract_result) ? $extractor->getTitle() : '';
debug(">>>>>>>>>>>> Extractor title ".$title .">>>>>>>>>>>>>>\n");   
  
if ($extract_result) {
    $readability->clean($content_block, 'select');
    if ($effective_url != null && $options->rewrite_relative_urls) makeAbsolute($effective_url, $content_block);
    // footnotes
    if (($links == 'footnotes') && (strpos($effective_url, 'wikipedia.org') === false)) {
        $readability->addFootnotes($content_block);
    }
    // remove nesting: <div><div><div><p>test</p></div></div></div> = <p>test</p>
    while ($content_block->childNodes->length == 1 && $content_block->firstChild->nodeType === XML_ELEMENT_NODE) {
        // only follow these tag names
        if (!in_array(strtolower($content_block->tagName), array('div', 'article', 'section', 'header', 'footer'))) break;
        //$html = $content_block->firstChild->innerHTML; // FTR 2.9.5
        $content_block = $content_block->firstChild;
    }
    // convert content block to HTML string
    // Need to preserve things like body: //img[@id='feature']
    if (in_array(strtolower($content_block->tagName), array('div', 'article', 'section', 'header', 'footer'))) {
        $html_parse = $content_block->innerHTML;
    } else {
        $html_parse = $content_block->ownerDocument->saveXML($content_block); // essentially outerHTML
    }
    unset($content_block);
    // post-processing cleanup
    $html_parse = preg_replace('!<p>[\s\h\v]*</p>!u', '', $html_parse);
    if ($links == 'remove') {
        $html_parse = preg_replace('!</?a[^>]*>!', '', $html_parse);
    }
    // get text sample for language detection
    $text_sample = strip_tags(substr($html_parse, 0, 500));
    $html_parse = make_substitutions($options->message_to_prepend).$html_parse;
    $html_parse .= make_substitutions($options->message_to_append);
    $txt = $html_parse;
    unset($html_parse);
    debug(">>>>>>>>>>>> Extractor Content ---- >>>>>>>>>>>>>>\n",make_substitutions($options->message_to_prepend)); 
     debug("  ---------------------------------------------------------------------  --------------- \n");
     debug(">>>>>>>>>>>> Extractor Content ---- >>>>>>>>>>>>>>\n",make_substitutions($options->message_to_append)); 
      debug("  ---------------------------------------------------------------------  --------------- \n");
      // filter xss?
		if ($xss_filter) {
			debug('Filtering HTML to remove XSS');
			$html = htmLawed::hl($html, array('safe'=>1, 'deny_attribute'=>'style', 'comment'=>1, 'cdata'=>1));
		}
    //debug(">>>>>>>>>>>> Extractor Content >>>>>>>>>>>>>>\n",$txt); 
    $txt = urldecode($txt);
    $txt = removeHTMLTag($txt,'script');
     debug("  ---------------------------------------------------------------------  --------------- \n");
}else
{
    $txt = removeHTMLTag($txt,'script');
}


///////////////////////////////////////////
/// Create Dummy Feed 
////////////////////////////////////////////
class DummySingleItemFeed {
		public $item;
		function __construct() { }
		public function get_title() { return ''; }
		public function get_description() { return 'User Selected Spin Content'; }
		public function get_link() { return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] 
                === 'on' ? "https" : "http") . "://" . 
          $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']; }
		public function get_language() { return false; }
		public function get_image_url() { return false; }
        public function set_item($item){$this->item = $item;}
		public function get_items($start=0, $max=1) { return array(0=>$this->item); }
	}
    
class DummySingleItem {
		public $description;
		function __construct($d) { $this->description = $d;  }
		public function get_permalink() { return ''; }
		public function get_title() { return 'User Item Custom '; }
		public function get_date($format='') { return false; }
		public function get_author($key=0) { return null; }
		public function get_authors() { return null; }
		public function get_description() { return $this->description; }
     //   public function set_description($descr) { $this->descr = $descr; }
		public function get_enclosure($key=0, $prefer=null) { return null; }
		public function get_enclosures() { return null; }
	}
$feed = new DummySingleItemFeed();
$item_cls = new DummySingleItem($txt);
//unset($txt);
//$item_cls->set_description($txt);
$feed->set_item($item_cls);

//debug(">>>>>>>>>>>>>>>>> SPIN ITEM >>>>>>>>>>>>>>>>>>",$item_cls);
$output = new FeedWriter(RSS2_OBJ);
//debug(">>>>>>>>>>>>>>>>> SPIN ITEM 1 >>>>>>>>>>>>>>>>>>");
$output->setTitle($feed->get_title());
//debug(">>>>>>>>>>>>>>>>> SPIN ITEM 1 >>>>>>>>>>>>>>>>>>");
$output->setDescription($feed->get_description());
//debug(">>>>>>>>>>>>>>>>> AFTER FEED WRITER LOAS >>>>>>>>>>>>>>>>>>");
$format = $output->getFormat();
//debug(">>>>>>>>>>>>>>>>> FEED Format type >>>>>>>>>>>>>>>>>> \n",$format);
$items = $feed->get_items(0);	
foreach ($items as $key => $item) {
    $newitem = $output->createNewItem();
	$newitem->setTitle(htmlspecialchars_decode($item->get_title()));
    $html =  $item->get_description();
   // debug("################ SPIN ITEM 1 #############",$html);
    // remove strange things
    $html = str_replace('</[>', '', $html);
    $html = convert_to_utf8($html/*, $response['headers']*/);
			// check site config for single page URL - fetch it if found
    $newitem->setDescription($html);
    $output->addItem($newitem);
    unset($html);
}// end for
//debug(">>>>>>>>>>>>>>>>> SPIN ITEM OUTPUT >>>>>>>>>>>>>>>>>>");
if (!$debug_mode) {
  //  debug(">>>>>>>>>>>>>>>>> SPIN ITEM OUTPUT 22222 >>>>>>>>>>>>>>>>>>");
     $rss=@$output->genarateFeed();
     debug(">>>>>>>> OUT >>>>>>>>\n",$rss);
     //fwrite($fh, "\xEF\xBB\xBF".$rss);
     //fclose($fh);
}


debug(">>>>>> ONLY SPIN PHP (END) >>>>>>>>\n");


/*
function debug($msg) {
	global $debug_mode;
	if ($debug_mode) {
		echo '* ',$msg,"\n";
		ob_flush();
		flush();
	}
}
*/
?>