<?php
    echo ">>>>>>>>>>>>>>>>>>>>>>>>> POST REQUEST >>>>>>>>>>>>>>>>>>><br>"; 
    echo str_replace('  ', '&nbsp; ', nl2br(print_r($_POST, true)));
	echo "<br> >>>>>>>>>>>>>>>>>>>>>>> END POST >>>>>>>>>>>>>>>>>>>>>>>>>>><br>";
	$keyword= filter_var($_POST['keyword'], FILTER_SANITIZE_SPECIAL_CHARS); 
    //debug(">>>>>>>>>>>>>>>>>>>> SEND KEYWORD >>>>>>>>>>>",$keyword);
    $keywords=filter_var($_POST['keywords'], FILTER_SANITIZE_SPECIAL_CHARS);// $_POST['keywords'];
    $urllink =filter_var($_POST['urllink'], FILTER_SANITIZE_SPECIAL_CHARS);// $_POST['urllink'];
?>