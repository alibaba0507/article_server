<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once(dirname(__FILE__).'/utils/utils.php'); // for debug call  debug($msg,$obj)
//require_once ('utils.php');
// use this for debuging only 
//ini_set('display_errors', 'On');
//error_reporting(E_ALL);



//*********************** Open Syntax Dict Files *************************//
$myIndxFile = "th_en_US_new.idx";
$lines = file($myIndxFile);//file in to an array
$fdat = fopen('th_en_US_new.dat', 'r');
$tmp_dic_arr = array(); // buffer array for used words
//************************* Cleaning **********************************//
// remove all links
$source = preg_replace("/<a[^>]+>/i", "", $source);
// replace with selected keywords
//include 'unik.php';
$search = $keyword;//$GLOBALS['keyword']; // search fields are also keywords
$search_array  = explode("+",$search);
foreach ($search_array as $val)
{
    $search .= "|".$val;
}
$search = implode(" ",explode("+",$search));

//$keywords = /*$GLOBALS['keywords']*/."|".$search;
if (strlen(trim($search)) > 0 && !strpos($keywords, "|".$search))
    $keywords .= "|".$search;

//$urllink = $GLOBALS['urllink'];
//$urlinternal = $_GET['urlinternal'];
$arr_keyword = explode("|",$keywords);
// sort keywords array longest to shortest 
 usort($arr_keyword, function($a, $b) {
		return strlen($b) - strlen($a);
});
//usort($arr_keyword,'sortByLength');
$keywords = implode("|", $arr_keyword);
$tagkeywords = implode(",",explode("|",$keywords));

$article=$source;
$rawarticle = $source;
include 'letter_index.php';

$artarray=$article;

// strip html tags
$words_count = strip_tags($artarray);
$step1 = array("(", ")", "[", "]", "?", ".", ",", "|", "\$", "*", "+", "^","{", "}");
$artarray=str_replace($step1," ",$words_count);
$artarray=str_replace("  "," ",$words_count);
$words_artarray = explode(" ",$words_count);

