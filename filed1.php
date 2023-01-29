<?php // BISMILLAAHIRRAHMAANIRRAHEEM
ini_set("max_execution_time", 4);
$pages = [];
function get_elements($inurl, $siteurl, $excurl, $str) {
	global $pages;
	if (!preg_match("/^(?:(?:(?:https:\/\/)|(?:http:\/\/))(?:www\.)*)([a-z0-9\.]+)/i", $inurl, $match)) { // extract url without http etc
		array_push( $pages, [ "url" => $inurl, "status" => false, "message" => "preg_match error", "count" => 0, "sub-pages" => 0, "sub-page-links" =>[] ] );
		return;
	}
	//$site = $match[0];
	$sitetosave = preg_match("/.[^?#]+/i", $match[0], $m) ? $m : $match[0]; // extract url without query string - to save as unique
	if (in_array($site, array_column($pages, "url"))) {
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
				return;
			}
		}
	}
	if ($content === false) {
		array_push( $pages, [ "url" => $sitetosave, "status" => false, "message" => "file_get_contents error", "count" => 0, "sub-pages" => 0, "sub-page-links" =>[] ] );
	} else {
		$count = count(explode($str, $content));
		$match = [];
		$page = [];
		$page["url"] = $sitetosave;
		$page["status"] = true;
		$page["message"] = "";
		$page["count"] = $count;
		$page["sub-pages"] = 0;
		$page["sub-page-links"] = [];
		if (!preg_match_all("/<a\s(?:[^>]\s*)*href=[\"']*([^\"'>]+)[\"'\s]*/i", $content, $match)) {// extract urls from <a ... href=
			return;
        }
        // if (!isset($match[1]))
        // {
        //     return;
        // }
		print_r($match);
		foreach ($match[1] as $elem) {
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
			// $url = (!preg_match("/[a-z0-9\.\/]+/i", $elem, $match)) // extract href= value - extractable?
			$purl = (!preg_match("/.+/i", $url, $match)) // extract href= value - extractable? // needless?
						? "" // no - just unlikely (InShaaALLAAH) placeholder condition for if
						// : (array_push($debug, $match)) && (preg_match("/((?<=https:\/\/)|(?<=http:\/\/)|(?<=www\.))[a-z0-9\.]+/i", $match[0], $match) // yes - starts with these keywords?
						// : (preg_match("/((?<=https:\/\/)|(?<=http:\/\/)|(?<=www\.))[a-z0-9\.]+/i", $url, $match) // yes - starts with these keywords?
						: (preg_match("/^(?:(?:(?:https:\/\/)|(?:http:\/\/))*(?:www\.)*)[a-z0-9\.]+/i", $url, $match) // yes - starts with these keywords?
							? (preg_match("/[a-z0-9\-]+\.[a-z0-9\-]+\.[a-z0-9\-]+/i", $match[0], $match) // yes - has atleast double dots?
								? (preg_match("/^[^\/?]*(?:(?:$escurl))[^\.a-z0-9\-]/i", $match[0], $match) // yes double dots - has the required base url?
									? $url // yes - this is a local full path, use whole url
									: "" // no - this is a foreign site, so ignore
								)
								: "" // this is a foreign site, so ignore
								// : (preg_match("/[a-z0-9\-]+\.[a-z0-9\-]+/i", $match[0], $match) // no double dots - has the url with single dot? (InShaaALLAAH of course)
								// 	? (($match[0] == $siteurl) // does the base url match?
								// 		? $elem // this is a local full path, use whole url
								// 		: "" // this is a foreign site, so ignore
								// 	)
								// 	: "" // just unlikely (InShaaALLAAH) placeholder condition for else
								)
							)
							: (preg_match("/^\/.+/i", $url, $match) // no, does not start with these keywords - has / at start?
								? $siteurl . $url // yes - its local path
								: ((!preg_match("/^[a-z0-9\-]+\.[a-z0-9\-]+[^\.a-z0-9\-]/i", $url, $match)) // no - has . in path? (inverted condition)
									? $siteurl . "/" . $url // no - its local path
									: (preg_match("/^[a-z0-9\-]+\/[a-z0-9\-]*\.[a-z0-9\-]*/i", $match[0], $match) // yes - is first . after a slash?
										? $siteurl . "/" . $url // yes - its local path
										: (preg_match("/(?<=[a-z0-9\-]\.)[a-z0-9\-]+/i", $match[0], $match) // no - is the alphanumeric string after the first dot extractable (InShaaALLAAH of course)
											? (in_array(strtolower($match[0]), $knownexts) // ALHAMDOLILLAAH yes - does it belong to known file extensions?
												? $siteurl . "/" . $url // yes - its "likely" a local path
												: "" // no - its "likely" a foreign site, so ignore
											)
											: "" // just unlikely (InShaaALLAAH) placeholder condition for else
										)
									)
								)
							)
						);
						echo "debug: <br />";
						print_r($debug);
						echo "<br />";
			if ($purl != "") {
				++$page["sub-pages"];
				array_push($page["sub-page-links"], $purl);
			}
			get_elements($purl, $siteurl, $excurl, $str);
		}
	}
}
$site = "https://www.nobleprog.co.uk";
$str = "training";
$siteurl = preg_match("/^(?:(?:(?:https:\/\/)|(?:http:\/\/))(?:www\.)*)([a-z0-9\.]+)/i", $site, $match) ? $match[0] : $site;
$excurl = str_replace(".", "\\.", $siteurl);
set_error_handler("warning_handler", E_WARNING);

function warning_handler($errno, $errstr) { 
	echo "Error: " . $errno . "<br />";
	echo "Error: " . $errstr . "<br />";
	debug_print_backtrace();
	echo "<br /><br />";
}
get_elements($site, $siteurl, $excurl, $str);
restore_error_handler();

?>