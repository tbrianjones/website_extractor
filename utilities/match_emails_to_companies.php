<?php
  
  // matches scraped emails to company ids based on the domain
  //
  //  - pushes matches out to $csv_file_path as a csv with the company id and the em
  //
  //  - this should take about 10 minutes per 1,000,000 emails being processed
  //
  
  ini_set( 'memory_limit', '1024M' );

  // load config
  require_once( '../config.php' );

  // csv file
  $csv_file_path = 'emails.csv';
  file_put_contents( $csv_file_path, '' );

  // connect to email_scraper db
  $Db = new mysqli( EMAIL_SCRAPER_HOST, EMAIL_SCRAPER_USER, EMAIL_SCRAPER_PASS, EMAIL_SCRAPER_NAME );
  
  // get all company domains and store into $domains
  echo "\n\n -- Generating list of cortex company domains and ids";
  $sql = "SELECT id, url
          FROM company_urls";
  $Query = $Db->query( $sql );
  while( $Row = $Query->fetch_object() ) {
    $host = strtolower( parse_url( $Row->url, PHP_URL_HOST ) );
    $parts = explode( '.', $host );
    $parts = array_reverse( $parts );
    $domain = $parts[1].'.'.$parts[0];
    $domains[$domain]['ids'][] = $Row->id; // add an array here of ids that match this domain to cover multiple companies
  }
    
  // get all emails
  echo "\n\n -- Getting all emails from DB";
  $sql = "SELECT DISTINCT email
          FROM emails
          LIMIT 10000;";
  $Query = $Db->query( $sql );
  while( $Row = $Query->fetch_object() ) {
    $email = strtolower( $Row->email );
    $parts =  explode( '@', $email );
    $email_domain = $parts[1];
    if( isset( $domains[$email_domain] ) ) {
      $matching_domain = $domains[$email_domain];
      foreach( $matching_domain['ids'] as $id )
        echo "\n  - GOOD EMAIL: ".$id.' - '.$email;
        file_put_contents( $csv_file_path, $id.',"'.$email.'"'."\n", FILE_APPEND );
    } else {
      echo "\n  - bad email: $email";
    }
  }

?>