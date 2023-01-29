<?php // BISMILLAAHIRRAHMAANIRRAHEEM
$pages = [];
function get_count($site, $str) {
	return ($content = file_get_contents($site)) ? count(explode($str, $content)) : -1;
}
function get_elements($site, $str) {
	global $pages;
	$site = preg_match("/^(?:(?:(?:https:\/\/)|(?:http:\/\/))(?:www\.)*)([a-z0-9\.]+)/i", $elem, $match) // extract url without http etc
	if (in_array($site, array_column($pages, "url"))) {
		return;
	}			
	$knownexts = ["php", "asp", "aspx", "jsp", "html", "htm"];
	$filters = ["(?<=https:\/\/)", "(?<=http:\/\/)", "(?<=www\.)"]; // to check if the href link points to another site
	$regfilters = "/(" . join("|", $filters) . ")[a-z0-9\.]+/i";
	$content = file_get_contents($site);
	if ($content === false) {
		array_push( $pages, [ "url" => $site, "status" => false, "count" => 0, "sub-pages" => 0, "sub-page-links" =>[] ] );
	} else {
		$count = count(explode($str, $content));
		$match = [];
		$page = [];
		$page["url"] = $site;
		$page["status"] = true;
		$page["count"] = $count;
		$page["sub-pages"] = 0;
		$page["sub-page-links"] = [];
		if (!preg_match_all("/<a\s(?:[^>]\s*)*href=[\"']*([a-z0-9]+)/i", $content, $match)) {// extract urls from <a ... href=
			return;
        }
        if (!isset($match[1]))
        {
            return;
        }
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
			$url = (!preg_match("/[a-z0-9\.\/]+/i", $elem, $match)) // extract href= value - extractable?
						? "" // no - just unlikely (InShaaALLAAH) placeholder condition for if
						: preg_match("/((?<=https:\/\/)|(?<=http:\/\/)|(?<=www\.))[a-z0-9\.]+/i", $elem, $match) // yes - starts with these keywords?
							? preg_match("/[a-z0-9]+\.[a-z0-9]+\.[a-z0-9]+/i", $match[0], $match) // yes - has double dots?
								? preg_match("/(?<=[a-z0-9]\.)[a-z0-9]+\.[a-z0-9]+/i", $match[0], $match) // yes double dots - has the url after the first dot? (InShaaALLAAH of course)
									? ($match[0] == $siteurl) // does the base url match?
										? $elem // yes - this is a local full path, use whole url
										: "" // no - this is a foreign site, so ignore
									: "" // double dots - just unlikely (InShaaALLAAH) placeholder condition for else
								: preg_match("/[a-z0-9]+\.[a-z0-9]+/i", $match[0], $match) // no double dots - has the url with single dot? (InShaaALLAAH of course)
									? ($match[0] == $siteurl) // does the base url match?
										? $elem // this is a local full path, use whole url
										: "" // this is a foreign site, so ignore
									: "" // just unlikely (InShaaALLAAH) placeholder condition for else
							: preg_match("/^\/.+/i", $match[0], $match) // no, does not start with these keywords - has / at start?
								? $siteurl . "/" . $match[0] // yes - its local path
								: (!preg_match("/[a-z0-9]+\.[a-z0-9]+/i", $match[0], $match)) // no - has . in path? (inverted condition)
									? $siteurl . "/" . $match[0] // no - its local path
									: preg_match("/^[a-z0-9]+\/[a-z0-9]*\.[a-z0-9]*/i", $match[0], $match) // yes - is first . after a slash?
										? $siteurl . "/" . $match[0] // yes - its local path
										: preg_match("/(?<=[a-z0-9]\.)[a-z0-9]+/i", $match[0], $match) // no - is the alphanumeric string after the first dot extractable (InShaaALLAAH of course)
											? in_array(strtolower($match[0]), $knownexts) // ALHAMDOLILLAAH yes - does it belong to known file extensions?
												? $siteurl . "/" . $match[0] // yes - its "likely" a local path
												: ""; // no - its "likely" a foreign site, so ignore
			if ($url != "") {
				++$page["sub-pages"];
				array_push($page["sub-page-links"], $url);
			}
		}
	}
}
$site = "https://www.nobleprog.co.uk";
$siteurl = "nobleprog.co.uk";




