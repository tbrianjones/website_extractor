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
      $this->scrape_phones();
      
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
  					$this->Webpage->add_local_link( $url );
  					
  					$num_links++;
  						
  				}
  				
  			}
  			
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