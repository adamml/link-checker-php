# PHP Link Checker

This repository contains a PHP class to check the links on a website.

The class has a static method which can be called to crawl all pages in a
sitemap, or nested sitemaps.

An example script is also provided to show the use of the CheckedURLs class
in a Command Line Interface.

This code is made available under the Unlicense.

## Class: `CheckedURLs`

A class to handle checking all `<a href="..." />` in a website.


### Method: `addURL(int $responseCode, String $hostUrl, String $targetURL)`

### Method: `array[[int], [String], [String]] getCheckedURLs()`

### Method: `int getHTTPResponse(String $targetURL)`

### Static Method: `linkChecker(String $sitemap, CheckedURLs $tested)`