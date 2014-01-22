<?php

  // run this to extract edc names and urls
  //
  //  - we lose ~5% to bad formatting on their site
  //
  //  - must update the html ( copy source for result page ) into listing_page.html
  //  - must update the table data widths below
  //
  $td_width_1 = 462;
  $td_width_2 = 384;

  // load html and extract matches
  $html = file_get_contents( 'listing_page.html' );
  $regex = '/(<td width="'.$td_width_1.'|<td width="'.$td_width_2.').{0,250}http.{0,250}(<\/font>|<\/td>)/s';
  preg_match_all( $regex, $html, $matches );
  $matches = $matches[0];
  
  // process each match
  $results_array = array();
  foreach( $matches as $match ) {
    
    // get url
    $i = strpos( $match, 'http://' );
    $f = strpos( $match, '">', $i );
    $url = substr( $match, $i, $f-$i );
    $parts = parse_url( $url );
    $url = $parts['scheme'].'://'.$parts['host'];
    $url = trim( $url );
    
    // get org name
    $i = $f+2;
    $f = strpos( $match, '</a>', $i );
    $name = substr( $match, $i, $f-$i );
    $name = strip_tags( $name );
    $name = html_entity_decode( $name );
    $name = preg_replace("/[^a-z0-9 .]+/i", " ", $name );
    $name = preg_replace("/[ \t]+/", " ", $name );
    $name = trim( $name );
    
    // save results to array
    if(
      $url != ''
      AND $name != ''
      AND stripos( $url, 'census.gov' ) === FALSE
    )
      $results_array[] = '"'.$name.'","'.$url.'"';
    
  }
  
  // save results to csv
  file_put_contents( 'results.csv', implode( "\n", $results_array ) );
  
?>