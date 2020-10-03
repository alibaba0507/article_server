<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once ('vendor/autoload.php');
use Buchin\GoogleSuggest\GoogleSuggest;

$kw = 'forex';
$d = 1;
if (isset($_GET['kw'])) // multiple words separate by ','
	$kw = $_GET['kw'];
if (isset($_POST['kw']))
	$kw = $_POST['kw'];
if (isset($_GET['d']))// depth
	$d = $_GET['d'];
if (isset($_POST['d']))
	$d = $_POST['d'];
$output = [];	
$kw_arr = explode(',',$kw);
//$merge = array_merge($a, $b);
for ($i = 0;$i < count($kw_arr);$i++)
{
	 $output =array_merge($output, printKwPerSearch($kw_arr[$i],$d));
    $output = array_filter(array_unique($output),function($value) { return isset($value[1]) && !is_null($value) && $value !== ''; });
	//var_dump ($output);
}
$result = [];
//echo '<br>----------------------------- BEFORE LOOP --------------------------------------------------------<br>';
	for ($j = 0;$j < count($output);$j++)
	{
		if (isset($output[$j]) && trim($output[$j]," ") !== ' ')
		{
			//var_dump (after ('String', $output[$i]));
			$result = array_merge($result,[$output[$j]]);
			//echo $output[$j];
	//	echo '<br>----------------------------------------------------------------------------------------<br>';
		}
		
	}// end for
	echo json_encode($result);
	
	

function printKwPerSearch($kw,$d)
{
	$kw_arr = [$kw];
	$output = [];
	for ($i = 0;$i < $d;$i++)
	{
		$tmp_arr = [];
		for ($j = 0;$j < count($kw_arr);$j++)
		{
			$tmp_arr = array_merge($tmp_arr, printKw($kw_arr[$j]));
		}
		$output = array_merge($output,$tmp_arr);
		$kw_arr = $tmp_arr;
		// printKw($kw)
	}
	return $output;
}

	
/**
 * 
 */
function printKw($kw)
{
	$suggested = GoogleSuggest::grab($kw);
	//var_dump ($suggested[1]);
/*	echo '<br>----------------------------- BEFORE LOOP --------------------------------------------------------<br>';
	foreach($suggested[1] as $item)
	{
		var_dump ($item);
		echo '<br>----------------------------------------------------------------------------------------<br>';
	}
	echo '<br>----------------------------- BEFORE LOOP --------------------------------------------------------<br>';
	*/
	return $suggested[1];
}
//var_dump($suggested);
?>