$time = microtime(true); // time in Microseconds
//error_log( "Time Start .... ");
//debug(">>>>>>>>>>>>>>>>>>>>>>>> UNIKE 1 >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>");
if (sizeof($words_artarray)>0)
{
	 for($i=0;$i<sizeof($words_artarray);$i++)
	{
	  
	    
	  $replace=$words_artarray[$i];
      
	  //error_log( "word process ... [".$replace."]");   
	  $replace=str_replace(" ","",$replace);
	  //if ($i == 44)
		// error_log( "word is [$replace]"); 
		if(($replace!="")&&(strlen(trim($replace))>=4))
		{
			 if (strpos($keywords,$replace) !== false)
			{
				$isLongTailFound = false;
				for ($k = $i + 1;$k < ($i + 6);$k++)
				{
					$replace .= " ".str_replace(" ","",$words_artarray[$k]);
					if (strpos($keywords,$replace) === false)
					{ // long tail
				        $isLongTailFound = true;
						if ($k - 1 != $i)
							$i = $k-1;
						break;
					}
				}
				
				continue; // go next do not replace this word	  
			}		  
			$replace= trim(rtrim($replace));
			$replace= trim(rtrim($replace,'\n'),'\n');
			$replace= trim(rtrim($replace,'\r'),'\r');
			$replacewith = "";
			//error_log( "word process ... [".$replace."][".(sizeof($tmp_dic_arr))."]");  
				
			 if (array_key_exists($replace, $tmp_dic_arr)) 
		    {   
		       // if ($i == 44)
				//	error_log( "we found cached array $tmp_dic_arr[$replace]"); 
		        // we found word in our cache
		        $replacewith = "";
				$syn = $tmp_dic_arr[$replace];
				$isKeyWord = false;
				//$arr_keyword = explode("|",$keywords);
				foreach ($arr_keyword as $value)
				{
					if (strpos(trim($value)," ") === false)
					{
					 if (strpos("|".$syn,"|".trim($value)."|") !== false)
					 {
						$replacewith = " ".trim($value)." ";
						$isKeyWord = true;
					    break; // keyword found
					 }
					}
						
				}
				if (strlen($replacewith) == 0)
				{
					$syn_arr = explode("|",$syn);
					$rnd = rand(1, sizeof($syn_arr) - 1);
					while (strlen(trim($syn_arr[$rnd])) == 0 || strpos($syn_arr[$rnd],'(') !== false)
						$rnd = rand(1, sizeof($syn_arr) - 1);
					$replacewith = " ".$syn_arr[$rnd]." ";
				}
				//if ($i < 20)
				//  echo "We found "
				if(($replace!="")&&($replace!=" ")&&($replacewith!=""))
				{
					//$replace=" ".$replace." ";
					$article = str_replace(" ".$replace." "," ".$replacewith." ",$article);
					$article = str_replace(",".$replace." ",",".$replacewith." ",$article);
					$article = str_replace(" ".$replace.","," ".$replacewith.",",$article);
					$article = str_replace(".".$replace." ",".".$replacewith." ",$article);
					$article = str_replace(" ".$replace."."," ".$replacewith.".",$article);
					$article = str_replace(" ".ucfirst(trim($replace))." "," ".ucfirst(trim($replacewith))." ",$article);
					
					if ($isKeyWord == false)
					{
						$replacewith = "";
						$rnd = rand(1, sizeof($syn_arr) - 1);
						$cnt = 0;
					
						if (sizeof($syn_arr) - 1 < 3)
						{
							for ($k = 0;$k < sizeof($syn_arr);$k++)
							{
								if (strpos($replacewith,$syn_arr[$k]) === false && strpos($syn_arr[$k], "(") === false)
								 $replacewith .= (strlen($replacewith) == 0) ? $syn_arr[$k] : "|".$syn_arr[$k];
							}
						}else
						{
							$loopCnt = 0;
							while ($cnt<3 )
							{
							  if (strpos($replacewith,$syn_arr[$rnd]) === false && strpos($syn_arr[$rnd], "(") === false)
							  {
								$cnt++;
								$replacewith .= (strlen($replacewith) == 0) ? $syn_arr[$rnd] : "|".$syn_arr[$rnd];  						
							  }				  
							  $rnd = rand(1, sizeof($syn_arr) - 1);		
							  if ($loopCnt >= sizeof($syn_arr) ) break;		
							  $loopCnt++;						  
							} // end while
						}// end if
						
						$replacewith = "{".$replacewith."}"; // this is all synonyms 
					}else
                    { // it is a keyword we must replace with links if any
                       /* if (strlen( trim($urllink)) > 0)
                        {
                          $replacewith = '<a href="'.$urllink.'">'.$replace.'</a>';
                            
                        }else{
                          $replacewith =  " <strong>".$replace."</strong> ";
                           
                        }
                        */
                       // $replacewith =  "<strong>".$replace."</strong>";
                    }
						//error_log( "word Replace ... [".$replace."][".$replacewith."]");
						//$replacewith = ("{".$replacewith."}"); // this is all synonyms 
                       // debug(" >>>>>>>>>>>>>>>>>>>>> TO BE REPLACED [" .$repace."]<<<<<<<<<<<<<<<<");
						$rawarticle = str_replace(" ".$replace." "," ".$replacewith." ",$rawarticle);
						$rawarticle = str_replace(",".$replace." ",",".$replacewith." ",$rawarticle);
						$rawarticle = str_replace(" ".$replace.","," ".$replacewith.",",$rawarticle);
						$rawarticle = str_replace(".".$replace." ",".".$replacewith." ",$rawarticle);
						$rawarticle = str_replace(" ".$replace."."," ".$replacewith.".",$rawarticle);
					
					/*if (isset($replaceWith) && $replaceWith != "" && strlen(trim($replaceWith)) > 0)
					{
						$rawarticle = inserthml($rawarticle,$replace,$replaceWith);
						$rawarticle = str_replace(" ".ucfirst(trim($replace))." "," ".ucfirst(trim($replacewith))." ",$rawarticle);
					}*/
				} // end if(($replace!="")&&($replace!=" ")&&($replacewith!=""))  
			}else if (isset($letters))
			{
                //debug(">>>>>>>>>>>>>>>> LETTERS >>>>>>>>>>>>",$letters);
				$searchIndex = strtoupper (substr($replace,0,1));	
				$leter_index = "";
                if (!preg_match('/[^A-Za-z]/', $searchIndex)) // '/[^a-z\d]/i' should also work.				
					$leter_index = $letters[$searchIndex];
				//if ($i <= 44)
				//	error_log( "letter Index is [$leter_index][$searchIndex]"); 
				if ($leter_index != "" && strlen(trim($leter_index)) > 2)
				{ // we found our index
				  $range = explode("|",$leter_index);
				  $start = $range[0];
				  $end = $range[1];
				  for ($j = $start;$j < $end;$j++)
			      {
					  $buffer = "";
					 $pos = strpos($lines[$j], strtolower($replace)."|");
				
					  //$line_str = substr(strtolower($lines[$j]),0,strlen(strtolower($replace)."|"))
					 // The !== operator can also be used.  Using != would not work as expected
					// because the position of 'a' is 0. The statement (0 != false) evaluates 
					// to false.
					$searchFor = strtolower($replace)."|";
					//if ($pos !== false)				    
					if ( startsWith($lines[$j],$searchFor) )
					{ // we found our word
					   $line_arr = explode("|",$lines[$j]);
					   fseek($fdat, intval($line_arr[1])); // we seek the positon in the big file
					   $buffer = fgets($fdat, 4096); // not so important for only to get the word
					   //if ($i < 20)
						//	echo "We found repace pos = [".$pos."][".$buffer."] at [".$line_arr[1]."] <br/>";
					   break;
					}// end if
				  }// end for($j) 
			   // if ($i <= 44)
				//	error_log( "After the loop [$buffer]"); 
				  if (strlen($buffer) > 0)
				  {
					$replacewith = "";  
				    $syn = "";  
					while (substr(trim($buffer = fgets($fdat, 4096)),0,1) == '(')
					{ 
					   if (strlen($syn) == 0)
							$syn = $buffer;
						else $syn .= "|".$buffer;
						//$buffer = fgets($fp, 4096);
					}//end while
					$tmp_dic_arr[$replace]	= $syn; // save to cache
					$isKeyWord = false;
					//$arr_keyword = explode("|",$keywords);
					$isDebug = false;
					//if (strpos($syn,'correspondence') !== false)
					//{
					//	$isDebug = true;
					//	error_log("******* we found our word worldwide [$syn][$keywords]");
					//}else 
                    //    $isDebug = false;						
					foreach ($arr_keyword as $value)
					{
						if (strpos(trim($value)," ") === false)
						{
						 //if ($isDebug && trim($value) == 'correspondence')
						//	error_log("******* loop dor the word ................ ");  
						 if (strpos("|".$syn,"|".trim($value)."|") !== false)
						 {
							
							$replacewith = " ".trim($value)." ";
						//	if ($isDebug && trim($value) == 'correspondence')
						//		error_log("******* loop dor the word .......[$replacewith]......... ");  
							$isKeyWord = true;
							break; // keyword found
						 }
						}
							
					}
					if (strlen($replacewith) == 0)
					{					
						$syn_arr = explode("|",$syn);
						//if ($i <= 44)
						// error_log( "Synonims [$syn][".sizeof($syn_arr)."]"); 
						$rnd = rand(1, sizeof($syn_arr) - 1);
						while (strlen(trim($syn_arr[$rnd])) == 0 || strpos($syn_arr[$rnd],'(') !== false)
						{
							$rnd = rand(1, sizeof($syn_arr) - 1);
						}
						//if ($i <= 44)
						// error_log( "Replace with $syn_arr[$rnd]"); 
						$replacewith = " ".$syn_arr[$rnd]." ";
					}
					if(($replace!="")&&($replace!=" ")&&($replacewith!=""))
					{
						//$replace=" ".$replace." ";
						//$article = str_replace($replace,$replacewith,$article);
						$article = str_replace(" ".$replace." "," ".$replacewith." ",$article);
						$article = str_replace(",".$replace." ",",".$replacewith." ",$article);
						$article = str_replace(" ".$replace.","," ".$replacewith.",",$article);
						$article = str_replace(".".$replace." ",".".$replacewith." ",$article);
						$article = str_replace(" ".$replace."."," ".$replacewith.".",$article);
						$article = str_replace(" ".ucfirst(trim($replace))." "," ".ucfirst(trim($replacewith))." ",$article);
						
						// replace synonims but no more then 3
						if ($isKeyWord == false)
						{
							$replacewith = "";
							//if ($i <= 150)
						   //   error_log( "Before loop ...$replace [$syn]"); 
							$rnd = rand(1, sizeof($syn_arr) - 1);
							$cnt = 0;
							if (sizeof($syn_arr) - 1 < 3)
							{
								for ($k = 0;$k < sizeof($syn_arr);$k++)
								{
									if (strpos($replacewith,$syn_arr[$k]) === false && strpos($syn_arr[$k], "(") === false)
										$replacewith .= (strlen($replacewith) == 0) ? $syn_arr[$k] : "|".$syn_arr[$k];
								}
							}else
							{
								$loopCnt = 0;
								while ($cnt<3)
								{
								  if (strpos($replacewith,$syn_arr[$rnd]) === false && strpos($syn_arr[$rnd], "(") === false)
								  {
									$cnt++;
									$replacewith .= (strlen($replacewith) == 0) ? $syn_arr[$rnd] : "|".$syn_arr[$rnd];  						
								  }				  
								  $rnd = rand(1, sizeof($syn_arr) - 1);	
								 // if ($i == 18 && $loopCnt < 20)
									//error_log("Loop [$replacewith][$rnd][".(sizeof($syn_arr) - 1)."] cnt[$cnt][$loopCnt]");
								if ($loopCnt >= sizeof($syn_arr) ) break;
								 $loopCnt++;
								} // end while
							}// end if
							//if ($i <= 150)
							 // error_log( "After Before loop ...$replace"); 
							$replacewith = " {".$replacewith."} "; // this is all synonyms 
						}else
                        {
                             if (strlen( trim($urllink)) > 0)
                                {
                                  $replacewith = '<a href="'.$urllink.'">'.$replace.'</a>';
                                    
                                }else{
                                  $replacewith =  "<strong>".$replace."</strong>";
                                   
                                }
                              //  debug(">>>>>>>> REPLACE [" .$replace . "] >>>> WITH [".$replacewith ." <<<<<<<<<");
                        }
						//htmlspecialchars("<font color=\"#008000\">{".$replacewith."}</font>");
						//$replacewith = htmlspecialchars(" <font color=\"red\"><b>{".$replacewith."}</b> </font>"); // this is all synonyms 
						//$rawarticle = str_replace($replace,$replacewith,$rawarticle);
                        
						$rawarticle = str_replace(" ".$replace." "," ".$replacewith." ",$rawarticle);
						$rawarticle = str_replace(",".$replace." ",",".$replacewith." ",$rawarticle);
						$rawarticle = str_replace(" ".$replace.","," ".$replacewith.",",$rawarticle);
						$rawarticle = str_replace(".".$replace." ",".".$replacewith." ",$rawarticle);
						$rawarticle = str_replace(" ".$replace."."," ".$replacewith.".",$rawarticle);
						
						//if (isset($replaceWith) && $replaceWith != "" && strlen(trim($replaceWith)) > 0)
						//{
						//	error_log("Replace with ".$replaceWith);
						//	$rawarticle = inserthml($rawarticle,$replace,$replaceWith);
						//	$rawarticle = str_replace(" ".ucfirst(trim($replace))." "," ".ucfirst(trim($replacewith))." ",$rawarticle);
					    //}
					} // end if(($replace!="")&&($replace!=" ")&&($replacewith!=""))  
				  }// end if (strlen($buffer) > 0)
					
				} // end if ($leter_index != "" && strlen(trim($leter_index)) > 2)
			}// end if (isset($letters)) 
		} // end if(($replace!="")&&(strlen(trim($replace))>=4))
	}// end for($i)
}// end if (sizeof($words_artarray)>0)
// Close Syndax Dict File 
fclose($fdat);
//debug(">>>>>>>>>>>>>>>>>>>>>>>> UNIKE 2 >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>");
// Now we must check for keywords and replace with urldecode

