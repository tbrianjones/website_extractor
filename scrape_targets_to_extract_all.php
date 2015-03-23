<?php
    
  // load config
  require_once( 'config.php' );
  
  // include states array for address translation below
  require_once( BASE_PATH.'app/helpers/states_array.php' );
  
  // create fresh results.csv
  $string = '"ID","Website Name","URL","Street","City","State","Zip","Raw Address","Count"';
  file_put_contents( CSV_ADDRESS_RESULTS_FILE_PATH, $string );

  // create fresh results.csv
  $string = '"ID","Website Name","URL","Phone","Count"';
  file_put_contents( CSV_PHONE_RESULTS_FILE_PATH, $string );

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
  if( SCRAPE_ADDRESSES AND GEOCODING_SOURCE == 'dst' ) {
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
    foreach( $webpages as $Webpage ) {
      if( count( $Webpage->emails ) > 0 )
        $emails = array_merge( $emails, $Webpage->emails );
      if( count( $Webpage->phones ) > 0 )
        $phones = array_merge( $phones, $Webpage->phones );
      if( count( $Webpage->addresses ) > 0 )
        $addresses = array_merge( $addresses, array_unique( $Webpage->addresses ) );
    }
        
    // extract most commonly occuring email that matches the website url
    /*f( count( $emails ) > 0 ) {
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
      $primary_email = '';*/
    
    // extract the most commonly occuring phone number
    if( count( $phones ) > 0 ) {
      $phones = array_count_values( $phones );
      foreach( $phones as $phone => $count ) {
        $phone = preg_replace( '/[^0-9]/', '', $phone );
        $csv_string = "\n".'"'.$Website->id.'","'.preg_replace( '/[^a-zA-Z0-9\s]/', '', $Website->name ).'","'.$Website->base_url.'","'.$phone.'","'.$count.'"';
        file_put_contents( CSV_PHONE_RESULTS_FILE_PATH, $csv_string, FILE_APPEND );
      } 
    }
    
    // extract the most commonly occuring address
    if( SCRAPE_ADDRESSES AND count( $addresses ) > 0 ) {
      $addresses = array_count_values( $addresses );
      foreach( $addresses as $address => $count ) {
        echo "\n -- Normalizing Address: $address";
        $street = '';
        $city = '';
        $state = '';
        $country = '';
        $zip = '';
        if( GEOCODING_SOURCE == 'dst' ) {
          $parts = $Dst->street2coordinates( $address );
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
          $zip = substr( $address, -5 );
          if( ! is_numeric( $zip ) )
            $zip = '';
        } else if( GEOCODING_SOURCE == 'geocodio' ) {
          time_nanosleep( 0, 500000000 );
          $cmd = 'curl -XGET "https://api.geocod.io/v1/parse?q='.urlencode($address).'&api_key='.GEOCODIO_API_KEY.'"';
          //echo "\n\n".'curl -XGET "https://api.geocod.io/v1/geocode?q='.urlencode($address).'&api_key='.GEOCODIO_API_KEY.'"'."\n\n";
          $response = shell_exec( $cmd );
          $Response = json_decode( $response );
          if( isset($Response->address_components->formatted_street) )
            $street = $Response->address_components->number.' '.$Response->address_components->formatted_street;
          if( isset($Response->address_components->city) )
            $city = $Response->address_components->city;
          if( isset($Response->address_components->state) )
            $state = $Response->address_components->state;
          if( isset($Response->address_components->zip) )
            $zip = $Response->address_components->zip;
        }
        
        // cleanse for csv
        $street = str_replace( '"', ' ', $street );
        $city = str_replace( '"', ' ', $city );
        $address = str_replace( '"', ' ', $address );

        // generate basic results csv line and save
        $csv_string = "\n".'"'.$Website->id.'","'.preg_replace( '/[^a-zA-Z0-9\s]/', '', $Website->name ).'","'.$Website->base_url.'","'.$street.'","'.$city.'","'.$state.'","'.$zip.'","'.$address.'","'.$count.'"';
        file_put_contents( CSV_ADDRESS_RESULTS_FILE_PATH, $csv_string, FILE_APPEND );
      }
      
    }
  }

?>