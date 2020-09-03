<?php
/*
$dir = (dirname(__FILE__));
require_once($dir.'/config/config.php');
include $dir.'/utils/utils.php';
$base_url = $options->host.((strlen(trim($options->base_html_dir))>0)?'/'.$options->base_html_dir:'');//'/'.$options->base_html_dir;
$home = $base_url; 
$home_inc =$options->base_include_dir;
*/
$hasValidUser = true;
$feed = new stdClass();
if (!isset($_POST['numbers']))
{
 $feed->error = 'Missing number parameters';
 $hasValidUser = false;
}
if (!isset($_POST['feedsource']))
{
 $feed->error = 'Missing feedsource parameters';
 $hasValidUser = false;
}
if (!isset($_POST['keyword']))
{
 $feed->error = 'Missing keyword parameters';
 $hasValidUser = false;
}
if (!$hasValidUser)
{ 
 echo json_encode($feed);
 die();
}
//$_POST['numbers'] = '1';
$numbers = filter_var($_POST['numbers'], FILTER_SANITIZE_SPECIAL_CHARS);

   //$_POST['keyword'] = 'forex';
   $keyword= filter_var($_POST['keyword'], FILTER_SANITIZE_SPECIAL_CHARS); 
    //debug(">>>>>>>>>>>>>>>>>>>> SEND KEYWORD >>>>>>>>>>>",$keyword);
	$_POST['keywords'] = '';
	$_POST['urllink'] = '';
    $keywords=filter_var($_POST['keywords'], FILTER_SANITIZE_SPECIAL_CHARS);// $_POST['keywords'];
    $urllink =filter_var($_POST['urllink'], FILTER_SANITIZE_SPECIAL_CHARS);// $_POST['urllink'];
	if ($_POST['feedsource'] == 'user_urls')
        {
			if (!isset($_POST['custom_urls']))
            { 
                $feed->error = 'Missing custom_urls parameters';
				echo json_encode($feed);
				die();
			}
             $fields = array ('keyword' => (urlencode($_POST['custom_urls'])));
            $rss = '';
            include 'custom_urls.php';
          //  debug(">>>>>>>>>>>>>>> ONLY SPIN AFTER SPIN PHP  >>>>>>>>>>>>>>>>>>>>>",$rss);
            $feed = createFeed($rss);
           
        }else{
            if ($_POST['feedsource'] == 'yahooanswers'){
                 // $urlsource = $baseurl ."/yahooanswers.php";//?
                 $fields = array ('keyword' => (($keyword)),
                                  'url' =>  ('http://answers.yahoo.com/search/search_result?p='),
                                   'end' => ('&submit-go=Search+Y!+Answers') );
            }else if ($_POST['feedsource'] == 'bing') {
                //$urlsource = $baseurl ."/bingnews.php";//?
                $fields = array ('keyword' => (($keyword)),
                                  'url' =>  ('http://www.bing.com/news/search?q='),
                                   'end' => ('&format=RSS') );
            }else if ($_POST['feedsource'] == 'google') {
                 $fields = array ('keyword' => (($keyword)),
                                  'url' =>  ('http://news.google.com/news?q='),
                                   'end' => ('&output=rss') );
             }else if ($_POST['feedsource'] == 'yahoo') {
                $fields = array ('keyword' => (($keyword)),
                                  'url' =>  ('https://news.yahoo.com/rss/?p='),
                                   'end' => ('') );
                
            }
             $rss = '';
             @include 'rssnews.php';
             $feed = @createFeed($rss);
        }
		
	    $count = 0;
       if (!isset($feed) or !isset($feed->channel) or !isset($feed->channel->item))
           return;
        echo json_encode($feed);
	    die();
      
   
	?>
  