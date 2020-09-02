<?php
//echo realpath(dirname(__FILE__).'/libraries').'<br>';
//echo PATH_SEPARATOR.'<br>';
//echo (dirname(dirname(__FILE__))).'<br>';
set_include_path(realpath(dirname(__FILE__).'/libraries').PATH_SEPARATOR.get_include_path());
// Autoloading of classes allows us to include files only when they're
// needed. If we've got a cached copy, for example, only Zend_Cache is loaded.
function autoload($class_name) {
	static $dir = null;
    
	if ($dir === null) $dir = (dirname(dirname(__FILE__)))."/libraries/";
	static $mapping = array(
		/*
		// Include Humble HTTP Agent to allow parallel requests and response caching
		'HumbleHttpAgent' => 'humble-http-agent/HumbleHttpAgent.php',
		'SimplePie_HumbleHttpAgent' => 'humble-http-agent/SimplePie_HumbleHttpAgent.php',
		'CookieJar' => 'humble-http-agent/CookieJar.php',
		// Include Zend Cache to improve performance (cache results)
		'Zend_Cache' => 'Zend/Cache.php',
		// Language detect
		'Text_LanguageDetect' => 'language-detect/LanguageDetect.php',
		// HTML5 Lib
		'HTML5_Parser' => 'html5/Parser.php',
		// htmLawed - used if XSS filter is enabled (xss_filter)
		'htmLawed' => 'htmLawed/htmLawed.php'
        */
        // Include FeedCreator for RSS/Atom creation
		'FeedWriter' => 'feedwriter/FeedWriter.php',
		'FeedItem' => 'feedwriter/FeedItem.php',
        // Include ContentExtractor and Readability for identifying and extracting content from URLs
		'ContentExtractor' => 'content-extractor/ContentExtractor.php',
        'SiteConfig' => 'content-extractor/SiteConfig.php',
		'Readability' => 'readability/Readability.php',
        'Requests' => 'http-request2/Requests.php'
	);
	if (isset($mapping[$class_name])) {
		debug("** Loading class $class_name ({$mapping[$class_name]})");
		require $dir.$mapping[$class_name];
		return true;
	} else {
		return false;
	}
}
spl_autoload_register('autoload');


?>