echo "found " . get_count($site) . "<br />";

http://google.ca
https://www.google.ca
www.google.ca
www.google.ca/some/path
/path
some/path
this.site.com
this.site.com/path




^((https:\/\/)|(http:\/\/)|(www\.)) // starts with?
	yes.... its a full path // www.site.com
		does it have two "."s before a "?" or a "/"?
			yes....
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
									no..... it could be foreign path
							

									
http://ww.google.ca/aas.chttps://
www.google.ca/abc

((?<=https:\/\/)|(?<=http:\/\/)|(?<=www\.))*[a-z0-9\.]+
^(?:(?:https:\/\/)|(?:http:\/\/)|(?:www\.))([a-z0-9\.]+)

<body><a ad href="asdad">dfdsf</a><a href="dfsdf">fsdfsdfdsf</a>

<a\s(?:.[^>]\s*)*href=([\"'a-z0-9]+)
<a\s([^>]\s*)*href=(["'a-z0-9]+)
[<a\s][a-z^>\s*]*href=(["'a-z0-9]+)
<a\s([^>]\s*)*href=
='some.com/url/a1.php fg
(?<=https:\/\/)[a-z0-9\.\/\?\&\=]+
((?<=https:\/\/)|(?<=http:\/\/))[a-z0-9\.]+
((?<=https:\/\/)|(?<=http:\/\/))[a-z0-9\.]+
(?<=[a-z0-9\.])\/[a-z0-9]+ 
^((https:\/\/)|(http:\/\/)|(www\.))
((?<=https:\/\/)|(?<=http:\/\/)|(?<=www\.))[a-z0-9\.]+
(?<=www\.)google\.ca[^a-z0-9][a-z0-9\.\/\?\&\=]+

google.ca/aas.chttps://
www.google.ca/abc
<a\s+[a-z^>]*[\s]+href=
<a\s+[a-z^>]*(?<=\s)href=
(<a\s+)(((?<=[a-z^>])*\s)*)href=
<a (.[^>])*href=
<a [a-z\s]*href=
<a\s([^\s^>]\s)*href=
<a [a-z='"\s]*href=








$c = 3;
if (($d = $c) == 3) {
	echo "yes";
} else {
	echo "no";
}
echo "<br />";
echo $d;

echo "<br /><br /><br />";


$fil = "/<a\s(?:[^>]\s*)*href=([\"'a-z0-9]+)/i";
$fil = "/<a\s(?:.[^>]\s*)*href=([\"'a-z0-9]+)/i";
$ma = [];
$str = '<body><a href="asdad">dfdsf</a><a href="dfsdf">fsdfsdfdsf</a>
';
echo (int)preg_match_all($fil, $str, $ma) . "<br />";
echo "<textarea cols=80 rows=10>" . print_r($ma, true) . "</textarea>";
echo "<br /><br />";







$filters = ["(?<=https:\/\/)", "(?<=http:\/\/)", "(?<=www\.)"];
$regfilters = "/(" . join("|", $filters) . ")[a-z0-9\.]+/i";
$str = "https://www.google.ca";
$ma = [];
echo $regfilters . "<br />";
//$regfilters = "/.*/i";
echo (int)preg_match($regfilters, $str, $ma) . "<br />";
print_r($ma);

echo "<br /><br />";

$rep = [",", "."];
$repw = "";
echo str_replace($rep, $repw, "some, text.is it") . "<br /><br />";


//$ep = "/[a-z0-9\.\/]+/gi";
$ep = "/[a-z0-9\.\/]+/i";

$str = '"/uRl/a1.php"';
//$ep = '/[m-o]+/i';
//$str = 'someone';

$ma = [];
echo (int)preg_match($ep, $str, $ma) . "<br />";
print_r($ma);

/*echo "<br /><br />";
if (preg_match("/\bweb\b/i", "PHP is the website scripting language of choicefor web .")) {
    echo "A match was found.";
} else {
    echo "A match was not found.";
}
*/
							
							
							
						



















?>