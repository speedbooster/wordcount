<?php // BISMILLAAHIRRAHMAANIRRAHEEM
$pages = [];
function get_count($site, $str) {
	return ($content = file_get_contents($site)) ? count(explode($str, $content)) : -1;
}
echo (($count = get_count("https://www.nobleprog.co.uk", "training")) == -1) ? "Error fetching contents" : "Keyword 'training' found $count times";
?>