<?php
/**
 * This file provides a Command Line Interface to the CheckedUrls class
 */

require('CheckedURLs/CheckedURLs.php');

$smap = NULL;
foreach($argv as $arg){
    $explodeArg = explode("=", $arg);
    if($explodeArg[0] == "-sitemap")
    {
        $smap = $explodeArg[1];
    };
}
if($smap != NULL){
    $tested = CheckedURLs::linkChecker($smap, new CheckedURLs());
    print($tested);
} else {
    throw new Exception(
        "ERROR: No -sitemap parameter was passed to ".
        "linkChecker.php...".PHP_EOL);
}

?>