<?php
    
  // load config
  require_once( 'config.php' );

  // connect to email_scraper db
  $Db = new mysqli( EMAIL_SCRAPER_HOST, EMAIL_SCRAPER_USER, EMAIL_SCRAPER_PASS, EMAIL_SCRAPER_NAME );


  // --- get next target from sqs, or populate the queue if empty -----------------
  

  // get next target to crawl
  require_once( 'Amazon_sqs.php' );
  
  $Sqs = new Amazon_sqs();
  $target = $Sqs->get_message();
  
  // target was returned
  if( $target ) {
    // do nothing ... just continue with this target
  
  // queue was empty, so try to populate it
  } else {
    
    // sleep for a random amount of time between 5 and 20 seconds
    //
    //  - then we check for messages in the queue again
    //  - if we find some we can avoid populating the queue ( means another process already did )
    //  - this keeps all the processors from trying to populate the queue at the same time when it's empty
    //
    $seconds = rand( 0, 1 );
    echo "\n\n -- sleeping for $seconds seconds ( rand between 5 and 20 )\n  - so every process doesn't try to populate the queue at the same time";
    sleep( $seconds );
    $target = $Sqs->get_message();
    if( $target ) {
      // do nothing ... just continue with this target
    } else {
    
      // load targets from database
      //
      //  - skip targets that have already been crawled ( already have emails in the emails table )
      //  - these either completed or broke midway
      //  - this allows us to kill a crawler server and it'll start back up where it left off
      //    - it will lose the last company it was processing only if those emails started writing to the db
      //
      echo "\n\n -- populating queue with target urls to process";
      $sql = "SELECT id, url
              FROM websites
              WHERE
                (
    							queued_for_processing = 0
    							OR(
    								queued_for_processing = 1
    								AND last_queued_for_processing < DATE_SUB( NOW(), INTERVAL ".( AWS_WEBSITE_EXTRACTOR_MESSAGE_RETENTION_PERIOD_SECONDS + AWS_WEBSITE_EXTRACTOR_DEFAULT_VISIBILITY_TIMEOUT_SECONDS )." SECOND )
    							)
    						)
                AND processed = 0
              LIMIT 2500";
      $Query = $Db->query( $sql );
      if( $Query ) {
        while( $Row = $Query->fetch_object() ) {
          $target['id'] = $Row->id;
          $url = parse_url( $Row->url, PHP_URL_SCHEME ).'://'.parse_url( $Row->url, PHP_URL_HOST );
          $target['url'] = trim( $url, '"' );
          $targets[] = $target;
        }
      }
      if( $Sqs->populate_queue( $targets ) ) {
        echo "\n -- updating websites in the database as queued for processing";
        $Db->autocommit( FALSE );
        foreach( $targets as $target ) {
          // update company in master db
      		$sql = "UPDATE websites
      		        SET
      		          queued_for_processing = 1,
      		          last_queued_for_processing = NOW()
      		        WHERE id = ".$target['id'];
          $Query = $Db->query( $sql );
        }
        $Db->commit();
  			$Db->autocommit( TRUE );
        die( "\n\n -- Website Extractor SQS Queue populated.\n  * No website was processed.\n\n" );
      } else {
        die( "\n\n -- Website Extractor SQS Queue failed to populate.\n  * No website was processed.\n\n" );
      }
    } 
  }
  
  
  // --- process the target -------------------------------------------------------
  
  
  // populate website object
  require_once( 'Website.php' );
  $Website = new Website();
  $Website->id = $target['id'];
  $Website->base_url = $target['url'];
  
  // crawl website
  require( 'Crawler.php' );
  $Crawler = new Crawler( $Website );
  $Crawler->go( CRAWLER_MAX_WEBPAGES_TO_CRAWL );
  
  // compile data from all webpages crawled on this website and write to db
  echo "\n\n -- Saving Emails to `email_scraper` Database";
  $webpages = $Website->get_webpages();
  $Db->autocommit( FALSE );
  foreach( $webpages as $Webpage ) {
    if( count( $Webpage->emails ) > 0 ) {
      $emails = array_count_values( $Webpage->emails );
      foreach( $emails as $email => $count ) {
        echo "\n  - $email ($count)";
        $sql = "INSERT INTO emails( website_id, url, email, count )
                VALUES( ".$Website->id.", '".$Db->real_escape_string( $Webpage->url )."', '".$Db->real_escape_string( $email )."', $count )";
        $Db->query( $sql );
      }
    }
  }
  $Db->commit();
  $Db->autocommit( TRUE );
  
  
  // --- delete message as target was succesfully processed -----------------------
  
  
  // delete message
  $Sqs->delete_message();
    
  // update website as not processing in the db
	$sql = "UPDATE websites
	        SET
	          queued_for_processing = 0,
	          processed = 1,
	          last_processed = NOW()
	        WHERE id = ".$Website->id;
  $Query = $Db->query( $sql );

?>