for ($i=0;$i < sizeof($arr_keyword);$i++)
{
    $replace = $arr_keyword[$i];
    if ($i == sizeof($arr_keyword) - 1)
    { // will pl
        $pos = strrpos(strtolower($article), strtolower(" ".$replace." "));
        if($pos !== false)
        {
            //error_log("//**************** str_lastreplace [$search] with[$replace]");
            $article = substr_replace($article,  ' <a href="https://en.wikipedia.org/w/index.php?search='.$replace.'&title=Special%3ASearch&go=Go" target="_blank">'.$replace.'</a> ', $pos, strlen(" ".$replace." "));
        }else if (strrpos(strtolower($article), strtolower(",".$replace." ")) !== false)
        {
             $pos = strrpos(strtolower($article), strtolower(",".$replace." "));
              $article = substr_replace($article,  ',<a href="https://en.wikipedia.org/w/index.php?search='.$replace.'&title=Special%3ASearch&go=Go" target="_blank">'.$replace.'</a> ', $pos, strlen(" ".$replace." "));
        }else if (strrpos(strtolower($article), strtolower(" ".$replace.",")) !== false)
        {
             $pos = strrpos(strtolower($article), strtolower(" ".$replace.","));
              $article = substr_replace($article,  ' <a href="https://en.wikipedia.org/w/index.php?search='.$replace.'&title=Special%3ASearch&go=Go" target="_blank">'.$replace.'</a>,', $pos, strlen(" ".$replace." "));
        }
        
        break;
    }
    if (strlen( trim($urllink)) > 0 && strlen(trim($replace)) > 0)
    {
      $replacewith = '<a href="'.$urllink.'">'.$replace.'</a>';
        
    }else if (strlen(trim($replace)) > 0){
      $replacewith =  "<strong>".$replace."</strong> ";
       
    }
    if (strlen(trim($replace)) > 0 && strlen(trim($replacewith)) > 0)
    {
        $article = str_replace(" ".$replace." "," ".$replacewith." ",$article);
        $article = str_replace(",".$replace." ",",".$replacewith." ",$article);
        $article = str_replace(" ".$replace.","," ".$replacewith.",",$article);
        $article = str_replace(".".$replace." ",".".$replacewith." ",$article);
        $article = str_replace(" ".$replace."."," ".$replacewith.".",$article);
    }
}
$article=str_replace("\'","'",$article);
$article=str_replace('\"','"',$article);
$article=str_replace("\n\r","</p><p>",$article);
$article=str_replace("\r\n","</p><p>",$article);

$rawarticle=str_replace("\'","'",$rawarticle);
$rawarticle=str_replace('\"','"',$rawarticle);
$rawarticle=str_replace("\n\r","</p><p>",$rawarticle);
$rawarticle=str_replace("\r\n","</p><p>",$rawarticle);

$time_diff = number_format (((microtime(true) - $time)/1000),5);
//debug(">>>>>>>>>>>>>>>>>>>>>>>> UNIKE (END) >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>");
//error_log("DONE ...... $time_diff in sec ");
/*if ($_POST["article"])
{
     echo $article;
}*/

?>