<?php

  // load config
  require_once( 'config.php' );

  // load targets into an array
  $contents = file_get_contents( 'targets.csv' );
  $contents = explode( "\r\n", $contents );
  foreach( $contents as $content ) {
    $content = explode( ',http', $content );
    $target['name'] = trim( $content[0], '"' );
    $target['url'] = 'http'.trim( $content[1], '"' );
    $targets[] = $target;
  }
  
  // load website class to store data in
  require_once( 'Website.php' );

  // process each target
  require( 'crawler.php' );
  foreach( $targets as $target ) {
    
    // populate website object
    $Website = new Website();
    $Website->base_url = $target['url'];
    $Website->name = $target['name'];
    
    // crawl website
    $Crawler = new Crawler( $Website );
    $Crawler->go( 5 );
    
    var_dump( $Website );
        
  }

?>