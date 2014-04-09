<?php
    
  // load config
  require_once( 'config.php' );

  // connect to email_scraper db
  $Db = new mysqli( EMAIL_SCRAPER_HOST, EMAIL_SCRAPER_USER, EMAIL_SCRAPER_PASS, EMAIL_SCRAPER_NAME );


  // populate aws extractor queue
  require_once( BASE_PATH . '/libraries/aws_php_sdk_v2/aws_client.php' );
	$Sqs = Aws_client::get_sqs_connection_instance();
	try {
		$i = 0;
		$total_companies_to_queue = count( $Companies->ids );
		$entries = array();
		foreach( $Companies->ids as $id ) {
			$i++;
			$company['company_id'] = $id;
			$entry['Id'] = $id;
			$entry['MessageBody'] = json_encode( $company );
			$entries[] = $entry;
			// max of ten entries can be batched at once
			if(
				count( $entries ) == 10
				OR $i == $total_companies_to_queue 
			) {
				$Response = $Sqs->sendMessageBatch(
					array(
		    		'QueueUrl' => AWS_INDEXER_QUEUE_URL,
						'Entries' => $entries
					)
				);
				$entries = array();
			}
		}

	} catch ( Exception $e ) {
		throw new Internal_resource_exception( 'Extractor SQS sendMessageBatch failed: ' . $e->getMessage() );
	}
	echo 'The Indexer Queue has been populated.';








  // load targets from database
  //
  //  - skip targets that have already been crawled ( already have emails in the emails table )
  //  - these either completed or broke midway
  //  - this allows us to kill a crawler server and it'll start back up where it left off
  //    - it will lose the last company it was processing only if those emails started writing to the db
  //
  $sql = "SELECT id, url
          FROM websites
          WHERE id NOT IN(
            SELECT website_id
            FROM emails
          )
          LIMIT 1000";
  $Query = $Db->query( $sql );
  if ( $Db->connect_errno )
    echo "Failed to connect to MySQL: (" . $Db->connect_errno . ") " . $Db->connect_error;
  if( $Query ) {
    while( $Row = $Query->fetch_object() ) {
      $target['id'] = $Row->id;
      $target['name'] = 'no name';
      if( ! parse_url( $Row->url ) ) {
        echo "\n ** Bad URL ( ".$Row->url." ): skipping this target.";
        continue;
      }
      $url = parse_url( $Row->url, PHP_URL_SCHEME ).'://'.parse_url( $Row->url, PHP_URL_HOST );
      $target['url'] = trim( $url, '"' );
      $targets[] = $target;
    }
  }
    
  // load website class to store data in
  require_once( 'Website.php' );

  // process each target
  require( 'Crawler.php' );
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
    
    
    echo "\n\n--- Calculating Website Data";
    
    // compile data from all webpages crawled on this website
    echo "\n\n -- Saving Emails to `email_scraper` Database";
    $webpages = $Website->get_webpages();
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

  }

?>