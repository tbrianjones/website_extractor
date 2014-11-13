<?php

  class Curl {
    
    // download file
		function go( webpage $Webpage ) {
		
			// output what's happening
			echo "\n\n--- DOWNLOADING FILE";
			
			// mark certain files as junk and don't download them
			$bad_file_types = array(
        '.pdf',
        '.mov',
        '.mpg',
        '.mp3',
        '.mp4',
        '.doc',
        '.xls',
        '.wmv',
        '.zip',
        '.bmp',
        '.jpg',
        '.png',
        '.tif',
        '.gif',
        '.swf',
        '.exe'
			);
			if( in_array( substr( $Webpage->url, -4 ), $bad_file_types ) ) {
			  $Webpage->junk = TRUE;
				$Webpage->download_error = "We don't process this file type.";
				return $Webpage;
      }
			
			// make curl request
			$curl = curl_init( $Webpage->url );
			$options = array(
				CURLOPT_RETURNTRANSFER    => true,                        // return web page
				CURLOPT_HEADER			      => false,                       // return headers 
				CURLOPT_FOLLOWLOCATION	  => true,                        // follow redirects
				CURLOPT_ENCODING		      => "",                          // handle all encodings 
				CURLOPT_AUTOREFERER       => true,                        // set referer on redirect 
				CURLOPT_CONNECTTIMEOUT    => CURL_CONNECTION_TIMEOUT,     // timeout on connect ( seconds )
				CURLOPT_TIMEOUT           => CURL_DOWNLOAD_TIMEOUT,       // timeout on response ( seconds )
				CURLOPT_MAXREDIRS         => 5,                           // stop after # of redirects 
				CURLOPT_USERAGENT         => CURL_USER_AGENT
			);
			curl_setopt_array( $curl, $options );
			$content	= curl_exec( $curl );
			$err		  = curl_errno( $curl );
			$errmsg		= curl_error( $curl );
			$header		= curl_getinfo( $curl );
			curl_close( $curl );	
			
			// output data and results
			if( CRAWLER_OUTPUT_DOWNLOAD_MESSAGES ) {
  			echo "\n";
        foreach( $header as $key => $value )
				  echo "\n    $key: $value";
        echo "\n\n  - number of errors: $err";
        if( $err > 0 )
				  echo "\n  * error messages: $errmsg";
      }
			
			// set file download size
			$Webpage->size = $header['size_download'];
			
			// mark file as junk if any errors occured during download
			if( $err > 0 || strlen( $errmsg ) > 0 ) {
			  $Webpage->junk = TRUE;
				$Webpage->download_error = "download errors: $errmsg";
				return $Webpage;
      }
			
			// mark file as junk if content_type could not be determined.
			if( is_null( $header['content_type'] ) || $header['content_type'] == '' ) {
			  $Webpage->junk = TRUE;
				$Webpage->download_error = 'content type could not be determined';
				return $Webpage;
      }
			
			// seperate content type from encoding and set values
			$array = explode( ';', $header['content_type'] );
			$Webpage->content_type = trim( strtolower( $array[0] ) );
			if( isset( $array[1] ) )
				$Webpage->encoding = trim( str_replace( 'charset=', '', strtolower( $array[1] ) ) );
			
			// check if current url is different than final url downloader was directed to
			if( $Webpage->url != $header['url'] ) {
        // deal with redirects here some day
			}
			
			// store http status
			$Webpage->http_status = $header['http_code'];
			
			// mark all files that didn't return a http response of 200 as junk
			if( $header[ 'http_code' ] != 200 ) {
			  $Webpage->junk = TRUE;
				$Webpage->download_error = 'http response was ' . $header['http_code'];
				return $Webpage;
      }
			
			// mark all files that are over this->max_download_size as junk
			if( $header[ 'size_download' ] > CURL_MAX_DOWNLOAD_SIZE ) {
			  $Webpage->junk = TRUE;
				$Webpage->download_error = 'exceeded max download size of '.CURL_MAX_DOWNLOAD_SIZE.' bytes';
				return $Webpage;
      }
				
			// overwrite encoding if it's also set in html meta headers
			//
			//	1) the meta header encoding tends to be more reliable
			//
			$num_matches = preg_match( '/charset=([a-zA-Z0-9-]+)/', $content, $charset );
			if( isset( $charset[1] ) )
				$Webpage->encoding = strtolower( $charset[1] );
			
			// mark as junk if not an html page
			if( $Webpage->content_type != 'text/html' ) {
			  $Webpage->junk = TRUE;
				$Webpage->download_error = "$Webpage->content_type files currently ignored";
				return $Webpage;
      }
				
			// mark file as junk if url suggests it's an image or pdf, but it resolves to a content-type of text/html
			$extension = substr( $Webpage->url, -3 );
			if(
				$Webpage->content_type == 'text/html'
				&& (
					$extension == 'jpg'
					OR $extension == 'peg'
					OR $extension == 'png'
					OR $extension == 'gif'
					OR $extension == 'bmp'
					OR $extension == 'pdf'
				)
			) {
			  $Webpage->junk = TRUE;
				$Webpage->download_error = "non-html url resolved to content type text/html";
				return $Webpage;
      }
			
			// mark as junk if over max content size
			if( $header['download_content_length'] > CURL_MAX_HTML_DOWNLOAD_SIZE && $Webpage->content_type == 'text/html' ) {
			  $Webpage->junk = TRUE;
				$Webpage->download_error = 'over max html download size of '.CURL_MAX_HTML_DOWNLOAD_SIZE.' bytes';
				return $Webpage;
      }
			
			// mark as junk if this is the website root_url and we've been redirected to a different domain
			// *** deal with this later - don't care now
			//
			
			// store content to class
			$Webpage->html = $content;
      
      return $Webpage;
      
		}
    
  } // end class

?>