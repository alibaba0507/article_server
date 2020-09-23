<?php
require_once ('src/AbstractStopwordProvider.php');
require_once ('src/StopwordArray.php');
require_once ('src/StopwordsPatternFile.php');
require_once ('src/StopwordsPHP.php');
require_once ('src/ILangParseOptions.php');
require_once ('src/LangParseOptions.php');
require_once ('src/RakePlus.php');

use DonatelloZa\RakePlus\RakePlus;
if (!isset($text_rake))
{
	echo "No Data<br>";
	die();
}
/*
$text = "Criteria of compatibility of a system of linear Diophantine equations, " .
    "strict inequations, and nonstrict inequations are considered. Upper bounds " .
    "for components of a minimal set of solutions and algorithms of construction " .
    "of minimal generating sets of solutions for all types of systems are given.";
*/
$text_rake = strip_tags($text_rake);
$rake = RakePlus::create($text_rake,'en_US', 10,false);
$keywords = $rake->keywords();
//print_r($keywords);
//echo "===============================================================================================================<br>";
//echo '<hr>';
$phrase_scores = $rake->sortByScore('desc')->scores();
//print_r($phrase_scores);

?>