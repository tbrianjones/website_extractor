<?php
  
  // the max number of files to crawl per website
  define( 'CRAWLER_MAX_WEBPAGES_TO_CRAWL',    100 );
  
  // the tag to apply to each organization in insightly
  define( 'ORGANIZATION_TAG', 'CCR' );
  
  // the csv file results are stashed in
  define( 'CSV_RESULTS_FILE_PATH', 'results.csv' );

  // crawler sleep
  define( 'CRAWLER_SLEEP_BETWEEN_DOWNLOADS',      0 );      // seconds to sleep between downloads

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
