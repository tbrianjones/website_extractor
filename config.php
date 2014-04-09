<?php
  
  // target list settings
  // - sometimes it's "\n"
  // - sometimes it's "\r\n"
  define( 'NEW_LINE_CHARACTER', "\n" );
  
  // the max number of files to crawl per website
  define( 'CRAWLER_MAX_WEBPAGES_TO_CRAWL',    10 );

  // crawler sleep
  define( 'CRAWLER_SLEEP_BETWEEN_DOWNLOADS',      0 );      // seconds to sleep between downloads

  // output processing messages
  define( 'CRAWLER_OUTPUT_DOWNLOAD_MESSAGES',     FALSE );  // output messages about downloading files processing
  define( 'CRAWLER_OUTPUT_LINK_MESSAGES',         FALSE );  // output messages about link processing

  // curl connection settings
  define( 'CURL_CONNECTION_TIMEOUT',      2 );
  define( 'CURL_DOWNLOAD_TIMEOUT',        5 );
  define( 'CURL_MAX_DOWNLOAD_SIZE',       1000000 ); // bytes ( eg. 5000000 is 5mb ) - actually functions as mark as junk if over this size filter
  define( 'CURL_MAX_HTML_DOWNLOAD_SIZE',  150000 ); // bytes ( eg. 5000000 is 5mb ) - actually functions as mark as junk if over this size filter
  define( 'CURL_USER_AGENT',              'Web Crawler' );
  
?>
