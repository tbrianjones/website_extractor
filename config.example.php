<?php
  
  define( 'BASE_PATH', '/data/extractor/' );
  
  // process memory
  define( 'OUTPUT_MEMORY_USAGE', TRUE );
  define( 'PHP_ALLOCATED_MEMORY', 128 ); // megabytes - default on ec2 php is 128
  ini_set( 'memory_limit', PHP_ALLOCATED_MEMORY.'M' );
  
  // email_scraper database info
  define( 'EMAIL_SCRAPER_HOST', '' );
  define( 'EMAIL_SCRAPER_USER', '' );
  define( 'EMAIL_SCRAPER_PASS', '' );
  define( 'EMAIL_SCRAPER_NAME', '' );
  
  // max consecutive non-200 http responses before killing crawl
  define( 'MAX_CONSECUTIVE_NON_200_HTTP_RESPONSES', 50 );
  
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
  
  
  // --- AMAZON AWS -----------------------------------------------------------


	// amazon developers key for jones' account
	define( 'AWS_KEY', '' );

	// amazon developers secret key for jones' account
	define( 'AWS_SECRET_KEY', '' );

	// amazon region
	define( 'AWS_REGION', 'us-east-1' ); // s3-ap-southeast-1.amazonaws.com
	
	// amazon SQS queue of companies to crawl
	//	*** this queue must exist or all requests to SQS will fail
	define( 'AWS_WEBSITE_EXTRACTOR_QUEUE',                                '' );
	define( 'AWS_WEBSITE_EXTRACTOR_QUEUE_URL',                            '' );
	define( 'AWS_WEBSITE_EXTRACTOR_DEFAULT_VISIBILITY_TIMEOUT_SECONDS',   1800 );   // this should match the settings in the sqs queue
	define( 'AWS_WEBSITE_EXTRACTOR_MESSAGE_RETENTION_PERIOD_SECONDS',     14400 );  // this should match the settings in the sqs queue
  
?>