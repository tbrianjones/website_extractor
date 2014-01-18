<?php

  class Crawler {
    
    $Curl;
    $Scraper;
    $Website; // the website being crawled
    
    // array containing all found page urls
    private $urls = array();
    
    // construct
    public function __construct( website $Website ) {
  
      // store the website object
      $this->Website = $Website;
      
      // prime urls with base_url from website
      require_once( 'Webpage.php' );
      $this->Website->webpages[] = new Webpage( $url );
      
      // create curl object to download urls
      require_once( 'Curl.php' );
      $this->Curl = new Curl();
      
      // creaste scraper object to process html
      require_once( 'Html_scraper.php' );
      $this->Scraper = new Html_scraper();
      
    } // end function
    
    // crawl the site
    public function go(
      $limit = 1 // max files to crawl
    ) {
      
      $webpages_crawled = 0;
      $webpages = $this->Website->get_webpages();
      foreach( $webpages as $url => $Webpage ) {
      
        // stop crawling when specified limit is reached
        if( $webpages_crawled == $limit ) {
          echo "\n\n ** Crawler processed specified max number of files .. crawling stopped.";
          break( 1 );
        }
        
        if( $Webpage = $this->Curl->go( $Webpage ) ) {
          $Webpage = $this->Scraper( $Webpage );

          // add new webpages to website
          $local_links = $Webpage->get_local_links();
          foreach( $local_links as $url )
            $this->Website->add_webpage( new Webpage( $url ) );
        }
        
        $this->Website->update_webpage( $Webpage );
        
        $webpages_crawled++;
        
      }
      
    } // end function
    
  } // end class

?>