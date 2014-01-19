Website Data Extractor
======================

### A utility That
- takes a .csv of urls as an input
- crawls the sites
- extracts basic info about each site ( including contact pages that exist )
- puts all data into a csv for bulk import into Insightly


### Using This Tool

- make sure your settings are correct ( config.php )
- put a list of company names and urls into the targets.csv file.
  - names in column a and urls in column b
  - generally excel fucks shit up when creating csvs, so use numbers or some other basic csv editor ( or just a text editor )
- execute scrape_targets.php
  - this will crawl all sites, extract data, and push the contents into results.csv
- insightly.csv can be imported into insightly to create organizations to target
  - go to the organizations tab
  - click to import on right, near the top
  - choose the results.csv file
  - go to town