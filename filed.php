<?php // BISMILLAAHIRRAHMAANIRRAHEEM
ini_set("max_execution_time", 10);
$depthlimit = 2;
$subpagelimit = 10;
$pages = [];
function get_elements($inurl, $siteurl, $escurl, $protocol, $str, $stage = 0) {
	global $pages, $depthlimit, $subpagelimit;
	$offset = "";
	for ($i = 0; $i < $stage; $i++)
	{
		$offset .= "  ";
	}
	echo $offset . $inurl . "<br />\n";
	// echo $offset . $inurl;
	// echo $offset . "> " . $siteurl . "<br />\n";
	// echo $offset . "> " . $escurl . "<br />\n";
	if (!preg_match("/^(?:(?:(?:https:\/\/)|(?:http:\/\/))(?:www\.)*)(.+)/i", $inurl, $match)) { // extract url without http etc
		// echo "\turl error<br />\n";
		array_push( $pages, [ "url" => $inurl, "status" => false, "message" => "preg_match error", "count" => 0, "sub-pages" => 0, "sub-page-links" =>[] ] );
		return;
	}
	
	$site = $inurl;
	$sitetosave = preg_match("/.[^?#]+/i", $match[1], $m) ? $m[0] : $match[1]; // extract url without query string - to save as unique
	if (in_array($sitetosave, array_column($pages, "url"))) {
		// echo "\trepeated url<br />\n";
		return;
	}
	if (($depthlimit > 0) && ($stage >= $depthlimit))
	{
		// echo "\texceeded depth limit<br />\n";
		return;
	}
	$knownexts = ["php", "asp", "aspx", "jsp", "html", "htm"];
	$filters = ["(?<=https:\/\/)", "(?<=http:\/\/)", "(?<=www\.)"]; // to check if the href link points to another site
	$regfilters = "/(" . join("|", $filters) . ")[a-z0-9\.]+/i";
	$content = file_get_contents($site);
	if (isset($http_response_header))
	{
		if (isset($http_response_header[0]))
		{
			if (str_contains($http_response_header[0], "404"))
			{
				array_push( $pages, [ "url" => $sitetosave, "status" => false, "message" => "404 error", "count" => 0, "sub-pages" => 0, "sub-page-links" =>[] ] );
				// echo "\t404 error<br />\n";
				return;
			}
		}
	}
	if ($content === false) {
		// echo "\terror<br />\n";
		array_push( $pages, [ "url" => $sitetosave, "status" => false, "message" => "file_get_contents error", "count" => 0, "sub-pages" => 0, "sub-page-links" =>[] ] );
	} else {
		$count = count(explode($str, $content));
		// echo "\t" . $count . "<br />\n";
		$match = [];
		$page = [];
		$page["url"] = $sitetosave;
		$page["status"] = true;
		$page["message"] = "in progress";
		$page["count"] = $count;
		$page["sub-pages"] = 0;
		$page["sub-page-links"] = [];
		array_push($pages, $page);
		$pindex = count($pages) - 1;
		if (!preg_match_all("/<a\s(?:[^>]\s*)*href=[\"']*([^\"'>]+)[\"'\s]*/i", $content, $match)) { // extract urls from <a ... href=
			return;
        }
		$i = 0;
		foreach ($match[1] as $elem) {
			if (($subpagelimit > 0) & ($i >= $subpagelimit))
			{
				echo $offset . "(sub-page limit reached. exiting...)<br />\n";
				break;
			}
			++$i;
				/*	is url extractable?
						no.....
							skip
						yes....
							starts with https, http, www?
								yes.... its a full path // www.site.com
									does it have two "."s in base url? (before a "?" or a "/")
										yes.... (could be a subdomain or a foreign site)
											check the url after the first .
											is url local ? visit : ignore
										no.....
											check the url before / or ?
											is url local ? visit : ignore
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
			$debug = [];
			$url = $elem;
			$pprot = preg_match("/^(?:((?:https:\/\/)|(?:http:\/\/))+).*/i", $url, $match) ? $match[1] : $protocol;
			// check if local or foreign url
			$purl = 	preg_match("/^(?:(?:(?:https:\/\/)|(?:http:\/\/))*(?:www\.)*)[a-z0-9\.]+/i", $url, $match) // url starts with these keywords?
							? (preg_match("/^(?:[^\/?\.]*\.){0,}(?:$escurl)(?=[^\.a-z0-9\-])/i", $match[0], $match) // has the required base url? (any subdomain: sub0.domain.com sub1.domain.com)
								? $url // yes - this is a local full path, use whole url
								: "" // no - this is a foreign site, so ignore
							)
							// ? (preg_match("/[a-z0-9\-]+\.[a-z0-9\-]+\.[a-z0-9\-]+/i", $match[0], $match) // yes - has atleast double dots?
							// 	? (preg_match("/^(?:[^\/?\.]*\.){0,}(?:$escurl)(?=[^\.a-z0-9\-])/i", $match[0], $match) // yes double dots - has the required base url? (any subdomain: sub0.domain.com sub1.domain.com)
							// 		? $url // yes - this is a local full path, use whole url
							// 		: "" // no - this is a foreign site, so ignore
							// 	)
							// 	: "" // this is a foreign site, so ignore
							// 	// : (preg_match("/[a-z0-9\-]+\.[a-z0-9\-]+/i", $match[0], $match) // no double dots - has the url with single dot? (InShaaALLAAH of course)
							// 	// 	? (($match[0] == $siteurl) // does the base url match?
							// 	// 		? $elem // this is a local full path, use whole url
							// 	// 		: "" // this is a foreign site, so ignore
							// 	// 	)
							// 	// 	: "" // just unlikely (InShaaALLAAH) placeholder condition for else
							// 	// )
							// )
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
			$purl = trim($purl);
			if (trim($purl) != "") {
				$tmp = strlen("mailto:");
				if (strlen($purl) >= $tmp)
				{
					if (substr($purl, 0, $tmp) == "mailto:")
					{
						array_push($pages[$pindex]["sub-page-links"], ["orig-url" => $url, "new-url" => $purl, "status" => "skip"]);
						continue;
					}
				}
				$tmp = strlen($pprot . $siteurl . "/" . "#");
				if (strlen($purl) >= $tmp)
				{
					if (($purl == $pprot . $siteurl . "/" . "/") || (substr($purl, 0, $tmp) == $pprot . $siteurl . "/" . "#"))
					{
						echo $offset . "  (rejecting...) " . $purl . "<br />\n";
						array_push($pages[$pindex]["sub-page-links"], ["orig-url" => $url, "new-url" => $purl, "status" => "skip"]);
						continue;
					}
				}
				++$pages[$pindex]["sub-pages"];
				array_push($pages[$pindex]["sub-page-links"], ["orig-url" => $url, "new-url" => $purl, "status" => "passed"]);
				// $newsiteurl = preg_match("/^(?:(?:(?:https:\/\/)|(?:http:\/\/))(?:www\.)*)(.+)/i", $purl, $match) ? $match[0] : $purl;
				$newsiteurl = preg_match("/^(?:(?:(?:https:\/\/)|(?:http:\/\/))(?:www\.)*)([^\?]+)[\/]/i", $purl, $match) ? $match[1] : $purl;
				$newescurl = str_replace(".", "\\.", $newsiteurl);
				$newescurl = str_replace("/", "\/", $newescurl);
				get_elements($purl, $newsiteurl, $newescurl, $pprot, $str, $stage+1);
			} else {
				array_push($pages[$pindex]["sub-page-links"], ["orig-url" => $url, "new-url" => $purl, "status" => "skip"]);
			}
		} // end foreach
		$pages[$pindex]["message"] = "";
	} // end if
}
$site = "https://www.nobleprog.co.uk/"; // base url must end with a slash
// $site = "http://127.0.0.1:81/work/test/";
$str = "training";
// $siteurl = preg_match("/^(?:(?:(?:https:\/\/)|(?:http:\/\/))(?:www\.)*)([a-z0-9\.]+)/i", $site, $match) ? $match[0] : $site;
// $siteurl = preg_match("/^(?:(?:(?:https:\/\/)|(?:http:\/\/))(?:www\.)*)(.+)/i", $site, $match) ? $match[0] : $site;
// https://www.someurl.com/path/abc.php?r=/this/path -> someurl.com/path
$protocol = preg_match("/^(?:((?:https:\/\/)|(?:http:\/\/))+).*/i", $site, $match) ? $match[1] : "http://";
$siteurl = preg_match("/^(?:(?:(?:https:\/\/)|(?:http:\/\/))(?:www\.)*)([^\?]*)[\/]/i", $site, $match) ? $match[1] : $site;
$escurl = str_replace(".", "\\.", $siteurl);
$escurl = str_replace("/", "\/", $escurl);
set_error_handler("warning_handler", E_WARNING);

function warning_handler($errno, $errstr) { 
	// echo "Error: " . $errno . "<br />";
	// echo "Error: " . $errstr . "<br />";
	// debug_print_backtrace();
	// echo "<br /><br />";
}
echo "Root url: " . $site . "<br />\n<br />\n";
get_elements($site, $siteurl, $escurl, $protocol, $str);
$counts = array_column($pages, "count");
$maxdigits = strlen(strval(max($counts)));
echo "<br />\n";
echo "- Results: <br />\n<br />\n";
foreach ($pages as $page) {
	if ($page["status"]) {
		echo sprintf("%0" . $maxdigits . "d", $page["count"]) . " \t " . $page["url"] . "<br />\n";
	}
}
echo "<br />\n";
echo "Total count of keyword \"" . $str . "\" on all pages: " . array_sum($counts) . "<br />\n";
echo "<br />\n";
restore_error_handler();
// print_r($pages);
?>