<?php
	
	//
	// signleton class for aws connections
	//
	//	- AWS Client connections were crushing memory when loaded inside file objects
	//		so TBJ created this singleton class.  Cuts memory usage by a lot.
	//
	
	// include amazon php sdk ( DOCS: http://docs.amazonwebservices.com/aws-sdk-php-2/latest/class-Aws.S3.S3Client.html )
	require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'vendor/autoload.php' );
	
	// allows use of the specific sdk components
	use Aws\S3\S3Client;
	use Aws\Sqs\SqsClient;

	class Aws_client
	{
	
		// aws connections
		private static $S3;
		private static $Sqs;
		
		// construct
		//
		private function __construct() {} 
		
		// returns s3 connection instance
		public static function get_s3_connection_instance() {
			// create aws s3 conneciton, if it wasn't created previously
			if( ! self::$S3 ) {
				try {
					$config = array(
					    'key'    => AWS_KEY,
					    'secret' => AWS_SECRET_KEY,
					    'region' => AWS_REGION
					);
					self::$S3 = S3Client::factory( $config );
				} catch ( Exception $e ) {
					throw new Internal_resource_exception( 'Could not connect to Amazon S3: ' . $e->getMessage() );
				}
			}
			return self::$S3;
		}
		
		// returns sqs connection instance
		public static function get_sqs_connection_instance() {
			// create aws sqs conneciton, if it wasn't created previously
			if( ! self::$Sqs ) {
				try {
					$config = array(
					    'key'    => AWS_KEY,
					    'secret' => AWS_SECRET_KEY,
					    'region' => AWS_REGION
					);
					self::$Sqs = SqsClient::factory( $config );
				} catch ( Exception $e ) {
					throw new Internal_resource_exception( 'Could not connect to Amazon SQS: ' . $e->getMessage() );
				}
			}
			return self::$Sqs;
		}
		
	}
		
?>