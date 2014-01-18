<?php

  // load config
  require_once( 'config.php' );
  
  // create fresh results.csv
  file_put_contents( 'results.csv', '' );

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
  require( 'Crawler.php' );
  foreach( $targets as $target ) {
    
    // populate website object
    $Website = new Website();
    $Website->base_url = $target['url'];
    $Website->name = $target['name'];
    
    // crawl website
    $Crawler = new Crawler( $Website );
    $Crawler->go( 50 );
    
    
    // --- process data from $Website object and write to csv ---
    
    
    echo "\n\n--- Website Data ---";
    
    // compile data from all webpages crawled on this website
    $webpages = $Website->get_webpages();
    $emails = array();
    $addresses = array();
    $phones = array();
    $pages_with_emails = array();
    $pages_with_phones = array();
    foreach( $webpages as $Webpage ) {
      if( count( $Webpage->emails ) > 0 )
        $emails = array_merge( $emails, $Webpage->emails );
      if( count( $Webpage->emails ) > 1 )
        $pages_with_emails[] = $Webpage->url;
      if( count( $Webpage->phones ) > 0 )
        $phones = array_merge( $phones, $Webpage->phones );
      if( count( $Webpage->phones ) > 1 )
        $pages_with_phones[] = $Webpage->url;
      if( count( $Webpage->addresses ) > 0 )
        $addresses = array_merge( $addresses, $Webpage->addresses );  
    }
    
    // extract most commonly occuriung email that matches the website url
    $emails = array_count_values( $emails );
    arsort( $emails );
    var_dump( $emails );
    $primary_email = key( $emails ); // if no email with the same domain is found, use the most common one
    foreach( $emails as $email => $count ) {
      $parts = explode( '@', $email );
      $email_domain = $parts[1];
      if( strpos( $Webpage->url, $email_domain ) ) {
        $primary_email = $email;
        break;
      }
    }
    echo "\n  - email: $primary_email";
    
    // extract the most commonly occuring phone number
    $phones = array_count_values( $phones );
    arsort( $phones );
    var_dump( $phones );
    echo "\n  - phone: ".key( $phones );
    
    // extract the most commonly occuring address
    $addresses = array_count_values( $addresses );
    arsort( $addresses );
    var_dump( $addresses );
    echo "\n  - address: ".key( $addresses );
    
    die;
    
    
    
  }

?>