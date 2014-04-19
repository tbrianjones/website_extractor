<?php

  // imports emails from keith at email-formats.com
  //
  //  *** put the domains_emails.csv file in this folder
  //
  
  ini_set( 'memory_limit', '1024M' );
  
  // load config
  require_once( '../../config.php' );

  // connect to email_scraper db
  $Db = new mysqli( EMAIL_SCRAPER_HOST, EMAIL_SCRAPER_USER, EMAIL_SCRAPER_PASS, EMAIL_SCRAPER_NAME );
  
  // getting all emails
  $contents = file_get_contents( 'domains_emails.csv' );
  $contents = explode( "\n", $contents );
  foreach( $contents as $row ) {
    $data = explode( ',', $row );
    $email = $data[1]."@".$data[0];
    $emails[] = $email;
  }
  
  // unique the emails
  $emails = array_unique( $emails );
  
  // insert into emails table
  $count = count( $emails );
  echo "\n\n total unique emails: $count";
  $i = 0;
  foreach( $emails as $email ) {
    $i++;
    echo "\n  - importing email $i of $count) $email";
    $sql = "insert into emails( email )
            values( '$email' );";
    $Qeury = $Db->query( $sql ); 
  }
  
?>