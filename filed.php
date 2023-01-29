<?php // BISMILLAAHIRRAHMAANIRRAHEEM
ini_set("max_execution_time", 10);		// maximum script execution time in seconds
$depthlimit = 2;						// how many stages of subpages to traverse
$subpagelimit = 10;						// how many valid url subpages of a webpage to process
$site = "https://www.nobleprog.co.uk/"; // base url must end with a slash
$str = "training";						// keyword to find

$singleoffset = "  ";					// for console formatting
$pages = [];							// array to hold all relevant information for pages

function get_elements($inurl, $siteurl, $escurl, $protocol, $str, $stage = 0) { // processing function, called recursively
	global $pages, $depthlimit, $subpagelimit, $singleoffset;
	$knownexts = ["php", "asp", "aspx", "jsp", "html", "htm"]; // script extensions to guess the url as local at one stage of url verification
	// compute offset spaces for formatting
	$offset = "";
	for ($i = 0; $i < $stage; $i++)
	{
		$offset .= $singleoffset;
	}
	echo $offset . $inurl . "\n";
	// removes the http://www. part
	if (!preg_match("/^(?:(?:(?:https:\/\/)|(?:http:\/\/))(?:www\.)*)(.+)/i", $inurl, $match)) { // extract url without http etc
		// echo "\turl error\n";
		array_push( $pages, [ "url" => $inurl, "status" => false, "message" => "preg_match error", "count" => 0, "sub-pages" => 0, "sub-page-links" =>[] ] );
		return;
	}
	
	$site = $inurl;
	// removes the ?var0=val0&var1=val1 etc part
	$sitetosave = preg_match("/.[^?#]+/i", $match[1], $m) ? $m[0] : $match[1]; // extract url without query string - to save as unique
	if (in_array($sitetosave, array_column($pages, "url"))) {
		// echo "\trepeated url\n";
		return;
	}
	if (($depthlimit > 0) && ($stage >= $depthlimit)) { 		// depth check
		// echo "\texceeded depth limit\n";
		return;
	}
	
	$content = file_get_contents($site); 						// visit page, read html
	if (isset($http_response_header))
	{
		if (isset($http_response_header[0]))
		{
			if (str_contains($http_response_header[0], "404"))	// page not found?
			{
				array_push( $pages, [ "url" => $sitetosave, "status" => false, "message" => "404 error", "count" => 0, "sub-pages" => 0, "sub-page-links" =>[] ] );
				// echo "\t404 error\n";
				return;
			}
		}
	}
	if ($content === false) {									// some other error
		// echo "\terror\n";
		array_push( $pages, [ "url" => $sitetosave, "status" => false, "message" => "file_get_contents error", "count" => 0, "sub-pages" => 0, "sub-page-links" =>[] ] );
	} else {													// ALHAMDOLILLAAH success
		$count = count(explode($str, $content));				// count the keyword on this page
		// echo "\t" . $count . "\n";
		$match = [];											// declaring for use later on
		$page = [];												// entry for this page in main array
		$page["url"] = $sitetosave;								// 	- url
		$page["status"] = true;									// 	- process outcome
		$page["message"] = "in progress";						// 	- error etc message
		$page["count"] = $count;								// 	- keyword count in the page contents
		$page["sub-pages"] = 0;									// 	- number of subpage links
		$page["sub-page-links"] = [];							// 	- subpage links listed
		array_push($pages, $page);								// store page data to main array
		$pindex = count($pages) - 1;							// store the entry index for reuse InShaaALLAAH
		if (!preg_match_all("/<a\s(?:[^>]\s*)*href=[\"']*([^\"'>]+)[\"'\s]*/i", $content, $match)) { // extract urls from <a ... href=
			return;
        }
		$i = 0;													// to count processed subpages
		foreach ($match[1] as $elem) {
				/*							The algorithm to determine if the url is native or foreign
					is url extractable?
						no.....
							skip
						yes....
							starts with https, http, www?
								yes.... its a full path // www.site.com
									does the base url match?
										yes.... visit
										no..... ignore
								no..... its partial path
									has / at start?
										yes.... its local path // /some/path
										no.....
											has . in path?
												no..... its local path // some/path
												yes....
													is first . after a slash?
														yes.... its local path // some/path.php?action=act
														no..... (difficult to determine)
															does the string after . match a known file extension? // .php, .asp, .aspx, .jsp
																yes.... it could be local path
																no..... it could be foreign path	*/
			$debug = [];	// leftover from debugging
			$url = $elem;
			$pprot = preg_match("/^(?:((?:https:\/\/)|(?:http:\/\/))+).*/i", $url, $match) ? $match[1] : $protocol;
			// check if local or foreign url
			$purl = 	preg_match("/^(?:(?:(?:https:\/\/)|(?:http:\/\/))*(?:www\.)*)[a-z0-9\.]+/i", $url, $match) // url starts with these keywords?
							? (preg_match("/^(?:[^\/?\.]*\.){0,}(?:$escurl)(?=[^\.a-z0-9\-])/i", $match[0], $match) // yes - has the required base url? (any subdomain: sub0.domain.com sub1.domain.com)
								? $url // yes - this is a local full path, use whole url
								: "" // no - this is a foreign site, so ignore
							)
							: (preg_match("/^\/.+/i", $url, $match) // no, does not start with these keywords - has / at start?
								? $pprot . $siteurl . $url // yes - its local path
								: ((!preg_match("/^[a-z0-9\-]+\.[a-z0-9\-]+[^\.a-z0-9\-]/i", $url, $match)) // no - has . in path? (inverted condition)
									? $pprot . $siteurl . "/" . $url // no - its local path
									: (preg_match("/^[a-z0-9\-]+\/[a-z0-9\-]*\.[a-z0-9\-]*/i", $match[0], $match) // yes - is first . after a slash?
										? $pprot . $siteurl . "/" . $url // yes - its local path
										: (preg_match("/(?<=[a-z0-9\-]\.)[a-z0-9\-]+/i", $match[0], $match) // no - is the alphanumeric string after the first dot extractable (InShaaALLAAH of course)
											? (in_array(strtolower($match[0]), $knownexts) // ALHAMDOLILLAAH yes - does it belong to known file extensions?
												? $pprot . $siteurl . "/" . $url // yes - its "likely" a local path
												: "" // no - its "likely" a foreign site, so ignore
											)
											: "" // just unlikely (InShaaALLAAH) placeholder condition for else
										)
									)
								)
							)
						;
			$purl = trim($purl);										// still some further url verification
			if (trim($purl) != "") {
				$tmp = strlen("mailto:");								// avoid email links
				if (strlen($purl) >= $tmp)
				{
					if (substr($purl, 0, $tmp) == "mailto:")
					{
						array_push($pages[$pindex]["sub-page-links"], ["orig-url" => $url, "new-url" => $purl, "status" => "skip"]);
						continue;
					}
				}
				$tmp = strlen($pprot . $siteurl . "/" . "#");			// avoid html crumbs etc
				if (strlen($purl) >= $tmp)
				{
					if (($purl == $pprot . $siteurl . "/" . "/") || (substr($purl, 0, $tmp) == $pprot . $siteurl . "/" . "#"))
					{
						echo $offset . "$singleoffset(rejecting...) " . $purl . "\n";
						array_push($pages[$pindex]["sub-page-links"], ["orig-url" => $url, "new-url" => $purl, "status" => "skip"]);
						continue;
					}
				}
				++$pages[$pindex]["sub-pages"];
				array_push($pages[$pindex]["sub-page-links"], ["orig-url" => $url, "new-url" => $purl, "status" => "passed"]);
				// re-compute supporting urls
				$newsiteurl = preg_match("/^(?:(?:(?:https:\/\/)|(?:http:\/\/))(?:www\.)*)([^\?]+)[\/]/i", $purl, $match) ? $match[1] : $purl;
				$newescurl = str_replace(".", "\\.", $newsiteurl);
				$newescurl = str_replace("/", "\/", $newescurl);
				if (($subpagelimit > 0) & ($i >= $subpagelimit)) { 		// page limit check
					echo $offset . "(sub-page limit reached. exiting...)\n";
					break;
				}
				++$i;
				get_elements($purl, $newsiteurl, $newescurl, $pprot, $str, $stage+1);	// recursive call to process subpage
			} else {
				array_push($pages[$pindex]["sub-page-links"], ["orig-url" => $url, "new-url" => $purl, "status" => "skip"]);
			}
		} // end foreach
		$pages[$pindex]["message"] = "";
	} // end if
}
// 										- Begin main -
// compute some supporting url formats
// https://www.someurl.com/path/abc.php?r=/this/path -> someurl.com/path
$protocol = preg_match("/^(?:((?:https:\/\/)|(?:http:\/\/))+).*/i", $site, $match) ? $match[1] : "http://";
$siteurl = preg_match("/^(?:(?:(?:https:\/\/)|(?:http:\/\/))(?:www\.)*)([^\?]*)[\/]/i", $site, $match) ? $match[1] : $site;
$escurl = str_replace(".", "\\.", $siteurl);
$escurl = str_replace("/", "\/", $escurl);
set_error_handler("warning_handler", E_WARNING);				// set custom error handler for debug

function warning_handler($errno, $errstr) {						// custom error handler for debug
	// echo "Error: " . $errno . "\n";
	// echo "Error: " . $errstr . "\n";
	// debug_print_backtrace();
	// echo "\n\n";
}
echo "Root url: " . $site . "\n\n";

get_elements($site, $siteurl, $escurl, $protocol, $str);		// main call to process the url

restore_error_handler();										// restore original error handler

$counts = array_column($pages, "count");						// Display results etc
$maxdigits = strlen(strval(max($counts)));
echo "\n";
echo "- Results: \n\n";
foreach ($pages as $page) {
	if ($page["status"]) {
		echo sprintf("%0" . $maxdigits . "d", $page["count"]) . " \t " . $page["url"] . "\n";
	}
}
echo "\n";
echo "Total count of keyword \"" . $str . "\" on all pages: " . array_sum($counts) . "\n";
echo "\n";
// print_r($pages);												// uncomment to see the stored pages information

// 										- End main -
?>