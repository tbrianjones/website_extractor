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
    public $content_stripped_of_html = NULL;
    
    // extracted data
    public $local_links = array();
    public $emails = array();
    public $phones = array();
    public $faxes = array();
    public $addresses = array();
    public $terms = array();
        
    public function __construct( $url ) {
      $this->url = $url;
    }
    
    public function gen_content_stripped_of_html() {
      if( is_null($this->content_stripped_of_html) )
        $this->content_stripped_of_html = strip_tags( $this->html, '<br><br/><br />' );
    }
    
  }

?>