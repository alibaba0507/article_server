<?php namespace Buchin\GoogleSuggest;

/**
*
*/
class GoogleSuggest
{
	public static function grab($keyword = '', $lang = '', $country = '', $source = '', $proxy = '')
	{
        $url = 'http://clients1.google.com/complete/search?';
		//$url = 'http://suggestqueries.google.com/complete/search?';
        //$url = 'http://www.google.com/complete/search?';
		$out = [];

        $query = [
            'output' => 'toolbar',
            'q' => $keyword,
			'json' => 't',
			'ds' => 'pr',
			'client' => 'serp',
			/*'client' => 'news',*/
        ];

        if(!empty($lang)){
            $query['hl'] = $lang;
        }

        if(!empty($country)){
            $query['gl'] = $country;
        }

        if(!empty($source)){
            $query['ds'] = $source;
        }

        $url .= http_build_query($query);
        if(!empty($proxy)) $proxy = "tcp://$proxy";
        $aContext = array(
            'http' => array(
                'proxy'           => "$proxy",
                'request_fulluri' => true,
            ),
        );

        $cxContext = stream_context_create($aContext);
		if($content = trim(file_get_contents($url, false, $cxContext)));
        {
			$myJSON = json_decode(utf8_encode($content));
            $out = $myJSON;
			//echo $myJSON;
			//var_dump($content);
           /* $xml = simplexml_load_string(utf8_encode($content));

            foreach($xml->CompleteSuggestion as $sugg)
                $out[] = (string)$sugg->suggestion['data'];
				*/
        }

        return $out;
	}
}
