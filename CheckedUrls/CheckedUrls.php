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
 * @method getHTTPResponse($targetURL)
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
            ++$i;
        }
        try{
            $hrc = get_headers($targetURL);
            
            if($hrc != FALSE){
                return intval(substr($hrc[0],9,3));
            } else { return -1; }
        } catch (Exception $e) { return -1; }

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
        } catch (Exception $e) {return FALSE;}
    }

    public function __toString(){
        $tableContent = "";
        $i = 0;
        $fourohfourCounter = 0;
        foreach($this->urls[0] as $u){
            $tableContent = $tableContent."| {$this->urls[1][$i]} |".
                " {$this->urls[2][$i]} | {$this->urls[0][$i]} |".PHP_EOL;
            if($this->urls[0][$i] == 404){++$fourohfourCounter;}
            ++$i;
        }

        return "# CheckedURLs".PHP_EOL.PHP_EOL.
        "URLs checked: ".
        $i."\t\t".
        "404 Errors found: ".
        $fourohfourCounter.PHP_EOL.
        "| Website Page | Target URL | HTTP Response Code |".PHP_EOL.
        "| ------------ | ---------- | ------------------ |".PHP_EOL.
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
    public static function linkChecker(String $sitemap, CheckedURLs $tested){
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string(file_get_contents($sitemap));
        if($xml){
            $xml->registerXPathNamespace('sm',
                            'http://www.sitemaps.org/schemas/sitemap/0.9');
            foreach($xml->xpath("//sm:loc") as $loc){
                $eLoc = explode("/", $loc);
                if(strpos(end($eLoc), "sitemap.xml")){
                    $tested = CheckedURLs::linkChecker($loc, $tested);
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