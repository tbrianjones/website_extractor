<?php

  // a class to deal with amazon simple ququeing service
  //
  class Amazon_sqs {
    
    public $Client; // the sqs cleint object

    // message data
    private $crawler_queue_message_id;
    private $crawler_queue_message_receipt_handle;
    
    
    public function __construct() {
      
      // load amazon sqs client
      require_once( BASE_PATH . '/libraries/aws_php_sdk_v2/aws_client.php' );
      $this->Client = Aws_client::get_sqs_connection_instance();
      
    }
    
    // loads a website from the amazon sqs queue
    //
    //  - returns FALSE if no message was returned by sqs
    //  - returns the message data otherwise
    //
    public function get_message() {
      
      // get company from queue
    	try {
    		$Response = $this->Client->receiveMessage(
    			array(
    				'QueueUrl' => AWS_WEBSITE_EXTRACTOR_QUEUE_URL,
    				'MaxNumberOfMessages' => 1,
    				'VisibilityTimeout' => AWS_WEBSITE_EXTRACTOR_DEFAULT_VISIBILITY_TIMEOUT_SECONDS,
    				'WaitTimeSeconds' => 5,
    			)
    		);
    		$messages = $Response->get( 'Messages' );
    		
    		// no messages returned by queue - probably need to populate the queue
    		if( is_null( $messages ) ) {
    			return FALSE;
    		
    		// a message was returned by the queue
    		} else {
    		
    		  // format message data to return
    			$message = json_decode( $messages[0]['Body'], TRUE );
    			$this->crawler_queue_message_id = $messages[0]['MessageId'];
    			$this->crawler_queue_message_receipt_handle = $messages[0]['ReceiptHandle'];
    
          // return message data
          return $message;
          
    		}
    			
    	} catch ( Exception $e ) {
    		throw new Internal_resource_exception( 'Crawler SQS receiveMessage failed: ' . $e->getMessage() );
    	}
      
    }
    
    public function delete_message() {
      
      return FALSE;
      
    }
    
    // populate aws extractor queue
    public function populate_queue(
      $targets // array of target ids and urls
    ) {
    	try {
    		$i = 0;
    		$total_companies_to_queue = count( $targets );
    		foreach( $targets as $target ) {
    			$i++;
    			$entry['Id'] = $target['id'];
    			$entry['MessageBody'] = json_encode( $target );
    			$entries[] = $entry;
    			// send messages as a batch ( max of ten entries can be batched at once )
    			if(
    				count( $entries ) == 10
    				OR $i == $total_companies_to_queue
    			) {
    				$Response = $this->Client->sendMessageBatch(
    					array(
    		    		'QueueUrl' => AWS_WEBSITE_EXTRACTOR_QUEUE_URL,
    						'Entries' => $entries
    					)
    				);
    				$entries = array();
    			}
    		}
    		return TRUE;
    	} catch ( Exception $e ) {
    		throw new Internal_resource_exception( 'Extractor SQS sendMessageBatch failed: ' . $e->getMessage() );
    	}
    }
    
  } // end class

?>