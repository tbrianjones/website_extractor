website_extractor
=================

A utility that takes a .csv of urls and crawls the sites, extracting basic info about the site and any contact pages that exist.

### Using It
- put a list of company names and urls into the targets.csv file.
  - names in column a and urls in column b
  - generally excel fucks shit up ( encoding, et al ), so try to use numbers
  
- execute scrape_targets.php
  - this will crawl all sites, extract data, and push the contents into insightly.csv
  
- insightly.csv can be imported into insightly to create organizations to target