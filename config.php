<?php

  define( 'CURL_CONNECTION_TIMEOUT',      5 );
  define( 'CURL_DOWNLOAD_TIMEOUT',        10 );
  define( 'CURL_MAX_DOWNLOAD_SIZE',       1000000 ); // bytes ( eg. 5000000 is 5mb ) - actually functions as mark as junk if over this size filter
  define( 'CURL_MAX_HTML_DOWNLOAD_SIZE',  150000 ); // bytes ( eg. 5000000 is 5mb ) - actually functions as mark as junk if over this size filter
  define( 'CURL_USER_AGENT',              'Industrial Interface Web Crawler - http://www.industrycortex.com/crawler.php' );
  
?>