<?php
/* Full Content RSS config */

// Create config object
if (!isset($options)) $options = new stdClass();

// Base Directory of the server
// ------------------------------
// This is base dir where main index.php
// is. This depend on the server, so if 
// move to server check for base dir and 
// chane it
 
$options->protocol=$_SERVER['PROTOCOL'] = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
$options->host = $options->protocol .'://'.$_SERVER['HTTP_HOST'];
$options->base_html_dir = 'prod/articlecreator';
$options->baseurl =((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['PHP_SELF']));
$options->base_include_dir = $_SERVER['DOCUMENT_ROOT'].'/'.$options->base_html_dir ;//'';//'/prod/articlecreator';
// Enable service
// ----------------------
// Set this to false if you want to disable the service.
// If set to false, no feed is produced and users will 
// be told that the service is disabled.
$options->enabled = true;

// Debug mode
// ----------------------
// Enable or disable debugging. When enabled debugging works by passing
// &debug to the searchresults.php querystring.
// Valid values:
// true or 'user' (default) - let user decide
// 'admin' - debug works only for logged in admin users
// false - disabled
$options->debug = true;
$options->print_screen = false;//true;

// Default entries
// ----------------------
// The number of feed items to process when no API key is supplied
// and no &max=x value is supplied in the querystring.
$options->default_entries = 10;

// Max entries
// ----------------------
// The maximum number of feed items to process when no access key is supplied.
// This limits the user-supplied &max=x value. For example, if the user
// asks for 20 items to be processed (&max=20), if max_entries is set to 
// 10, only 10 will be processed.
$options->max_entries = 10;

// Rewrite relative URLs
// ----------------------
// With this enabled relative URLs found in the extracted content
// block are automatically rewritten as absolute URLs.
$options->rewrite_relative_urls = true;

// Exclude items if extraction fails
// ---------------------------------
// Excludes items from the resulting feed
// if we cannot extract any content from the
// item URL.
// Possible values...
// Enable: true
// Disable: false (default)
// User decides: 'user' (this option will appear on the form)
$options->exclude_items_on_fail = 'user';

// Enable multi-page support
// -------------------------
// If enabled, we will try to follow next page links on multi-page articles.
// Currently this only happens for sites where next_page_link has been defined 
// in a site config file.
$options->multipage = true;

// Enable caching
// ----------------------
// Enable this if you'd like to cache results
// for 10 minutes. Cache files are written to disk (in cache/ subfolders
// - which must be writable).
// Initially it's best to keep this disabled to make sure everything works
// as expected. If you have APC enabled, please also see smart_cache in the
// advanced section.
$options->caching = false;

// Cache directory
// ----------------------
// Only used if caching is true
$options->cache_dir = dirname(__FILE__).'/cache';

// Message to prepend (without access key)
// ----------------------
// HTML to insert at the beginning of each feed item when no access key is supplied.
// Substitution tags:
// {url} - Feed item URL
// {effective-url} - Feed item URL after we've followed all redirects
$options->message_to_prepend = '';

// Message to append
// ----------------------
// HTML to insert at the end of each feed item when no access key is supplied.
// Substitution tags:
// {url} - Feed item URL
// {effective-url} - Feed item URL after we've followed all redirects
$options->message_to_append = '';

// Error message when content extraction fails (without access key)
// ----------------------
$options->error_message = '[unable to retrieve full content]';

// Keep enclosure in feed items
// If enabled, we will try to preserve enclosures if present.
// ----------------------
$options->keep_enclosures = true;

// Detect language
// ---------------
// Should we try and find/guess the language of the article being processed?
// Values will be placed inside the <dc:language> element inside each <item> element
// Possible values:
// * Ignore language: 0
// * Use article/feed metadata (e.g. HTML lang attribute): 1 (default)
// * As above, but guess if not present: 2
// * Always guess: 3
// * User decides: 'user' (value of 0-3 can be passed in querystring: e.g. &l=2)
$options->detect_language = 1;

/////////////////////////////////////////////////
/// RESTRICT ACCESS /////////////////////////////
/////////////////////////////////////////////////


// URLs to allow
// ----------------------
// List of URLs (or parts of a URL) which the service will accept.
// If the list is empty, all URLs (except those specified in the blocked list below)
// will be permitted.
// Empty: array();
// Non-empty example: array('example.com', 'anothersite.org');
$options->allowed_urls = array();

// URLs to block
// ----------------------
// List of URLs (or parts of a URL) which the service will not accept.
// Note: this list is ignored if allowed_urls is not empty
$options->blocked_urls = array();

/////////////////////////////////////////////////
/// ADVANCED OPTIONS ////////////////////////////
/////////////////////////////////////////////////

// Enable XSS filter?
// ----------------------
$options->xss_filter = 'user';

// Allowed parsers
// ----------------------

// To disable HTML parsing with html5lib, you can remove it from this list.
// By default we allow both: libxml and html5lib.
$options->allowed_parsers = array('libxml', 'html5lib');
//$options->allowed_parsers = array('libxml'); //disable html5lib - forcing libxml in all cases

// Enable Cross-Origin Resource Sharing (CORS)
// ----------------------
// If enabled we'll send the following HTTP header
// Access-Control-Allow-Origin: *
// see http://en.wikipedia.org/wiki/Cross-origin_resource_sharing
$options->cors = false;

// Use APC user cache?
// ----------------------
// If enabled we will store site config files (when requested 
// for the first time) in APC's user cache. Keys prefixed with 'sc.'
// This improves performance by reducing disk access.
// Note: this has no effect if APC is unavailable on your server.
$options->apc = true;

