<?php
define('RSS2', 1);
define('JSON', 2);
define('JSONP', 3);
define('ATOM', 4);
define('RSS2_OBJ',5);
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
//echo dirname(__FILE__).'/../utils/utils.php';
//require_once(dirname(__FILE__).'/../utils/utils.php'); // for debug call  debug($msg,$obj)
 /**
 * Univarsel Feed Writer class
 *
 * Genarate RSS2 or JSON (original: RSS 1.0, RSS2.0 and ATOM Feed)
 *
 * Modified for FiveFilters.org's Full-Text RSS project
 * to allow for inclusion of hubs, JSON output. 
 * Stripped RSS1 and ATOM support.
 *                             
 * @package     UnivarselFeedWriter
 * @author      Anis uddin Ahmad <anisniit@gmail.com>
 * @link        http://www.ajaxray.com/projects/rss
 */
 class FeedWriter
 {
	 private $self          = null;     // self URL - http://feed2.w3.org/docs/warning/MissingAtomSelfLink.html
	 private $hubs          = array();  // PubSubHubbub hubs
	 private $channels      = array();  // Collection of channel elements
	 private $items         = array();  // Collection of items as object of FeedItem class.
	 private $data          = array();  // Store some other version wise data
	 private $CDATAEncoding = array();  // The tag names which have to encoded as CDATA
	 private $xsl			= null;		// stylesheet to render RSS (used by Chrome)
	 private $json			= null;		// JSON object
	 
	 private $version   = null; 
	
	/**
	* Constructor
	* 
	* @param    constant    the version constant (RSS2 or JSON).       
	*/ 
	function __construct($version = RSS2)
	{	
       // debug(" #################### FeedWriter 1 ########################");
		$this->version = $version;
	  //    debug(" #################### FeedWriter 1 ########################");
		// Setting default value for assential channel elements
		$this->channels['title']        = $version . ' Feed';
		$this->channels['link']         = 'http://www.ajaxray.com/blog';
       //   debug(" #################### FeedWriter 1 ########################");
				
		//Tag names to encode in CDATA
		$this->CDATAEncoding = array('description', 'content:encoded', 'content', 'subtitle', 'summary');
       //   debug(" #################### FeedWriter 1 ########################");
	}
	
	public function setFormat($format) {
		$this->version = $format;
	}
    public function getFormat()
    {
        return $this->version;
    }

	// Start # public functions ---------------------------------------------
	
	/**
	* Set a channel element
	* @access   public
	* @param    srting  name of the channel tag
	* @param    string  content of the channel tag
	* @return   void
	*/
	public function setChannelElement($elementName, $content)
	{
		$this->channels[$elementName] = $content ;
	}
	
	/**
	* Set multiple channel elements from an array. Array elements 
	* should be 'channelName' => 'channelContent' format.
	* 
	* @access   public
	* @param    array   array of channels
	* @return   void
	*/
	public function setChannelElementsFromArray($elementArray)
	{
		if(! is_array($elementArray)) return;
		foreach ($elementArray as $elementName => $content) 
		{
			$this->setChannelElement($elementName, $content);
		}
	}
	
	/**
	* Genarate the actual RSS/JSON file
	* 
	* @access   public
	* @return   void
	*/ 
	public function genarateFeed()
	{
		if ($this->version == RSS2) {
			header('Content-type: text/xml; charset=UTF-8');
			// this line prevents Chrome 20 from prompting download
			// used by Google: https://news.google.com/news/feeds?ned=us&topic=b&output=rss
			header('X-content-type-options: nosniff');
		} elseif ($this->version == JSON) {
			header('Content-type: application/json; charset=UTF-8');
			$this->json = new stdClass();
		} elseif ($this->version == JSONP) {
			header('Content-type: application/javascript; charset=UTF-8');
			$this->json = new stdClass();
		}
       // file_put_contents('./log_'.date("j.n.Y").'.log', "---------------- BEFORE PRINT HEAD \n", FILE_APPEND); 
       // debug(">>>>>>>>>>>>>>>>>> GENERATE HEADER >>>>>>>>>>>>>>>>>>>>>>>>>>>");
       $out = "";
       
           $out .= $this->printHead();
       //  debug(">>>>>>>>>>>>>>>>>> GENERATE CHANELS >>>>>>>>>>>>>>>>>>>>>>>>>>>");
		 $out .= $this->printChannels();
       //  debug(">>>>>>>>>>>>>>>>>> GENERATE CHANNELS >>>>>>>>>>>>>>>>>>>>>>>>>>>",$out);
		 $out .=  $this->printItems();
       // debug(">>>>>>>>>>>>>>>>>> GENERATE ITMEMS >>>>>>>>>>>>>>>>>>>>>>>>>>>",$out);
		 $out .= $this->printTale();
     //    debug(">>>>>>>>>>>>>>>>>> GENERATE TITLE >>>>>>>>>>>>>>>>>>>>>>>>>>>",$out);
		if  ($this->version == RSS2_OBJ)
            return $out;
        if ($this->version == JSON || $this->version == JSONP) {
			//echo json_encode($this->json);
            return ($this->json);
		}
	}
	
	/**
	* Create a new FeedItem.
	* 
	* @access   public
	* @return   object  instance of FeedItem class
	*/
	public function createNewItem()
	{
		$Item = new FeedItem($this->version);
		return $Item;
	}
	
	/**
	* Add a FeedItem to the main class
	* 
	* @access   public
	* @param    object  instance of FeedItem class
	* @return   void
	*/
	public function addItem($feedItem)
	{
		$this->items[] = $feedItem;    
	}
	
	// Wrapper functions -------------------------------------------------------------------
	
	/**
	* Set the 'title' channel element
	* 
	* @access   public
	* @param    srting  value of 'title' channel tag
	* @return   void
	*/
	public function setTitle($title)
	{
		$this->setChannelElement('title', $title);
	}
	
	/**
	* Add a hub to the channel element
	* 
	* @access   public
	* @param    string URL
	* @return   void
	*/
	public function addHub($hub)
	{
		$this->hubs[] = $hub;    
	}
	
	/**
	* Set XSL URL
	* 
	* @access   public
	* @param    string URL
	* @return   void
	*/
	public function setXsl($xsl)
	{
		$this->xsl = $xsl;    
	}	
	
	/**
	* Set self URL
	* 
	* @access   public
	* @param    string URL
	* @return   void
	*/
	public function setSelf($self)
	{
		$this->self = $self;    
	}	
	
	/**
	* Set the 'description' channel element
	* 
	* @access   public
	* @param    srting  value of 'description' channel tag
	* @return   void
	*/
	public function setDescription($desciption)
	{
		$tag = ($this->version == ATOM)? 'subtitle' : 'description'; 
		$this->setChannelElement($tag, $desciption);
	}
	
	/**
	* Set the 'link' channel element
	* 
	* @access   public
	* @param    srting  value of 'link' channel tag
	* @return   void
	*/
	public function setLink($link)
	{
		$this->setChannelElement('link', $link);
	}
	
	/**
	* Set the 'image' channel element
	* 
	* @access   public
	* @param    srting  title of image
	* @param    srting  link url of the imahe
	* @param    srting  path url of the image
	* @return   void
	*/
	public function setImage($title, $link, $url)
	{
		$this->setChannelElement('image', array('title'=>$title, 'link'=>$link, 'url'=>$url));
	}
	
	// End # public functions ----------------------------------------------
	
	// Start # private functions ----------------------------------------------
	
	/**
	* Prints the xml and rss namespace
	* 
	* @access   private
	* @return   void
	*/
	private function printHead()
	{
		if ($this->version == RSS2 || $this->version == RSS2_OBJ)
		{
			$out  = '<?xml version="1.0" encoding="utf-8"?>'."\n";
			if ($this->xsl) $out .= '<?xml-stylesheet type="text/xsl" href="'.htmlspecialchars($this->xsl).'"?>' . PHP_EOL;
			$out .= '<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:media="http://search.yahoo.com/mrss/">' . PHP_EOL;
          //  file_put_contents('./log_'.date("j.n.Y").'.log', "---------------- HEAD ".$this->dump_str($out)." \n", FILE_APPEND); 
			//debug(" >>>>>>>>>>>\n",$out);
            if ($this->version == RSS2_OBJ)
                return $out;
            else
                echo $out;
		}
		elseif ($this->version == JSON || $this->version == JSONP)
		{
			$this->json->rss = array('@attributes' => array('version' => '2.0'));
		}
	}
	
	/**
	* Closes the open tags at the end of file
	* 
	* @access   private
	* @return   void
	*/
	private function printTale()
	{
		if ($this->version == RSS2 || $this->version == RSS2_OBJ)
		{
		//	echo '</channel>'.PHP_EOL,'</rss>'; 
        if ($this->version == RSS2_OBJ)
            return  '</channel></rss>'.PHP_EOL; 
        else
            echo '</channel></rss>'.PHP_EOL; 
         //    file_put_contents('./log_'.date("j.n.Y").'.log',  '</channel></rss>'.PHP_EOL, FILE_APPEND); 
            
		}    
		// do nothing for JSON
	}

	/**
	* Creates a single node as xml format
	* 
	* @access   private
	* @param    string  name of the tag
	* @param    mixed   tag value as string or array of nested tags in 'tagName' => 'tagValue' format
	* @param    array   Attributes(if any) in 'attrName' => 'attrValue' format
	* @return   string  formatted xml tag
	*/
	private function makeNode($tagName, $tagContent, $attributes = null)
	{        
		if ($this->version == RSS2 || $this->version == RSS2_OBJ)
		{
			$nodeText = '';
			$attrText = '';
			if (is_array($attributes))
			{
				foreach ($attributes as $key => $value) 
				{
					$attrText .= " $key=\"$value\" ";
				}
			}
			$nodeText .= "<{$tagName}{$attrText}>";
			if (is_array($tagContent))
			{ 
				foreach ($tagContent as $key => $value) 
				{
					$nodeText .= $this->makeNode($key, $value);
				}
			}
			else
			{
				//$nodeText .= (in_array($tagName, $this->CDATAEncoding))? $tagContent : htmlentities($tagContent);
				$nodeText .= htmlspecialchars($tagContent);
			}           
			//$nodeText .= (in_array($tagName, $this->CDATAEncoding))? "]]></$tagName>" : "</$tagName>";
			$nodeText .= "</$tagName>";
			return $nodeText . PHP_EOL;
		}
		elseif ($this->version == JSON || $this->version == JSONP)
		{
			$tagName = (string)$tagName;
			$tagName = strtr($tagName, ':', '_');
			$node = null;
			if (!$tagContent && is_array($attributes) && count($attributes))
			{
				$node = array('@attributes' => $this->json_keys($attributes));
			} else {
				if (is_array($tagContent)) {
					$node = $this->json_keys($tagContent);
				} else {
					$node = $tagContent;
				}
			}
			return $node;
		}
		return ''; // should not get here
	}
	
	private function json_keys(array $array) {
		$new = array();
		foreach ($array as $key => $val) {
			if (is_string($key)) $key = strtr($key, ':', '_');
			if (is_array($val)) {
				$new[$key] = $this->json_keys($val);
			} else {
				$new[$key] = $val;
			}
		}
		return $new;
	}
	
	/**
	* @desc     Print channels
	* @access   private
	* @return   void
	*/
	private function printChannels()
	{
		//Start channel tag
        $out = "";
		if ($this->version == RSS2 || $this->version == RSS2_OBJ) {
			$out .= '<channel>' . PHP_EOL;    
            //echo '<channel>' . PHP_EOL;  
			// add hubs
			foreach ($this->hubs as $hub) {
				//echo $this->makeNode('link', '', array('rel'=>'hub', 'href'=>$hub, 'xmlns'=>'http://www.w3.org/2005/Atom'));
			$out .= '<link rel="hub"  href="'.htmlspecialchars($hub).'" xmlns="http://www.w3.org/2005/Atom" />' . PHP_EOL;
            //echo '<link rel="hub"  href="'.htmlspecialchars($hub).'" xmlns="http://www.w3.org/2005/Atom" />' . PHP_EOL;
			}
			// add self
			if (isset($this->self)) {
				//echo $this->makeNode('link', '', array('rel'=>'self', 'href'=>$this->self, 'xmlns'=>'http://www.w3.org/2005/Atom'));
				$out .= '<link rel="self" href="'.htmlspecialchars($this->self).'" xmlns="http://www.w3.org/2005/Atom" />' . PHP_EOL;
                //echo '<link rel="self" href="'.htmlspecialchars($this->self).'" xmlns="http://www.w3.org/2005/Atom" />' . PHP_EOL;
			}
			//Print Items of channel
			foreach ($this->channels as $key => $value) 
			{
				$node = $this->makeNode($key, $value);
                $out .= $node;
                //echo $node;
			}
            
             // file_put_contents('./log_'.date("j.n.Y").'.log', "---------------- HEAD ".$this->dump_str($out)." \n", FILE_APPEND); 
          if ($this->version == RSS2_OBJ)
             return $out;
          else
           echo $out;
        } elseif ($this->version == JSON || $this->version == JSONP) {
			$this->json->rss['channel'] = (object)$this->json_keys($this->channels);
		}
	}
	
	/**
	* Prints formatted feed items
	* 
	* @access   private
	* @return   void
	*/
	private function printItems()
	{    
        $out = "";
      //  debug("------------- item >>> ");
		foreach ($this->items as $item) 
		{
            $item_id = "";
			$thisItems = $item->getElements();
			// debug("------------- item >>> ", $thisItems);
			//$out .= $this->startItem();
			
			if ($this->version == JSON || $this->version == JSONP) {
				$json_item = array();
			}
			
			foreach ($thisItems as $feedItem ) 
			{
               //  debug("------------- item >>> ", $feedItem);
				if ($this->version == RSS2 || $this->version == RSS2_OBJ) {
                 //   debug("------------- ADD item >>> ");
					$item_id .= $this->makeNode($feedItem['name'], $feedItem['content'], $feedItem['attributes']);
                 //   debug("------------- ADD item AS >>>>>> \n",$item_id);
				} elseif ($this->version == JSON || $this->version == JSONP) {
					$json_item[strtr($feedItem['name'], ':', '_')] = $this->makeNode($feedItem['name'], $feedItem['content'], $feedItem['attributes']);
				}
			}
            if (strlen(trim($item_id)) > 0)
            {
              
                  $out .= ($this->startItem() . $item_id .$this->endItem());
        	}
         
            if ($this->version == JSON || $this->version == JSONP) {
				if (count($this->items) > 1) {
					$this->json->rss['channel']->item[] = $json_item;
				} else {
					$this->json->rss['channel']->item = $json_item;
				}
			}
		}
         // file_put_contents('./log_'.date("j.n.Y").'.log', "---------------- BEFORE PRINT ITEMS ".$this->dump_str($out)." \n", FILE_APPEND); 
       if ($this->version == RSS2_OBJ)
       {    
        //   debug("------------- item >>> ", $out);
         return $out;
       }else
       {   
         echo $out;
       }
	}
	
	/**
	* Make the starting tag of channels
	* 
	* @access   private
	* @return   void
	*/
	private function startItem()
	{
		if ($this->version == RSS2 || $this->version == RSS2_OBJ)
		{
			return '<item>' . PHP_EOL; 
		}    
		// nothing for JSON
	}
	
	/**
	* Closes feed item tag
	* 
	* @access   private
	* @return   void
	*/
	private function endItem()
	{
		if ($this->version == RSS2 || $this->version == RSS2_OBJ)
		{
			return '</item>' . PHP_EOL; 
		}    
		// nothing for JSON
	}
    
    private function dump_str($obj)
    {
        return var_export($obj,true);
    }
	
	// End # private functions ----------------------------------------------
 }