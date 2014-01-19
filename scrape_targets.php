<?php
  
  // load config
  require_once( 'config.php' );
  
  // include states array for address translation below
  require_once( 'states_array.php' );
  
  // create fresh results.csv
  file_put_contents( CSV_RESULTS_FILE_PATH, 'Organization Name,Work phone,Work email,Work web site,Work line #1,Work city,Work state,Work zip/postal code,Work country,Organization Tag 1,Background' );

  // load targets into an array
  $contents = file_get_contents( 'targets.csv' );
  $contents = explode( "\r\n", $contents );
  foreach( $contents as $content ) {
    $content = explode( ',http', $content );
    $target['name'] = trim( $content[0], '"' );
    $target['url'] = 'http'.trim( $content[1], '"' );
    $targets[] = $target;
  }
  
  // load dst api client
  require_once( 'libraries/data_science_toolkit_php_api_client/dst_api_client.php' );
  $Dst = new Dst_api_client();
  $Dst->set_base_url();
  
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
    $Crawler->go( CRAWLER_MAX_WEBPAGES_TO_CRAWL );
    
    
    // --- process data from $Website object and write to csv ---
    
    
    echo "\n\n--- Calculating Website Data ---";
    
    // compile data from all webpages crawled on this website
    $webpages = $Website->get_webpages();
    $emails = array();
    $addresses = array();
    $phones = array();
    $pages_with_emails = array();
    $pages_with_phones = array();
    foreach( $webpages as $Webpage ) {
      if( count( $Webpage->emails ) > 0 ) {
        $emails = array_merge( $emails, $Webpage->emails );
        $pages_with_emails[$Webpage->url] = count( $Webpage->emails );
      }
      if( count( $Webpage->phones ) > 0 ) {
        $phones = array_merge( $phones, $Webpage->phones );
        $pages_with_phones[$Webpage->url] = count( $Webpage->phones );
      }
      if( count( $Webpage->addresses ) > 0 )
        $addresses = array_merge( $addresses, $Webpage->addresses );  
    }
    
    // extract most commonly occuriung email that matches the website url
    if( count( $emails ) > 0 ) {
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
    } else
      $primary_email = '';
    
    // extract the most commonly occuring phone number
    if( count( $phones ) > 0 ) {
      $phones = array_count_values( $phones );
      arsort( $phones );
      var_dump( $phones );
      $primary_phone = key( $phones );
    } else
      $primary_phone = '';
    
    // extract the most commonly occuring address
    if( count( $addresses ) > 0 ) {
      $addresses = array_count_values( $addresses );
      arsort( $addresses );
      var_dump( $addresses );
      $primary_address = key( $addresses );
      $parts = $Dst->street2coordinates( $primary_address );
      $parts = json_decode( $parts, TRUE );
      $parts = current( $parts );
      if( isset( $parts['street_address'] ) )
        $street = $parts['street_address'];
      else
        $street = '';
      if( isset( $parts['locality'] ) )
        $city = $parts['locality'];
      else
        $city = '';
      if( isset( $parts['region'] ) )
        $state = $states[ $parts['region'] ];
      else
        $state = '';
      if( isset( $parts['country_name'] ) )
        $country = $parts['country_name'];
      else
        $country = '';
    } else {
      $primary_address = '';
      $street = '';
      $city = '';
      $state = '';
      $country = '';
    }    
    
    // create notes about most likely contact pages
    arsort( $pages_with_phones );
    arsort( $pages_with_emails );
    $i = 0;
    $notes = '';
    foreach( $pages_with_emails as $url => $count ) {
      $notes .= "Webpage with $count Emails\n$url\n\n";
      $i++;
      if( $i == 5 )
        break(1);
    }
    $i = 0;
    foreach( $pages_with_phones as $url => $count ) {
      $notes .= "Webpage with $count Phone Numbers\n$url\n\n";
      $i++;
      if( $i == 5 )
        break(1);
    }
    if( $primary_address != '' )
      $notes .= "Primary Address\n$primary_address";
    $notes = str_replace( "'", '"', $notes ); // removes single quotes ( which there really shouldn't be anyway ), so we don't mess up the csv
        
    // generate csv line and save it
    $csv_string = "\n".'"'.$Website->name.'","'.$primary_phone.'","'.$primary_email.'","'.$Website->base_url.'","'.$street.'","'.$city.'","'.$state.'","","'.$country.'","'.ORGANIZATION_TAG.'","'.$notes.'"';
    file_put_contents( CSV_RESULTS_FILE_PATH, $csv_string, FILE_APPEND );
    
    echo "\n  - email: $primary_email";
    echo "\n  - phone: $primary_phone";
    echo "\n  - address: $primary_address";

    die;

  }

?>