<?php

  class Html_scraper {
    
    public $Webpage;
    private $Dom;
    
    // link data
    //
		//  1) decided to limit urls to 256 chracters ( this->max_url_length ) just because longer ones tend to be junk 
		//  2) 2083 is the max functional url length allowed by any browser ( internet explorer )
		//
		var $max_url_length     = 255;  // max url length we allow in our data
		var $max_links_to_save  = 100;  // max links to save per page
  
    public function go( webpage $Webpage ) {
      
      $this->Webpage = $Webpage;
      
      // create dom object ( *requires the php xml library - AWS Linux: sudo yum install php-xml )
			$this->Dom = new DOMDocument();
			@$this->Dom->loadHTML( $this->Webpage->html ); // @ hides errors for malformed html			
      
      // extract data
      $this->scrape_links();
      $this->scrape_emails();
      $this->scrape_phones();
      $this->scrape_addresses();
      
      // return updated webpage object
      return $this->Webpage;
      
    }
  
  // --- LINK SCRAPERS --------------------------------------
  
  
    //  extract links
    //
    //  - stores links in $this->internal_links
		//
		function scrape_links() {
			
			echo "\n\n--- SCRAPING AND PROCESSING LINKS";
			
			// get all <a> elements from file
			//
			//	*** don't eliminate duplicate links because we will lose anchor text that
			//		will be found in subsequent links to the same file later on the page
			//
			$links[0] = $this->Dom->getElementsByTagName( 'a' );
			$links[1] = $this->Dom->getElementsByTagName( 'A' );
						
			foreach( $links as $elements )
			{
				
  			if( $elements->length > 0 ) {
  			
  				$i = 0;
  				$num_links = 0;
  				$count = $elements->length;
  				foreach ( $elements as $element ) {
  					
  					$i++;
  					if( CRAWLER_OUTPUT_LINK_MESSAGES )
    					echo "\n\n -- PROCESSING LINK $i OF $count";
  					
  					// stop processing links if we've processed the max specified
  					if( $num_links > $this->max_links_to_save ) {
  						if( CRAWLER_OUTPUT_LINK_MESSAGES )
    					  echo "\n\n ** max links processed - remaining links have been skipped";
  						break( 1 );
  					}						
  					
  					// extract href, filter url and clean it
  					$url = $element->getAttribute( 'href' );
  					$url = $this->filter_url( $url );
  					if( $url === TRUE )
  						continue;
  					
  					// save link - it has passed all the filters
  					if( CRAWLER_OUTPUT_LINK_MESSAGES )
    					echo "\n  - link saved: $url";
  					$this->Webpage->local_links[] = $url;
  					
  					$num_links++;
  						
  				}
  				
  			}
  			
  		}	
							
		}		
    
    // add emails to emails table
		//
		function scrape_emails() {
		
			echo "\n\n--- SCRAPING AND SAVING EMAILS";
			
			// scrape for email address with regex
			//	- http://www.regular-expressions.info/email.html
			$regex = '/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i';
			$results = preg_match_all( $regex, $this->Webpage->html, $emails );
						
			// at least one email was found
			if( $results > 0 ) {
				$emails = $emails[0];
				foreach( $emails as $email ){
				  echo "\n  - email: $email";
				  $this->Webpage->emails[] = $email;
				}
			}
			
			// no emails were found in this file
			else
				echo "\n  - no emails were found in this file";
		
		}
		
		// get addresses
		//
		function scrape_addresses()
		{
		
			echo "\n\n--- SCRAPING ADDRESSES";
						
			// extract addresses from file contents
			//
			//	- some inspiration:
			//	- http://stackoverflow.com/questions/11160192/how-to-parse-freeform-street-postal-address-out-of-text-and-into-components
			//	- http://stackoverflow.com/questions/18730947/regex-to-match-a-limited-number-of-characters-including-an-unlimited-number-of-w?noredirect=1#comment27603970_18730947
			//
			// (\d{2,5}|post office|p\.?\s?o\.?)(\s*(?:\S\s*){10,100})
			$regex = "/\b(p\.?\s?o\.?\b|post office|\d{2,5})\s*(?:\S\s*){8,50}(AK|Alaska|AL|Alabama|AR|Arkansas|AZ|Arizona|CA|California|CO|Colorado|CT|Connecticut|DC|Washington\sDC|Washington\D\.C\.|DE|Delaware|FL|Florida|GA|Georgia|GU|Guam|HI|Hawaii|IA|Iowa|ID|Idaho|IL|Illinois|IN|Indiana|KS|Kansas|KY|Kentucky|LA|Louisiana|MA|Massachusetts|MD|Maryland|ME|Maine|MI|Michigan|MN|Minnesota|MO|Missouri|MS|Mississippi|MT|Montana|NC|North\sCarolina|ND|North\sDakota|NE|New\sEngland|NH|New\sHampshire|NJ|New\sJersey|NM|New\sMexico|NV|Nevada|NY|New\sYork|OH|Ohio|OK|Oklahoma|OR|Oregon|PA|Pennsylvania|RI|Rhode\sIsland|SC|South\sCarolina|SD|South\sDakota|TN|Tennessee|TX|Texas|UT|Utah|VA|Virginia|VI|Virgin\sIslands|VT|Vermont|WA|Washington|WI|Wisconsin|WV|West\sVirginia|WY|Wyoming)(\s+|\&nbsp\;|\<(\S|\s){1,10}\>){1,5}\d{5}/i";
			$results = preg_match_all( $regex, $this->Webpage->html, $addresses );
			if( $results > 0 ) {
				
				// get the addresses from the preg match array
				$addresses = $addresses[0];
				
				// remove duplicates so we only count each address once per page
				
				// process all addresses
				foreach( $addresses as $address ) {
					
					// clean up address
					$address = html_entity_decode( $address );
					$address = str_replace( ',', ', ', $address ); // add a space after commas
					$address = preg_replace( '/\&[^\;]{1,5}\;/', ' ', $address ); // remove entities not converted
					$address = preg_replace( '/<[^>]+>/', ' ', $address ); // remove html
					$address = preg_replace( '/[^a-zA-Z0-9\.\-\,]/', ' ', $address );
					$address = preg_replace( '/\s+/S', ' ', $address ); // remove multiple spaces
					
					// remove multiple numbers at the beginning
					$i = -1;
					$parts = explode( ' ', $address );
					foreach( $parts as $part ) {
						if( is_numeric( trim( $part ) ) )
							$i++;
						else
							break;
					}
					if( $i > 0 ) {
						$p = 0;
						while( $i > $p ) {
							unset( $parts[$p] );
							$p++;
						}
						$address = implode( ' ', $parts );
					}
					
					// store address
					$this->Webpage->addresses[] = $address;
					
				}
				
			} else {
				echo "\n  - no addresses were found in this file";
			}

		}
		
		// get phone numbers
		//
		function scrape_phones() {
						
			echo "\n\n\n--- SCRAPING PHONES ---";
						
			// extract phones from file contents
			//
			//	- some inspiration:
			//	- http://stackoverflow.com/questions/123559/a-comprehensive-regex-for-phone-number-validation
			//
			$regex = "/(\s|\b)(\(\d{3}\)|\d{3})(\s|\-|\.)\d{3}(\s|\-|\.)\d{4}\b/";
			$results = preg_match_all( $regex, $this->Webpage->html, $phones );
			if( $results > 0 ) {
				
				// get the emails from the preg match array
				$phones = $phones[0];
								
				// process all phones
				foreach( $phones as $phone ) {
					
					// look for fax
					$fax = 0;
					$pos = -1;
					$matches = array();
					while( $pos = strpos( $this->Webpage->html, $phone, $pos + 1 ) ) {
						$match = substr( $this->Webpage->html, $pos - 50, strlen( $phone ) + 50 );
						$match = html_entity_decode( $match );
						$match = preg_replace( '/<[^>]+>/', ' ', $match ); // remove html
						$match = preg_replace( '/\s+/S', ' ', $match );
						if( stripos( $match, 'fax' ) ) {
							if( stripos( $match, $phone ) - stripos( $match, 'fax' ) < 12 )
								$fax = 1;
						}
					}
										
					// clean phone number
  				$phone = preg_replace( '/[^0-9]/', '', $phone );
          $phone = substr( $phone, 0, 3 ) . '-' . substr( $phone, 3, 3 ) . '-' . substr( $phone, 6, 4 );	
          
          if( $fax )  
            $this->Webpage->faxes[] = $phone;
          else
					  $this->Webpage->phones[] = $phone;
					  
				}
				
			} else {
				echo "\n  - no phones were found in this file";
			}

		}
				
    
  // --- UTILITIES TO CLEANSE AND FILTER URLS -------------------------------------------
		
				
		// filter and clean up url
		//
		//	1) pass a non-absolute url
		//
		//	2) filter it, and process it into the absolute url
		//
		//	3) return TRUE if the url failed a filter
		//
		//	4) return the absolute url if the url passed all filters
		//
		private function filter_url( $url ) {
			
			// skip link if it contains the word 'javascript'
			if( strpos( $url, 'javascript' ) !== FALSE ) {
				if( CRAWLER_OUTPUT_LINK_MESSAGES ) {
  				echo "\n\n  * link skipped because url contains javascript";
          echo "\n    url: $url";
        }
				return TRUE;
			}
			
			// get absolute url
			$url = $this->get_absolute_url( $url, $this->Webpage->url );
			
			// clean url
			$url = $this->clean_url( $url );
			
			// make sure link isn't too long
			if( strlen( $url ) > $this->max_url_length ) {
				if( CRAWLER_OUTPUT_LINK_MESSAGES ) {
				  echo "\n\n  * link skipped because url exceeded $this->max_url_length characters";
          echo "\n    url: $url";
				}
				return TRUE;
			}
			
			// check url for multiple question marks
			str_replace( '?', '?', $url, $occurances );
			if( $occurances > 1 ) {
			  if( CRAWLER_OUTPUT_LINK_MESSAGES ) {
  				echo "\n\n  * link skipped because url contains multiple question marks";
  				echo "\n    url: $url";
        }
				return TRUE;
			}
			
			// check url for repeat folders
			//
			//	1) eg. http://www.domain.com/home/home/home/images/17.jpg
			//
			$uri = parse_url( $url, PHP_URL_PATH );
			$strings = explode( '/', $uri );
			foreach( $strings as $key => $string ) {
				// remove empty values from array
				if( $string == '' )
					unset( $strings[ $key ] );
			}
			$num_strings = count( $strings );
			$num_unique_strings = count( array_unique( $strings ) );
			if( $num_strings != $num_unique_strings )
			{
				if( CRAWLER_OUTPUT_LINK_MESSAGES ) {
  				echo "\n\n  * link skipped because uri contains repeat folders";
  				echo "\n    url: $url";
        }
				return TRUE;
			}
			
			// parse current file url and the url we are filtering
			$parsed_file_url = parse_url( $this->Webpage->url );
			$parsed_url = parse_url( $url );
			
			// filter if no host is found
			if (
				! isset( $parsed_file_url[ 'host' ] )
				OR ! isset( $parsed_url[ 'host' ] )
			) {
				if( CRAWLER_OUTPUT_LINK_MESSAGES ) {
	  			echo "\n\n  * link skipped because a host could not be determined from the url";
          echo "\n    url: $url";
        }
				return TRUE;
			}
			
			// add link to links table ( the filters below this method call apply only to internal links )
			/* TBJ not processing external links right now
			$this->add_external_link( $url, $anchor_text );*/
			
			// filter if scheme doesn't match this files scheme ( http, ftp, etc. )
			//
			//	*** filtering this way removes lots of duplicate pages
			//		even though we lose some good pages
			//
			//	- ignoring the advice above that this filters junk, tbj changed this so http and https
			//		are ok and interchangable, meaning we store links when the crawled page is http and
			//		the found link is https ( and vice versa ) - 9/9/13
			//
			if(
				$parsed_file_url[ 'scheme' ] != $parsed_url[ 'scheme' ]
				AND $parsed_file_url[ 'scheme' ] != $parsed_url[ 'scheme' ] . 's'
				AND $parsed_file_url[ 'scheme' ] . 's' != $parsed_url[ 'scheme' ]
			) {
				if( CRAWLER_OUTPUT_LINK_MESSAGES ) {
  				echo "\n\n  * link skipped because url had a different scheme ( http vs ftp )";
          echo "\n    url: $url";
        }
				return TRUE;
			}
			
			// filter if the domain is different than this file's domain
			//
			//	- www.domain.com and products.domain.com are treated as the same when using $this->get_domain_from_url
			//	- www.domain.com and www.website.com are treated as different and skipped
			//
			if( $this->get_domain_from_url( $this->Webpage->url ) != $this->get_domain_from_url( $url ) ) {
				if( CRAWLER_OUTPUT_LINK_MESSAGES ) {
  				echo "\n\n  * link skipped because url is on a different domain";
          echo "\n    url: $url";
        }
				return TRUE;
			}

			// filter if the domain is on a different subdomain
			//
			//	- www.domain.com and products.domain.com are treated as different and skipped
			//
			//	- www.domain.com and domain.com are treated as the same and not skipped, as this is almost always the case
			//
			//	- filtering different subdomains loses a lot of good data, but also skips a lot of bad data
			//
			//	- tbj turned this off - 9/9/13	 - it's a trade off between crawling more than we should when
			//		a company provided a subdomain, and losing lots of good data about a company that happens to
			//		be on a different subdomain
			//
			/*$file_url_host_without_www = preg_replace( '/^www\./', '', $parsed_file_url['host'] );
			$url_host_without_www = preg_replace( '/^www\./', '', $parsed_url['host'] );
			if( $file_url_host_without_www != $url_host_without_www ) {
				echo "\n\n  * not added to files table because url is on a different subdomain";
				echo "\n    url: $url";
				return TRUE;
			}*/			
			
			// return the absolute url because it passed all filters
			return $url;
			
		}
		
		
		// clean up url for insertion into the database
		//
		private function clean_url( $url )
		{
			
			// eliminate all hashes and the chracters that follow the hash from urls
			//
			//  1) these are creating duplicate pages in the files table
			//	   since hashes are generally just internal page links
			//
			$array = explode( '#', $url );
			$url = $array[0];
			
			// trim leading and trailing white space
			$url = trim( $url );
			
			// trim ?'s and #'s
			//
			//  1) these create infinite urls sometimes ( eg. .com#, .com##, .com### )
			//
			$url = trim( $url, '?' );
			
			// replace spaces with proper encoded character ( ' ' = %20 )
			$url = str_replace( ' ', '%20', $url );
									
			// return clean string
			return $url;
			
		}
		
		// converts a relative uri to an absolute url
		//
		//  1) $rel is the url from within html
		//
		//  2) $base is the url of the current page being processed
		//
		//  3) tutorial: 
		//
		//  *** returns an empty string when parse_url() fails to parse $rel
		//
		//  *** this function returns a complete absolute url
		//      DO NOT FUCK WITH THIS METHOD --> apply filters to urls in html_processor::clean_url()
		//
		private function get_absolute_url( $rel, $base )
		{
						
			// check for a scheme ( eg. http ) and return the passed url if it's already absolute
			if( parse_url( $rel, PHP_URL_SCHEME ) != '' )
				return $rel;
			
			// concat passed uris and return that if $rel is just query perameters
			else if ( @$rel[0] == '#' || @$rel[0] == '?' )
				return $base.$rel;
			
			// store parsed url into an array
			extract( parse_url( $base ) );
			
			// tbj edit: set path = '' when path isn't set by parse_url()
			//  1) this happens with root urls ( eg. http://www.domain.com )
			if( ! isset( $path ) )
				$path = '';
			
			$abs = ( @$rel[0] == '/' ? '' : preg_replace( '#/[^/]*$#', '', $path ) ) . "/$rel";
			$re  = array( '#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#' );
			
			for ( $n = 1; $n > 0; $abs = preg_replace( $re, '/', $abs, -1, $n ) );
			return $scheme . '://' . $host.str_replace( '../', '', $abs );
			
		}
		
		// returns the domain of a url
		//
		//	examples:
		//		- www.domain.com returns domain.com
		//		- products.domain.com returns domain.com
		//
		private function get_domain_from_url(
			$url
		) {
			$parsed_url = parse_url( $url );
			$host_array = explode( '.', $parsed_url['host'] );
			$host_array = array_reverse( $host_array );
			$domain = $host_array[1] . '.' . $host_array[0];
			return strtolower( $domain );
		}
    
  } // end class

?>