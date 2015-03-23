<?php
    
  // load config
  require_once( 'config.php' );
  
  // include states array for address translation below
  require_once( BASE_PATH.'app/helpers/states_array.php' );
  
  // load terms to create columns in output
  $contents = file_get_contents( BASE_PATH.'inputs/terms.csv' );
  $contents = explode( "\n", $contents );
  foreach( $contents as $row ) {
    $term = explode( '","', $row );
    $terms[] = trim( $term[0], '"' );
  }
  
  // create fresh results.csv
  $string = '"ID","Website Name","URL","Primary Telephone","Primary Street","Primary City","Primary State","Primary Zip","Num Emails","Num Phones","Num Addresses"';
  foreach( $terms as $term ) // add terms to results output
    $string .= ',"'.$term.'"';
  file_put_contents( CSV_RESULTS_FILE_PATH, $string );
  
  // create fresh contacts pages results file
  file_put_contents( CONTACT_PAGES_CSV_RESULTS_FILE_PATH, '"ID","Website Name","Website URL","File URL","Num Emails"' );

  // load targets.csv
  $contents = file_get_contents( BASE_PATH.'inputs/targets.csv' );
  $contents = str_replace( '“', '"', $contents ); // replace stupid angled double quotes
  $contents = str_replace( '”', '"', $contents ); // replace stupid angled double quotes
  $contents = explode( NEW_LINE_CHARACTER, $contents );
  foreach( $contents as $content ) {
    $content = explode( ',"', $content );
    $target['id'] = trim( $content[0], '" ' );
    $target['name'] = trim( $content[1], '" ' );
    $url = trim( $content[2], '" ' );
    if( ! parse_url( $url ) ) {
      echo "\n ** Bad URL ( ".$url." ): skipping this target.";
      continue;
    }
    $url = parse_url( $url, PHP_URL_SCHEME ).'://'.parse_url( $url, PHP_URL_HOST );
    $target['url'] = trim( $url, '"' );
    $targets[] = $target;
  }
        
  // load dst api client
  if( SCRAPE_ADDRESSES ) {
    require_once( BASE_PATH.'app/libraries/data_science_toolkit_php_api_client/dst_api_client.php' );
    $Dst = new Dst_api_client();
    $Dst->set_base_url();
  }
  
  // load website class to store data in
  require_once( BASE_PATH.'app/models/Website.php' );

  // process each target
  require( BASE_PATH.'app/Crawler.php' );
  foreach( $targets as $target ) {
    
    // populate website object
    $Website = new Website();
    $Website->id = $target['id'];
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
    $found_terms = array();
    foreach( $terms as $term )
      $found_terms[$term] = 0;
    $pages_with_emails = array();
    $pages_with_phones = array();
    foreach( $webpages as $Webpage ) {
      if( count( $Webpage->emails ) > 0 ) {
        $emails = array_merge( $emails, $Webpage->emails );
        $pages_with_emails[$Webpage->url] = count( array_unique( $Webpage->emails ) );
      }
      if( count( $Webpage->phones ) > 0 ) {
        $phones = array_merge( $phones, $Webpage->phones );
        $pages_with_phones[$Webpage->url] = count( array_unique( $Webpage->phones ) );
      }
      if( count( $Webpage->addresses ) > 0 )
        $addresses = array_merge( $addresses, array_unique( $Webpage->addresses ) );
      if( count( $Webpage->terms ) > 0 ) {
        foreach( $Webpage->terms as $term => $count )
          $found_terms[$term] = $found_terms[$term] + $count;
      }
    }
    
    var_dump( $found_terms );
    
    // extract most commonly occuring email that matches the website url
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
    if( SCRAPE_ADDRESSES AND count( $addresses ) > 0 ) {
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
      // extract zip
      $zip = substr( $primary_address, -5 );
      if( ! is_numeric( $zip ) )
        $zip = '';
    } else {
      $primary_address = '';
      $street = '';
      $city = '';
      $state = '';
      $country = '';
      $zip = '';
    }
    
    // generate basic results csv line and save
    if( $primary_phone != '' )
      $primary_phone = preg_replace( '/[^0-9]/', '', $primary_phone );
    $csv_string = "\n".'"'.$Website->id.'","'.preg_replace( '/[^a-zA-Z0-9\s]/', '', $Website->name ).'","'.$Website->base_url.'","'.$primary_phone.'","'.preg_replace( '/[^a-zA-Z0-9\s]/', '', $street ).'","'.preg_replace( '/[^a-zA-Z0-9\s]/', '', $city ).'","'.$state.'","'.$zip.'","'.count($emails).'","'.count($phones).'","'.count($addresses).'"';
    foreach( $terms as $term )
      $csv_string .= ',"'.$found_terms[$term].'"';
    file_put_contents( CSV_RESULTS_FILE_PATH, $csv_string, FILE_APPEND );
    
    // generate csv containing pages with lots of emails
    foreach( $pages_with_emails as $url => $count ) {
      if( $count > 1 ) {
        $string = '"'.$Website->id.'","'.$Website->name.'","'.$Website->base_url.'","'.$url.'","'.$count.'"';    
        file_put_contents( CONTACT_PAGES_CSV_RESULTS_FILE_PATH, "\n".$string, FILE_APPEND );
      }
    }
    
    echo "\n  - email: $primary_email";
    echo "\n  - phone: $primary_phone";
    echo "\n  - address: $primary_address";

  }

?>