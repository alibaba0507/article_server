<?php
////////////////////////////////
// Load config file
////////////////////////////////
//$dir = dirname(dirname(__FILE__));
//require_once($dir.'/config/config.php');

//*********************** Functions ************************************//

function getReferer()
{
	return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
}
function getBrowserType () {
if (!empty($_SERVER['HTTP_USER_AGENT'])) 
{ 
   $HTTP_USER_AGENT = $_SERVER['HTTP_USER_AGENT']; 
} 
else if (!empty($HTTP_SERVER_VARS['HTTP_USER_AGENT'])) 
{ 
   $HTTP_USER_AGENT = $HTTP_SERVER_VARS['HTTP_USER_AGENT']; 
} 
else if (!isset($HTTP_USER_AGENT)) 
{ 
   $HTTP_USER_AGENT = ''; 
} 
if (preg_match('/Opera(\| )([0-9].[0-9]{1,2})/', $HTTP_USER_AGENT, $log_version)) 
{ 
   $browser_version = $log_version[2]; 
   $browser_agent = 'opera'; 
} 
else if (preg_match('/MSIE ([0-9].[0-9]{1,2})/', $HTTP_USER_AGENT, $log_version)) 
{ 
   $browser_version = $log_version[1]; 
   $browser_agent = 'ie'; 
} 
else if (preg_match('/OmniWeb\/([0-9].[0-9]{1,2})/', $HTTP_USER_AGENT, $log_version)) 
{ 
   $browser_version = $log_version[1]; 
   $browser_agent = 'omniweb'; 
} 
else if (preg_match('/Netscape([0-9]{1})/', $HTTP_USER_AGENT, $log_version)) 
{ 
   $browser_version = $log_version[1]; 
   $browser_agent = 'netscape'; 
} 
else if (preg_match('/Mozilla\/([0-9].[0-9]{1,2})/', $HTTP_USER_AGENT, $log_version)) 
{ 
   $browser_version = $log_version[1]; 
   $browser_agent = 'mozilla'; 
} 
else if (preg_match('/Konqueror\/([0-9].[0-9]{1,2})/', $HTTP_USER_AGENT, $log_version)) 
{ 
   $browser_version = $log_version[1]; 
   $browser_agent = 'konqueror'; 
} 
else 
{ 
   $browser_version = 0; 
   $browser_agent = 'other'; 
}
return $browser_agent;
}

