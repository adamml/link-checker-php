<?php
/** 
 * This file defines a PHP class used to test links in a website.
 * A static method in that class can be used to drive the link checking
 * from an XML sitemap.
 * 
 * @author adamml
 * @version 1.0
 */

/**
 * A class to handle checking all <a href="..." /> in a website.
 * 
 * @method addURL($responseCode, $hostURL, $targetURL)
 * @method getCheckedURLs()
 * @method getTestedResponse($targetURL)
 * @method isURLChecked($targetURL)
 * @static linkChecker($sitemap, $tested)
 */
class CheckedURLs {
    private $urls;

    public function __construct(){
        $this->urls = [[], [], []];
    }

    /**
     * Return an array of arrays of the URLs checked so far
     * 
     * @return Array of arrays. Count = 3. return[0] is an array of the
     * HTTP response codes as an integer; return[1] is an array of strings
     * of the URL on which the target URL was found; return[2] is an array
     * of strings
     */
    public function getCheckedURLs(){return $this->urls;}

    /**
     * Returns the HTTP response code for a URL. The method uses the object's
     * cached results if the URL has already been tested.
     * 
     * @param String $targetURL The URL to give the HTTP response code for
     * @return Integer HTTP response code from the URL, or -1 if no response
     * code
     */
    public function getHTTPResponse(String $targetURL){
        $i = 0;
        foreach($this->urls[2] as $thisTargetUrl){
            if($thisTargetUrl === $targetURL){
                return $this->urls[0][$i];
            }
            $i += 1;
        }
        try{
            $hrc = get_headers($targetURL);
            if($hrc != FALSE){
                return (int)substr($hrc[0], 9, 3);
            } else { return -1; }
        } finally { return -1; }

    }

    /**
     * Adds a URL to the object's cache of tested URLs.
     * 
     * @param Integer $responseCode The HTTP response code received on testing
     * the URL
     * @param String The URL of the web page on which the tested URL was found
     * @param String The target URL which was tested and provided the
     * $responseCode
     * @return Boolean Returns TRUE on success, FALSE on failure
     */
    public function addURL(int $responseCode, String $hostURL,
                                    String $targetURL){
        try{
            array_push($this->urls[0], $responseCode);
            array_push($this->urls[1], $hostURL);
            array_push($this->urls[2], $targetURL);
            return TRUE;
        } finally {return FALSE;}
    }

    public function __toString(){
        function fourohfourcounter($carry, $item){
            if($item === 404){$carry += 1;}
            return $carry;
        }

        $tableContent = "";
        $i = 0;
        foreach($this->urls[0] as $u){
            $tableContent."| {$this->urls[1][$i]} | {$this->urls[2][$i]}".
                " | {$this->urls[0][$i]} |".PHP_EOL;
            $i += 1;
        }
        return "# CheckedURLs".PHP_EOL.PHP_EOL.
        "URLs checked: ".
        count($this->urls[0])."\t\t".
        "404 Errors found: ".
        array_reduce($this->urls[0], "fourohfourcounter", 0).PHP_EOL.
        "| Website Page | Target URL | HTTP Response Code |".PHP_EOL.
        $tableContent.PHP_EOL;
    }

    /**
    * Loops through a sitemap, or nested sitemaps, and checks each page for
    * broken links.
    * 
    * Any <a href=""></a> on a page within the sitemap is tested, and non-200
    * HTTP responses are reported to the screen, with bth the containing page
    * and the target URL listed.
    * 
    * @param String $sitemap
    * @param CheckedURLs $tested
    */
    public static function linkExtract(String $sitemap, CheckedURLs $tested){
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string(file_get_contents($sitemap));
        if($xml){
            $xml->registerXPathNamespace('sm',
                            'http://www.sitemaps.org/schemas/sitemap/0.9');
            foreach($xml->xpath("//sm:loc") as $loc){
                $eLoc = explode("/", $loc);
                if(strpos(end($eLoc), "sitemap.xml")){
                    $tested = CheckedURLs::linkExtract($loc, $tested);
                } else {
                    $html = file_get_contents($loc);
                    $dom = new DOMDocument();
                    $dom->loadHTML($html);
                    $anchors = $dom->getElementsByTagName('a');
                    foreach($anchors as $a){
                        if($a->getAttribute('href') != '#' && !str_starts_with(
                                (string)$a->getAttribute('href'), 'mailto:') &&
                                str_contains((string)$a->getAttribute('href'),
                                    '://')){
                            $tested->addURL(
                                $tested->getHTTPResponse($a->getAttribute(
                                    'href')), $loc, $a->getAttribute('href'));
                        }
                    }
                }
            }
        }
        return $tested;
    }
}
?>