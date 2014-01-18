<?php

  // the website class contains all the information we're extracting about a website
  //
  //  - the website object should be passed around the crawlers, scrapers, etc. so data can be stored/retrieved
  //
  class Website {
    
    public $name;
    public $base_url;
    
    // array containing website objects
    //
    //  - must use $this->add_webpage() method to maintain urls as keys
    //    - this allows us to efficiently skip duplicate webpages
    //
    private $webpages = array();
    
    public function __construct() {}
    
    public function get_webpages() {
      return $this->webpages;
    }
    
    public function add_webpage( webpage $Webpage ) {
      if( ! array_key_exists( $Webpage->url, $this->webpages ) )
        $this->webpages[ $Webpage->url ] = $Webpage;
    }
    
    public function update_webpage( webpage $Webpage ) {
      $this->webpages[ $Webpage->url ] = $Webpage;
    }
    
  }

?>