// Function to get the client ip address
function get_client_ip_env() {
    $ipaddress = '';
    if (getenv('HTTP_CLIENT_IP'))
        $ipaddress = getenv('HTTP_CLIENT_IP');
    else if(getenv('HTTP_X_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
    else if(getenv('HTTP_X_FORWARDED'))
        $ipaddress = getenv('HTTP_X_FORWARDED');
    else if(getenv('HTTP_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_FORWARDED_FOR');
    else if(getenv('HTTP_FORWARDED'))
        $ipaddress = getenv('HTTP_FORWARDED');
    else if(getenv('REMOTE_ADDR'))
        $ipaddress = getenv('REMOTE_ADDR');
    else
        $ipaddress = 'NA';
 
    return $ipaddress;
}

// Function to get the client ip address
function get_client_ip_server() {
    $ipaddress = '';
    if ($_SERVER['HTTP_CLIENT_IP'])
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if($_SERVER['HTTP_X_FORWARDED_FOR'])
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if($_SERVER['HTTP_X_FORWARDED'])
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if($_SERVER['HTTP_FORWARDED_FOR'])
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if($_SERVER['HTTP_FORWARDED'])
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if($_SERVER['REMOTE_ADDR'])
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'NA';
 
    return $ipaddress;
}

function startsWith($haystack, $needle) 
{
    // search backwards starting from haystack length characters from the end
    return ($needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE);
}
function endsWith($haystack, $needle) {
    // search forward starting from end minus needle length characters
    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
}


function inserthml($artcl,$searchFor,$replWith)
{
    $tmp = "";
	$startPos = 0;
    $posTag = strpos($artcl, '>', 1);	
	while ($posTag > 0)
	{
		$posEndTag = strpos($artcl, '<', $posTag + 1);
        $tmp .= substr($artcl,$startPos,$posTag - $startPos);
        $tmpStr = substr($artcl,$posTag,$posEndTag - $posTag);
        $tmpStr = str_replace($searchFor,$replWith,$tmpStr);
		$tmp .= $tmpStr;
		$startPos = $posEndTag + 1;
		$posTag = strpos($artcl, '>', $startPos);	
		if ($posTag < 1)
		{ // append the rest of the 
		  $tmp .= substr($artcl,$startPos);
		}
	}
	return tmp;
}

function closeHTMLtags($html) {
    preg_match_all('#<([a-z]+)(?: .*)?(?<![/|/ ])>#iU', $html, $result);
    $openedtags = $result[1];
    preg_match_all('#</([a-z]+)>#iU', $html, $result);

    $closedtags = $result[1];
    $len_opened = count($openedtags);

    if (count($closedtags) == $len_opened) {
        return $html;
    }
    $openedtags = array_reverse($openedtags);
    for ($i=0; $i < $len_opened; $i++) {
        if (!in_array($openedtags[$i], $closedtags)) {
            $html .= '</'.$openedtags[$i].'>';
        } else {
            unset($closedtags[array_search($openedtags[$i], $closedtags)]);
        }
    }
    return $html;
}
function removeHTMLTag($html_txt,$tagName)
{
     libxml_use_internal_errors(true);
     // Must clean HTML from script and non script head so on ...
    $doc = new DOMDocument();
    //////// Tydy support not provided /////////////
    /////// Check on the server /////////////////
    /*$tidy_config = array( 
                         'clean' => true, 
                         'output-xhtml' => true, 
                         'show-body-only' => true, 
                         'wrap' => 0, 

                         ); 

    $tidy = tidy_parse_string( $txt, $tidy_config, 'UTF8'); 
    $tidy->cleanRepair(); 
    $doc->loadHTML(( (string) $tidy));
    */
    // load the HTML string we want to strip
    $doc->loadHTML($html_txt);
    //get_inner_html
    // get all the script tags
    $script_tags = $doc->getElementsByTagName($tagName/*'script'*/);

    $length = $script_tags->length;

    // for each tag, remove it from the DOM
    for ($i = 0; $i < $length; $i++) {
      debug(">>>>>>>>>>> removeHTMLTag >>>>>>\n",$script_tags->item($i));
      $script_tags->item($i)->parentNode->removeChild($script_tags->item($i));
    }

    // get the HTML string back
    $no_script_html_string = $doc->saveHTML();
    $txt = $no_script_html_string;
    return $txt;
}

function createFeed($rss)
{
     $indx = stripos($rss,"<rss version=\"2.0\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\" xmlns:media=\"http://search.yahoo.com/mrss/\">");
    $rss  = substr($rss,$indx);
   // debug(">>>>>>>>>>>> PROCESS FILES CURL >>>>>>>>>>>>>>>>>",$returned);
    $feed = simplexml_load_string($rss);
   //  debug(">>>>>>>>>>>> PROCESS FILES CURL >>>>>>>>>>>>>>>>>",($feed));
    return $feed;
}
function processFeed($filename,$type = null,$fields = null)
{
    //debug(">>>>>>>>>>>> PROCESS FILES CURL ".$type .">>>>> " .$urlsource ."   >>>>>>>>>",$fields);
   /* $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $urlsource);
    if ($type !== null && $fields !== null)
    {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$fields);
    }
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $returned = curl_exec($ch);
    curl_close($ch);
    */
    $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://".$_SERVER['SERVER_NAME'].
    dirname($_SERVER['PHP_SELF']); 
   $filename = $actual_link.$filename;
   debug(">>>>>>>>>> INCLUDE >>>[".strval ($filename) ."]>>>>>>>>>>>>>\n");
   $rss = '';
   //$fn = "'".strval ($urlsource)."'";
   //debug(">>>>>>>>>> INCLUDE >>>[".$fn ."]>>>>>>>>>>>>>\n");
   //include ($fn);//'only_spin.php';//$urlsource;
   // if (is_file($filename)) {
       // ob_start();
        include $filename;
       // $b = ob_get_clean();
        //debug(">>>>>>>>>> INCLUDE (III)>>>[".$filename ."]>>>>>>>>>>>>>\n",$b);
    //}
   $returned = $rss;
   //$returned = json_decode($returned,true);
   debug(">>>>>>>>>>>> BEFORE PROCESS FILES CURL >>>>>>>>>>>>>>>>>",($returned));
   
   //return ($returned->rss);
    // Clean the document for parsing
    $indx = stripos($returned,"<rss version=\"2.0\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\" xmlns:media=\"http://search.yahoo.com/mrss/\">");
    $returned  = substr($returned,$indx);
   // debug(">>>>>>>>>>>> PROCESS FILES CURL >>>>>>>>>>>>>>>>>",$returned);
    $feed = simplexml_load_string($returned);
   //  debug(">>>>>>>>>>>> PROCESS FILES CURL >>>>>>>>>>>>>>>>>",($feed));
    return $feed;
    
}

/**
 * Print all debug information to 
 * file 
 *@parms $msg - string message to be attached
 *@params $obj - object to be printed could be anyting (class,json,array ...)
 */
function debug($msg,$obj = null,$delete = false) {
    $dir = dirname(dirname(__FILE__));
    include $dir.'/config/config.php';
    if ($options->debug == false)
        return;
    $logFile = dirname(dirname(__FILE__)).'/tempfiles/log_app.log';
	if ($delete === true)
    {
       // echo " >>>> DELETE - TRUE </br>";        
        //$out = "";
       // if ($obj){$out = var_export($obj,true);}
       // file_put_contents('tempfiles/log_app.log', '>>>>>> DELETE TRUE >>>>\n'.$msg.$out." \n", FILE_APPEND);
        unlink( $logFile);
        return;
    }
    $out = "";
    if ($obj){$out = var_export(serialize($obj),true);}
    //file_put_contents('./log_'.date("j.n.Y").'.log', $msg.$out." \n", FILE_APPEND);
    if ($options->print_screen == true)
        echo  $msg.$out." \n";
    else
        file_put_contents($logFile, $msg.$out." \n", FILE_APPEND);
}


///////////////////////////////
// HELPER FUNCTIONS
///////////////////////////////

function url_allowed($url) {
	global $options;
	if (!empty($options->allowed_urls)) {
		$allowed = false;
		foreach ($options->allowed_urls as $allowurl) {
			if (stristr($url, $allowurl) !== false) {
				$allowed = true;
				break;
			}
		}
		if (!$allowed) return false;
	} else {
		foreach ($options->blocked_urls as $blockurl) {
			if (stristr($url, $blockurl) !== false) {
				return false;
			}
		}
	}
	return true;
}

//////////////////////////////////////////////
// Convert $html to UTF8
// (uses HTTP headers and HTML to find encoding)
// adapted from http://stackoverflow.com/questions/910793/php-detect-encoding-and-make-everything-utf-8
//////////////////////////////////////////////
function convert_to_utf8($html, $header=null)
{
	$encoding = null;
	if ($html || $header) {
		if (is_array($header)) $header = implode("\n", $header);
		if (!$header || !preg_match_all('/^Content-Type:\s+([^;]+)(?:;\s*charset=["\']?([^;"\'\n]*))?/im', $header, $match, PREG_SET_ORDER)) {
			// error parsing the response
			debug('Could not find Content-Type header in HTTP response');
		} else {
			$match = end($match); // get last matched element (in case of redirects)
			if (isset($match[2])) $encoding = trim($match[2], "\"' \r\n\0\x0B\t");
		}
		// TODO: check to see if encoding is supported (can we convert it?)
		// If it's not, result will be empty string.
		// For now we'll check for invalid encoding types returned by some sites, e.g. 'none'
		// Problem URL: http://facta.co.jp/blog/archives/20111026001026.html
		if (!$encoding || $encoding == 'none') {
			// search for encoding in HTML - only look at the first 50000 characters
			// Why 50000? See, for example, http://www.lemonde.fr/festival-de-cannes/article/2012/05/23/deux-cretes-en-goguette-sur-la-croisette_1705732_766360.html
			// TODO: improve this so it looks at smaller chunks first
			$html_head = substr($html, 0, 50000);
			if (preg_match('/^<\?xml\s+version=(?:"[^"]*"|\'[^\']*\')\s+encoding=("[^"]*"|\'[^\']*\')/s', $html_head, $match)) {
				$encoding = trim($match[1], '"\'');
			} elseif (preg_match('/<meta\s+http-equiv=["\']?Content-Type["\']? content=["\'][^;]+;\s*charset=["\']?([^;"\'>]+)/i', $html_head, $match)) {
				$encoding = trim($match[1]);
			} elseif (preg_match_all('/<meta\s+([^>]+)>/i', $html_head, $match)) {
				foreach ($match[1] as $_test) {
					if (preg_match('/charset=["\']?([^"\']+)/i', $_test, $_m)) {
						$encoding = trim($_m[1]);
						break;
					}
				}
			}
		}
		if (isset($encoding)) $encoding = trim($encoding);
		// trim is important here!
		if (!$encoding || (strtolower($encoding) == 'iso-8859-1')) {
			// replace MS Word smart qutoes
			$trans = array();
			$trans[chr(130)] = '&sbquo;';    // Single Low-9 Quotation Mark
			$trans[chr(131)] = '&fnof;';    // Latin Small Letter F With Hook
			$trans[chr(132)] = '&bdquo;';    // Double Low-9 Quotation Mark
			$trans[chr(133)] = '&hellip;';    // Horizontal Ellipsis
			$trans[chr(134)] = '&dagger;';    // Dagger
			$trans[chr(135)] = '&Dagger;';    // Double Dagger
			$trans[chr(136)] = '&circ;';    // Modifier Letter Circumflex Accent
			$trans[chr(137)] = '&permil;';    // Per Mille Sign
			$trans[chr(138)] = '&Scaron;';    // Latin Capital Letter S With Caron
			$trans[chr(139)] = '&lsaquo;';    // Single Left-Pointing Angle Quotation Mark
			$trans[chr(140)] = '&OElig;';    // Latin Capital Ligature OE
			$trans[chr(145)] = '&lsquo;';    // Left Single Quotation Mark
			$trans[chr(146)] = '&rsquo;';    // Right Single Quotation Mark
			$trans[chr(147)] = '&ldquo;';    // Left Double Quotation Mark
			$trans[chr(148)] = '&rdquo;';    // Right Double Quotation Mark
			$trans[chr(149)] = '&bull;';    // Bullet
			$trans[chr(150)] = '&ndash;';    // En Dash
			$trans[chr(151)] = '&mdash;';    // Em Dash
			$trans[chr(152)] = '&tilde;';    // Small Tilde
			$trans[chr(153)] = '&trade;';    // Trade Mark Sign
			$trans[chr(154)] = '&scaron;';    // Latin Small Letter S With Caron
			$trans[chr(155)] = '&rsaquo;';    // Single Right-Pointing Angle Quotation Mark
			$trans[chr(156)] = '&oelig;';    // Latin Small Ligature OE
			$trans[chr(159)] = '&Yuml;';    // Latin Capital Letter Y With Diaeresis
			$html = strtr($html, $trans);
		}
		if (!$encoding) {
			debug('No character encoding found, so treating as UTF-8');
			$encoding = 'utf-8';
		} else {
			debug('Character encoding: '.$encoding);
			if (strtolower($encoding) != 'utf-8') {
				debug('Converting to UTF-8');
				$html = SimplePie_Misc::change_encoding($html, $encoding, 'utf-8');
				/*
				if (function_exists('iconv')) {
					// iconv appears to handle certain character encodings better than mb_convert_encoding
					$html = iconv($encoding, 'utf-8', $html);
				} else {
					$html = mb_convert_encoding($html, 'utf-8', $encoding);
				}
				*/
			}
		}
	}
	return $html;
}

function makeAbsolute($base, $elem) {
	$base = new SimplePie_IRI($base);
	// remove '//' in URL path (used to prevent URLs from resolving properly)
	// TODO: check if this is still the case
	if (isset($base->path)) $base->path = preg_replace('!//+!', '/', $base->path);
	foreach(array('a'=>'href', 'img'=>'src') as $tag => $attr) {
		$elems = $elem->getElementsByTagName($tag);
		for ($i = $elems->length-1; $i >= 0; $i--) {
			$e = $elems->item($i);
			//$e->parentNode->replaceChild($articleContent->ownerDocument->createTextNode($e->textContent), $e);
			makeAbsoluteAttr($base, $e, $attr);
		}
		if (strtolower($elem->tagName) == $tag) makeAbsoluteAttr($base, $elem, $attr);
	}
}
function makeAbsoluteAttr($base, $e, $attr) {
	if ($e->hasAttribute($attr)) {
		// Trim leading and trailing white space. I don't really like this but 
		// unfortunately it does appear on some sites. e.g.  <img src=" /path/to/image.jpg" />
		$url = trim(str_replace('%20', ' ', $e->getAttribute($attr)));
		$url = str_replace(' ', '%20', $url);
		if (!preg_match('!https?://!i', $url)) {
			if ($absolute = SimplePie_IRI::absolutize($base, $url)) {
				$e->setAttribute($attr, $absolute);
			}
		}
	}
}
function makeAbsoluteStr($base, $url) {
	$base = new SimplePie_IRI($base);
	// remove '//' in URL path (causes URLs not to resolve properly)
	if (isset($base->path)) $base->path = preg_replace('!//+!', '/', $base->path);
	if (preg_match('!^https?://!i', $url)) {
		// already absolute
		return $url;
	} else {
		if ($absolute = SimplePie_IRI::absolutize($base, $url)) {
			return $absolute;
		}
		return false;
	}
}
// returns single page response, or false if not found
function getSinglePage($item, $html, $url) {
	global $http, $extractor;
	debug('Looking for site config files to see if single page link exists');
	$site_config = $extractor->buildSiteConfig($url, $html);
	$splink = null;
	if (!empty($site_config->single_page_link)) {
		$splink = $site_config->single_page_link;
	} elseif (!empty($site_config->single_page_link_in_feed)) {
		// single page link xpath is targeted at feed
		$splink = $site_config->single_page_link_in_feed;
		// so let's replace HTML with feed item description
		$html = $item->get_description();
	}
	if (isset($splink)) {
		// Build DOM tree from HTML
		$readability = new Readability($html, $url);
		$xpath = new DOMXPath($readability->dom);
		// Loop through single_page_link xpath expressions
		$single_page_url = null;
		foreach ($splink as $pattern) {
			$elems = @$xpath->evaluate($pattern, $readability->dom);
			if (is_string($elems)) {
				$single_page_url = trim($elems);
				break;
			} elseif ($elems instanceof DOMNodeList && $elems->length > 0) {
				foreach ($elems as $item) {
					if ($item instanceof DOMElement && $item->hasAttribute('href')) {
						$single_page_url = $item->getAttribute('href');
						break 2;
					} elseif ($item instanceof DOMAttr && $item->value) {
						$single_page_url = $item->value;
						break 2;
					}
				}
			}
		}
		// If we've got URL, resolve against $url
		if (isset($single_page_url) && ($single_page_url = makeAbsoluteStr($url, $single_page_url))) {
			// check it's not what we have already!
			if ($single_page_url != $url) {
				// it's not, so let's try to fetch it...
				//$_prev_ref = $http->referer;
				//$http->referer = $single_page_url;
                $response_ref = Requests::get($single_page_url, array('Referer' => $response['referer']));
				if (($response_ref ) && $response_ref->status_code < 300) {
					$response_ref->headers['Referer'] = $_prev_ref;
					return $response_ref;
				}
				 $response['referer'] = $_prev_ref;
			}
		}
	}
	return false;
}

// based on content-type http header, decide what to do
// param: HTTP headers string
// return: array with keys: 'mime', 'type', 'subtype', 'action', 'name'
// e.g. array('mime'=>'image/jpeg', 'type'=>'image', 'subtype'=>'jpeg', 'action'=>'link', 'name'=>'Image')
function get_mime_action_info($headers) {
	global $options;
	// check if action defined for returned Content-Type
	$info = array();
	if (preg_match('!^Content-Type:\s*(([-\w]+)/([-\w\+]+))!im', $headers, $match)) {
		// look for full mime type (e.g. image/jpeg) or just type (e.g. image)
		// match[1] = full mime type, e.g. image/jpeg
		// match[2] = first part, e.g. image
		// match[3] = last part, e.g. jpeg
		$info['mime'] = strtolower(trim($match[1]));
		$info['type'] = strtolower(trim($match[2]));
		$info['subtype'] = strtolower(trim($match[3]));
		foreach (array($info['mime'], $info['type']) as $_mime) {
			if (isset($options->content_type_exc[$_mime])) {
				$info['action'] = $options->content_type_exc[$_mime]['action'];
				$info['name'] = $options->content_type_exc[$_mime]['name'];
				break;
			}
		}
	}
	return $info;
}

function remove_url_cruft($url) {
	// remove google analytics for the time being
	// regex adapted from http://navitronic.co.uk/2010/12/removing-google-analytics-cruft-from-urls/
	// https://gist.github.com/758177
	return preg_replace('/(\?|\&)utm_[a-z]+=[^\&]+/', '', $url);
}

function make_substitutions($string) {
	if ($string == '') return $string;
	global $item, $effective_url;
	$string = str_replace('{url}', htmlspecialchars($item->get_permalink()), $string);
	$string = str_replace('{effective-url}', htmlspecialchars($effective_url), $string);
	return $string;
}

function get_cache() {
	global $options, $valid_key;
	static $cache = null;
	if ($cache === null) {
		$frontendOptions = array(
			'lifetime' => 10*60, // cache lifetime of 10 minutes
			'automatic_serialization' => false,
			'write_control' => false,
			'automatic_cleaning_factor' => $options->cache_cleanup,
			'ignore_user_abort' => false
		);
		$backendOptions = array(
			'cache_dir' => ($valid_key) ? $options->cache_dir.'/rss-with-key/' : $options->cache_dir.'/rss/', // directory where to put the cache files
			'file_locking' => false,
			'read_control' => true,
			'read_control_type' => 'strlen',
			'hashed_directory_level' => $options->cache_directory_level,
			'hashed_directory_perm' => 0777,
			'cache_file_perm' => 0664,
			'file_name_prefix' => 'ff'
		);
		// getting a Zend_Cache_Core object
		$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
	}
	return $cache;
}

function dump_str($obj)
{
 return var_export($obj,true);
}

?>