// Smart cache (experimental)
// ----------------------
// With this option enabled we will not cache to disk immediately.
// We will store the cache key in APC and if it's requested again
// we will cache results to disk. Keys prefixed with 'cache.'
// This improves performance by reducing disk access.
// Note: this has no effect if APC is disabled or unavailable on your server,
// or if you have caching disabled.
$options->smart_cache = true;

// Fingerprints
// ----------------------
// key is fingerprint (fragment to find in HTML)
// value is host name to use for site config lookup if fingerprint matches
$options->fingerprints = array(
	// Posterous
	'<meta name="generator" content="Posterous"' => array('hostname'=>'fingerprint.posterous.com', 'head'=>true),
	// Blogger
	'<meta content=\'blogger\' name=\'generator\'' => array('hostname'=>'fingerprint.blogspot.com', 'head'=>true),
	'<meta name="generator" content="Blogger"' => array('hostname'=>'fingerprint.blogspot.com', 'head'=>true),
	// WordPress (hosted)
	// '<meta name="generator" content="WordPress.com"' => array('hostname'=>'fingerprint.wordpress.com', 'head'=>true),
	// WordPress (self-hosted and hosted)
	'<meta name="generator" content="WordPress' => array('hostname'=>'fingerprint.wordpress.com', 'head'=>true)
);

// User Agent strings - mapping domain names
// ----------------------
// e.g. $options->user_agents = array('example.org' => 'PHP/5.2');
$options->user_agents = array( 'lifehacker.com' => 'PHP/5.2',
							   'gawker.com' => 'PHP/5.2',
							   'deadspin.com' => 'PHP/5.2',
							   'kotaku.com' => 'PHP/5.2',
							   'jezebel.com' => 'PHP/5.2',
							   'io9.com' => 'PHP/5.2',
							   'jalopnik.com' => 'PHP/5.2',
							   'gizmodo.com' => 'PHP/5.2',
							   '.wikipedia.org' => 'Mozilla/5.2',
							   '.fok.nl' => 'Googlebot/2.1'
							  );

// URL Rewriting
// ----------------------
// Currently allows simple string replace of URLs.
// Useful for rewriting certain URLs to point to a single page
// or HTML view. Although using the single_page_link site config
// instruction is the preferred way to do this, sometimes, as
// with Google Docs URLs, it's not possible.
// Note: this might move to the site config file at some point.
$options->rewrite_url = array(
	// Rewrite public Google Docs URLs to point to HTML view:
	// if a URL contains docs.google.com, replace /Doc? with /View?
	'docs.google.com' => array('/Doc?' => '/View?'),
	'tnr.com' => array('tnr.com/article/' => 'tnr.com/print/article/'),
	'.m.wikipedia.org' => array('.m.wikipedia.org' => '.wikipedia.org')
);

// Content-Type exceptions
// -----------------------
// Here you can define different actions based
// on the Content-Type header returned by server.
// MIME type as key, action as value.
// Valid actions:
// * 'exclude' - exclude this item from the result
// * 'link' - create HTML link to the item
$options->content_type_exc = array( 
							   'application/pdf' => array('action'=>'link', 'name'=>'PDF'),
							   'image' => array('action'=>'link', 'name'=>'Image'),
							   'audio' => array('action'=>'link', 'name'=>'Audio'),
							   'video' => array('action'=>'link', 'name'=>'Video')
							  );

// Cache directory level
// ----------------------
// Spread cache files over different directories (only used if caching is enabled).
// Used to prevent large number of files in one directory.
// This corresponds to Zend_Cache's hashed_directory_level
// see http://framework.zend.com/manual/en/zend.cache.backends.html
// It's best not to change this if you're unsure.
$options->cache_directory_level = 0;

// Cache cleanup
// -------------
// 0 = script will not clean cache (rename cachecleanup.php and use it for scheduled (e.g. cron) cache cleanup)
// 1 = clean cache everytime the script runs (not recommended)
// 100 = clean cache roughly once every 100 script runs
// x = clean cache roughly once every x script runs
// ...you get the idea :)
$options->cache_cleanup = 100;

/////////////////////////////////////////////////
/// DO NOT CHANGE ANYTHING BELOW THIS ///////////
/////////////////////////////////////////////////

if (!defined('_FF_FTR_VERSION')) define('_FF_FTR_VERSION', '3.0');

if (basename(__FILE__) == 'config.php') {
	if (file_exists(dirname(__FILE__).'/custom_config.php')) {
		require_once dirname(__FILE__).'/custom_config.php';
	}
	
	// check for environment variables - often used on cloud platforms
	// environment variables should be prefixed with 'ftr_', e.g.
	// ftr_max_entries: 1
	// will set the max_entries value to 1.
	foreach ($options as $_key=>&$_val) {
		$_key = "ftr_$_key";
		if (($_env = getenv($_key)) !== false) {
			if (is_array($_val)) {
				if ($_key === 'ftr_admin_credentials') {
					$_val = array_combine(array('username', 'password'), array_map('trim', explode(':', $_env, 2)));
					if ($_val === false) $_val = array('username'=>'admin', 'password'=>'');
				}
			} elseif ($_env === 'true' || $_env === 'false') {
				$_val = ($_env === 'true');
			} elseif (is_numeric($_env)) {
				$_val = (int)$_env;
			} else { // string
				$_val = $_env;
			}
		}
	}
	unset($_key, $_val, $_env);
}