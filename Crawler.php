<?php

  class Crawler {
    
    private $Curl;
    private $Scraper;
    private $Website; // the website being crawled
    
    // array containing all found page urls
    private $urls = array();
    
    // num non 200 http responses in a row
    private $consecutive_bad_http_responses = 0;
    
    // website extractor db connection
    private $Db;
    
    // construct
    public function __construct( website $Website ) {
  
      // store the website object
      $this->Website = $Website;
      
      // prime urls with base_url from website
      require_once( 'Webpage.php' );
      $this->Website->add_webpage( new Webpage( $this->Website->base_url ) );
      
      // create curl object to download urls
      require_once( 'Curl.php' );
      $this->Curl = new Curl();
      
      // creaste scraper object to process html
      require_once( 'Html_scraper.php' );
      $this->Scraper = new Html_scraper();
      
      // connect to db to save emails
      //
      //  - this is a hack on this system so we can delete each webpage as we process it
      //  - this saves memory, and was introduced so we could crawl tens of thousands of pages on a site
      //
      $this->Db = new mysqli( EMAIL_SCRAPER_HOST, EMAIL_SCRAPER_USER, EMAIL_SCRAPER_PASS, EMAIL_SCRAPER_NAME );
      
    } // end function
    
    // crawl the site
    public function go(
      $limit = 1 // max files to crawl
    ) {
      
      $num_webpages_crawled = 0;
      $num_webpages_scraped = 0;
      $num_webpages_to_crawl = count( $this->Website->get_webpages() );
      while( $num_webpages_crawled < $num_webpages_to_crawl ) {
        
        // output memory usage
        if( OUTPUT_MEMORY_USAGE ) {
          echo "\n\n -- Memory Usage";
          echo "\n  - ".memory_get_usage();
          echo "\n  - ".memory_get_usage( TRUE );
          echo "\n  - ".memory_get_peak_usage();
          echo "\n  - ".memory_get_peak_usage( TRUE );
        }
        
        // stop crawling when specified limit is reached
        if( $num_webpages_scraped == $limit ) {
          echo "\n\n ** Crawler processed specified max number of files .. crawling stopped.";
          break( 1 );
        }
        
        // stop crawling right before the process runs out of memory
        if( memory_get_peak_usage( TRUE ) > (PHP_ALLOCATED_MEMORY-1)*1048576 ) {
          echo "\n\n ** Crawler about to run out of memory .. crawling stopped ( so we can save processed data ).";
          break( 1 );
        }
        
        // process next webpage
        $webpages = $this->Website->get_webpages();
        $webpages = array_slice( $webpages, $num_webpages_crawled );
        $Webpage = current( $webpages );
        echo "\n\n=== PROCESSING WEBPAGE $num_webpages_crawled of $num_webpages_to_crawl Crawled - $num_webpages_scraped Scraped ( max to scrape: $limit )\n    $Webpage->url";
        if( $Webpage = $this->Curl->go( $Webpage ) ) {
          if( $Webpage->junk ) {
            echo "\n ** JUNK: $Webpage->download_error";
          } else {
            // scrape webpage
            $Webpage = $this->Scraper->go( $Webpage );
            // add new webpages to website
            $local_links = $Webpage->local_links;
            $local_links = array_unique( $local_links ); // do not iterate over duplicates
            foreach( $local_links as $url )
              $this->Website->add_webpage( new Webpage( $url ) );
            $num_webpages_scraped++;
          }
        }
        
        // update emails in database ( if any were found )
        if( count( $Webpage->emails ) > 0 ) {
          $this->Db->autocommit( FALSE );
          $emails = array_count_values( $Webpage->emails );
          foreach( $emails as $email => $count ) {
            echo "\n  - $email ($count)";
            $sql = "INSERT INTO emails( website_id, url, email, count )
                    VALUES( ".$this->Website->id.", '".$this->Db->real_escape_string( $Webpage->url )."', '".$this->Db->real_escape_string( $email )."', $count )";
            $this->Db->query( $sql );
          }
          $this->Db->commit();
          $this->Db->autocommit( TRUE );
        }
        
        // kill crawl if we get a lot of bad http responses in a row
        if( $Webpage->http_status != 200 )
          $consecutive_bad_http_responses++;
        else
          $consecutive_bad_http_responses = 0;
        echo "\n  * consecutive non-200 http status codes: $consecutive_bad_http_responses of ".MAX_CONSECUTIVE_NON_200_HTTP_RESPONSES;
        if( $consecutive_bad_http_responses >= MAX_CONSECUTIVE_NON_200_HTTP_RESPONSES )
          die( "\n\n ** Target has return many consecutive non-200 HTTP Responses. Something's probably wrong. Crawl stopped." );
        
        // overwrite webpage that was just processed with an object that just has the url
        //
        //  - this is a hack to save on memory
        //
        $this->Website->update_webpage( new Webpage( $Webpage->url ) );
        
        // update for next loop
        $num_webpages_crawled++;
        $num_webpages_to_crawl = count( $this->Website->get_webpages() );
                
      }
      
    } // end function
    
  } // end class

?>