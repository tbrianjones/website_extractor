<?php

  // the webpage class contains all the information we're extracting from a specific webpage
  //
  //  - the webpage object should be passed around the crawlers, scrapers, etc. so data can be stored/retrieved
  //
  class Webpage {
    
    public $url;
    public $size; // webpage file size when downloaded
    public $download_error;
    public $junk;
    public $encoding;
    public $content_type;
    public $http_status;
    public $html;
    
    // extracted data
    public $local_links = array();
    
    public function __construct( $url ) {
      $this->url = $url;
    }
    
    public function get_local_links() {
      return $this->local_links;
    }
    
    public function add_local_link( $url ) {
      if( ! in_array( $this->local_links ) )
        $this->local_links[] = $url;
    }
    
  }

?>