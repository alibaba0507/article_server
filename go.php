<?php
  //  echo ">>>>>>>>>>>>>>>>>>>>>>>>> POST REQUEST >>>>>>>>>>>>>>>>>>><br>"; 
  //  echo str_replace('  ', '&nbsp; ', nl2br(print_r($_POST, true)));
//	echo "<br> >>>>>>>>>>>>>>>>>>>>>>> END POST >>>>>>>>>>>>>>>>>>>>>>>>>>><br>";
	$keyword= filter_var($_POST['keyword'], FILTER_SANITIZE_SPECIAL_CHARS); 
    //debug(">>>>>>>>>>>>>>>>>>>> SEND KEYWORD >>>>>>>>>>>",$keyword);
    $keywords=filter_var($_POST['keywords'], FILTER_SANITIZE_SPECIAL_CHARS);// $_POST['keywords'];
    $urllink =filter_var($_POST['urllink'], FILTER_SANITIZE_SPECIAL_CHARS);// $_POST['urllink'];
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
	 
	 $jsonstring = json_encode($feed);
     echo $jsonstring;

    die();
?>