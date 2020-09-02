<?php
/**
 * this file must be used inside custom_url.php or rssnews.php
 */
  //debug(">>>>>>>>>>>>>>>>>> RESPONCE FOR [" .$permalink ."]>>>>>>>>>>>>>>>",$response);
    $effective_url = $response['effective_url'];
    $do_content_extraction = true;
    //if (!url_allowed($effective_url)) continue;
    // check if action defined for returned Content-Type
    $mime_info = get_mime_action_info($response['headers']);
    if (isset($mime_info['action'])) {
        if ($mime_info['action'] == 'exclude') {
            return;
            //continue; // skip this feed item entry
        } elseif ($mime_info['action'] == 'link') {
            if ($mime_info['type'] == 'image') {
                $html = "<a href=\"$effective_url\"><img src=\"$effective_url\" alt=\"{$mime_info['name']}\" /></a>";
            } else {
                $html = "<a href=\"$effective_url\">Download {$mime_info['name']}</a>";
            }
            $title = $mime_info['name'];
            $do_content_extraction = false;
        }
    }
    if ($do_content_extraction) {
        $html = $response['body'];
        // remove strange things
        $html = str_replace('</[>', '', $html);
       // debug(">>>>> BEFORE convert_to_utf8 (BODY)>>> ",$html);
       // debug(">>>>> BEFORE convert_to_utf8 (HEADER)>>> ",$response['headers']);
        $html = convert_to_utf8($html, $response['headers']);
        // check site config for single page URL - fetch it if found
        $is_single_page = false;
        if ($single_page_response = getSinglePage($item, $html, $effective_url)) {
            $is_single_page = true;
            $html = $single_page_response['body'];
            // remove strange things
            $html = str_replace('</[>', '', $html);	
            $html = convert_to_utf8($html, $single_page_response['headers']);
            $effective_url = $single_page_response['effective_url'];
            debug("Retrieved single-page view from $effective_url");
            unset($single_page_response);
        }
        debug('--------');
        debug('Attempting to extract content');
        $extract_result = $extractor->process($html, $effective_url);
        $readability = $extractor->readability;
        $content_block = ($extract_result) ? $extractor->getContent() : null;			
        $title = ($extract_result) ? $extractor->getTitle() : '';
        debug('Attempting to extract title ---------------- ',$title);
       // debug('Attempting to extract content ---------------- ',$content_block);
        // Deal with multi-page articles
        //die('Next: '.$extractor->getNextPageUrl());
        $is_multi_page = (!$is_single_page && $extract_result && $extractor->getNextPageUrl());
        if ($options->multipage && $is_multi_page) {
            debug('--------');
            debug('Attempting to process multi-page article');
            $multi_page_urls = array();
            $multi_page_content = array();
            while ($next_page_url = $extractor->getNextPageUrl()) {
                debug('--------');
                debug('Processing next page: '.$next_page_url);
                // If we've got URL, resolve against $url
                if ($next_page_url = makeAbsoluteStr($effective_url, $next_page_url)) {
                    // check it's not what we have already!
                    if (!in_array($next_page_url, $multi_page_urls)) {
                        // it's not, so let's attempt to fetch it
                        $multi_page_urls[] = $next_page_url;						
                        $_prev_ref = $http->referer;
                        $response = Requests::get($next_page_url);
                        if (($response) && $response->status_code < 300) {
                            // make sure mime type is not something with a different action associated
                            $response['headers'] = 'Content-Type:'.$response->headers['Content-Type'].'\n';
                            $response['body'] = $response->body;
                            $page_mime_info = get_mime_action_info($response['headers']);
                            if (!isset($page_mime_info['action'])) {
                                $html = $response['body'];
                                // remove strange things
                                $html = str_replace('</[>', '', $html);
                                $html = convert_to_utf8($html, $response['headers']);
                                if ($extractor->process($html, $next_page_url)) {
                                    $multi_page_content[] = $extractor->getContent();
                                    continue;
                                } else { debug('Failed to extract content'); }
                            } else { debug('MIME type requires different action'); }
                        } else { debug('Failed to fetch URL'); }
                    } else { debug('URL already processed'); }
                } else { debug('Failed to resolve against '.$effective_url); }
                // failed to process next_page_url, so cancel further requests
                $multi_page_content = array();
                break;
            }
            // did we successfully deal with this multi-page article?
            if (empty($multi_page_content)) {
                debug('Failed to extract all parts of multi-page article, so not going to include them');
                $multi_page_content[] = $readability->dom->createElement('p')->innerHTML = '<em>This article appears to continue on subsequent pages which we could not extract</em>';
            }
            foreach ($multi_page_content as $_page) {
                $_page = $content_block->ownerDocument->importNode($_page, true);
                $content_block->appendChild($_page);
            }
            unset($multi_page_urls, $multi_page_content, $page_mime_info, $next_page_url);
        }
    }
    // use extracted title for both feed and item title if we're using single-item dummy feed
    if ($isDummyFeed) {
        $output->setTitle($title);
        $newitem->setTitle($title);
    }
    if ($do_content_extraction) {
		// if we failed to extract content...
		if (!$extract_result) {
			if ($exclude_on_fail) {
				debug('Failed to extract, so skipping (due to exclude on fail parameter)');
				//continue; // skip this and move to next item
                return;
            }
			//TODO: get text sample for language detection
			$html = $options->error_message;
			// keep the original item description
			$html .= $item->get_description();
		} else {
			$readability->clean($content_block, 'select');
			if ($options->rewrite_relative_urls) makeAbsolute($effective_url, $content_block);
			// footnotes
			if (($links == 'footnotes') && (strpos($effective_url, 'wikipedia.org') === false)) {
				$readability->addFootnotes($content_block);
			}
			// remove nesting: <div><div><div><p>test</p></div></div></div> = <p>test</p>
			while ($content_block->childNodes->length == 1 && $content_block->firstChild->nodeType === XML_ELEMENT_NODE) {
				// only follow these tag names
				if (!in_array(strtolower($content_block->tagName), array('div', 'article', 'section', 'header', 'footer'))) break;
				//$html = $content_block->firstChild->innerHTML; // FTR 2.9.5
				$content_block = $content_block->firstChild;
			}
			// convert content block to HTML string
			// Need to preserve things like body: //img[@id='feature']
			if (in_array(strtolower($content_block->tagName), array('div', 'article', 'section', 'header', 'footer'))) {
				$html = $content_block->innerHTML;
			} else {
				$html = $content_block->ownerDocument->saveXML($content_block); // essentially outerHTML
			}
			unset($content_block);
			// post-processing cleanup
			$html = preg_replace('!<p>[\s\h\v]*</p>!u', '', $html);
			if ($links == 'remove') {
				$html = preg_replace('!</?a[^>]*>!', '', $html);
			}
			// get text sample for language detection
			$text_sample = strip_tags(substr($html, 0, 500));
			$html = make_substitutions($options->message_to_prepend).$html;
			$html .= make_substitutions($options->message_to_append);
		}
	}

		if ($valid_key && isset($_GET['pubsub'])) { // used only on fivefilters.org at the moment
			$newitem->addElement('guid', 'http://fivefilters.org/content-only/redirect.php?url='.urlencode($item->get_permalink()), array('isPermaLink'=>'false'));
		} else {
			$newitem->addElement('guid', $item->get_permalink(), array('isPermaLink'=>'true'));
		}
		// filter xss?
		if ($xss_filter) {
			debug('Filtering HTML to remove XSS');
			$html = htmLawed::hl($html, array('safe'=>1, 'deny_attribute'=>'style', 'comment'=>1, 'cdata'=>1));
		}
		$newitem->setDescription($html);
		
		// set date
		if ((int)$item->get_date('U') > 0) {
			$newitem->setDate((int)$item->get_date('U'));
		} elseif ($extractor->getDate()) {
			$newitem->setDate($extractor->getDate());
		}
		
		// add authors
		if ($authors = $item->get_authors()) {
			foreach ($authors as $author) {
				$newitem->addElement('dc:creator', $author->get_name());
			}
		} elseif ($authors = $extractor->getAuthors()) {
			//TODO: make sure the list size is reasonable
			foreach ($authors as $author) {
				//TODO: addElement replaces this element each time.
				// xpath often selects authors from other articles linked from the page.
				// for now choose first item
				$newitem->addElement('dc:creator', $author);
				break;
			}
		}
		
		// add language
		if ($detect_language) {
			$language = $extractor->getLanguage();
			if (!$language) $language = $feed->get_language();
			if (($detect_language == 3 || (!$language && $detect_language == 2)) && $text_sample) {
				try {
					if ($use_cld) {
						// Use PHP-CLD extension
						$php_cld = 'CLD\detect'; // in quotes to prevent PHP 5.2 parse error
						$res = $php_cld($text_sample);
						if (is_array($res) && count($res) > 0) {
							$language = $res[0]['code'];
						}	
					} else {
						//die('what');
						// Use PEAR's Text_LanguageDetect
						if (!isset($l))	{
							$l = new Text_LanguageDetect('libraries/language-detect/lang.dat', 'libraries/language-detect/unicode_blocks.dat');
						}
						$l_result = $l->detect($text_sample, 1);
						if (count($l_result) > 0) {
							$language = $language_codes[key($l_result)];
						}
					}
				} catch (Exception $e) {
					//die('error: '.$e);	
					// do nothing
				}
			}
			if ($language && (strlen($language) < 7)) {	
				$newitem->addElement('dc:language', $language);
			}
		}
		
		// add MIME type (if it appeared in our exclusions lists)
		if (isset($mime_info['mime'])) $newitem->addElement('dc:format', $mime_info['mime']);
		// add effective URL (URL after redirects)
		if (isset($effective_url)) {
			//TODO: ensure $effective_url is valid witout - sometimes it causes problems, e.g.
			//http://www.siasat.pk/forum/showthread.php?108883-Pakistan-Chowk-by-Rana-Mubashir-–-25th-March-2012-Special-Program-from-Liari-(Karachi)
			//temporary measure: use utf8_encode()
			$newitem->addElement('dc:identifier', remove_url_cruft(utf8_encode($effective_url)));
		} else {
			$newitem->addElement('dc:identifier', remove_url_cruft($item->get_permalink()));
		}
		// check for enclosures
		if ($options->keep_enclosures) {
			if ($enclosures = $item->get_enclosures()) {
				foreach ($enclosures as $enclosure) {
					if (!$enclosure->get_link()) continue;
					$enc = array();
					// Media RSS spec ($enc): http://search.yahoo.com/mrss
					// SimplePie methods ($enclosure): http://simplepie.org/wiki/reference/start#methods4
					$enc['url'] = $enclosure->get_link();
					if ($enclosure->get_length()) $enc['fileSize'] = $enclosure->get_length();
					if ($enclosure->get_type()) $enc['type'] = $enclosure->get_type();
					if ($enclosure->get_medium()) $enc['medium'] = $enclosure->get_medium();
					if ($enclosure->get_expression()) $enc['expression'] = $enclosure->get_expression();
					if ($enclosure->get_bitrate()) $enc['bitrate'] = $enclosure->get_bitrate();
					if ($enclosure->get_framerate()) $enc['framerate'] = $enclosure->get_framerate();
					if ($enclosure->get_sampling_rate()) $enc['samplingrate'] = $enclosure->get_sampling_rate();
					if ($enclosure->get_channels()) $enc['channels'] = $enclosure->get_channels();
					if ($enclosure->get_duration()) $enc['duration'] = $enclosure->get_duration();
					if ($enclosure->get_height()) $enc['height'] = $enclosure->get_height();
					if ($enclosure->get_width()) $enc['width'] = $enclosure->get_width();
					if ($enclosure->get_language()) $enc['lang'] = $enclosure->get_language();
					$newitem->addElement('media:content', '', $enc);
				}
			}
		}
	/* } */
	$output->addItem($newitem);
	unset($html);
	//$item_count++;
 
?>