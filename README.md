Website Data Extractor
======================


### A Utility That
- takes a .csv containing the following as input
  - id
  - website name ( company name that maps to this domain, usually )
  - url ( http://www.website.com )
- crawls these sites
- extracts basic info about each site
  - emails
  - addresses
  - trigger terms
- puts extracted data into .csvs


### Using This Tool
- make sure your settings are correct ( config.php )
- put a list of website ids, names, and urls into the `targets.csv` file
  - make sure it has linux line endings ( \n )
    - or make sure you update the line endings setting in `config.php`
  - generally excel fucks shit up when creating csvs, so use numbers or some other basic csv editor ( or just a text editor )
- execute scrape_targets.php
  - this will crawl all sites, extract data, and push the contents into results.csv


### Addresses and Geocoding
- this tool uses the public "Data Science Toolkit API" by default
- If you run lots of addresses through it, it will block you ( dunno specific limits )
- See the submodule ( https://github.com/tbrianjones/data_science_toolkit_php_api_client ) to learn more about the Data Science Toolkit and running your own server of it for mass geocoding.

  
### Dev Notes
- Improve HTML Scraper to allow crawling of sites with JS redirects and Frames
  - recycle cortex crawler html_file_processor code for stuff below
	- add frame scraper for links
		- store links a 
	- add javascript redirects scraping
		- store as regular link
	- combine all link scraping into one link scrape method
		- regular links, redirect, and frame