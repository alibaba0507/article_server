<?php
require 'src/AbstractStopwordProvider.php';
require 'src/StopwordArray.php';
require 'src/StopwordsPatternFile.php';
require 'src/StopwordsPHP.php';
require 'src/ILangParseOptions.php';
require 'src/LangParseOptions.php';
require 'src/RakePlus.php';

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
$rake = RakePlus::create($text_rake);
$keywords = $rake->keywords();
//print_r($keywords);
//echo "===============================================================================================================<br>";
echo '<hr>';
$phrase_scores = $rake->sortByScore('desc')->scores();
//print_r($phrase_scores);

?>