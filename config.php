<?php


  // --- GENERAL SETTINGS -------------------------------------------------

  
  // base path of this app
  define( 'BASE_PATH', '/data/website_extractor/' ); // include trailing slash
  
  // what to extract
  define( 'EXTRACT_ADDRESSES', 1 ); // extract addresses?  0-no, 1-yes

  // the max number of files to crawl per website
  define( 'CRAWLER_MAX_WEBPAGES_TO_CRAWL', 20 );
  
  // what to scrape
  define( 'SCRAPE_EMAILS',    1 );
  define( 'SCRAPE_ADDRESSES', 1 );
  define( 'SCRAPE_PHONES',    1 );
  define( 'SCRAPE_TERMS',     0 );
  

  // --- ADVANCED SETTINGS ------------------------------------------------
  
  
  // target list settings
  // - sometimes it's "\n"
  // - sometimes it's "\r\n"
  define( 'NEW_LINE_CHARACTER', "\n" );
  
  // results files
  define( 'CSV_RESULTS_FILE_PATH', BASE_PATH.'results/results.csv' );
  define( 'CONTACT_PAGES_CSV_RESULTS_FILE_PATH', BASE_PATH.'results/contact_pages.csv' );
  

  // crawler sleep
  define( 'CRAWLER_SLEEP_BETWEEN_DOWNLOADS', 250000 ); // microseconds to sleep between downloads ( 2,000,000 = 2sec )

  // output processing messages
  define( 'CRAWLER_OUTPUT_DOWNLOAD_MESSAGES',     FALSE );  // output messages about downloading files processing
  define( 'CRAWLER_OUTPUT_LINK_MESSAGES',         FALSE );  // output messages about link processing

  // curl connection settings
  define( 'CURL_CONNECTION_TIMEOUT',      5 );
  define( 'CURL_DOWNLOAD_TIMEOUT',        10 );
  define( 'CURL_MAX_DOWNLOAD_SIZE',       1000000 ); // bytes ( eg. 5000000 is 5mb ) - actually functions as mark as junk if over this size filter
  define( 'CURL_MAX_HTML_DOWNLOAD_SIZE',  150000 ); // bytes ( eg. 5000000 is 5mb ) - actually functions as mark as junk if over this size filter
  define( 'CURL_USER_AGENT',              'Industrial Interface Web Crawler - http://www.industrycortex.com/crawler.php' );
  
?>
