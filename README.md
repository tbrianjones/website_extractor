Website Data Extractor
======================


What does this utility do?
--------------------------
- crawls urls that are stored in a database
  - currenlty setup to be a remote amazon mysql rds
- urls are read from this database and put into an amazon sqs queue
- these urls are read from the queue and crawled / scraped
- basic info is extracted from each website and stored in the database
  - right now it's just pulling emails


Using This Tool
---------------
- create `config.php` from `config.example.php`
- make sure your settings are correct in `config.php`
- make sure the remote database is created using the 'database.sql' file
  - then populate with urls to process
- `scrape_target.php` is the file used to process each website
- data will be written to the database

### running one site at a time
- simply execute `scrape_target.php` and one url will be processed
  - the sqs queue will also be populated
  - subsequent scrapes will use the queue, until it runs out. then it will be repopuulated
  
### running processes in parallel
- install supervisord
  - create a symlink from `/etc/supervisord.conf` to `BASE_PATH/external_configs/supervisord.conf`
  - `sudo ln -s /data/extractor/external_configs/supervisord.conf /etc/supervisord.conf`
- adjust the settings in `external_configs/supervisord.conf` to run the desired number of processors
- run supervisord
  - /usr/bin/supervidors
  - then turn on the crawlers with `/usr/bin/supervisorctl`
  
### notes on parallel processing
- the system should ahve no problem if `scrape_target.php` is killed, or has an error, or a server dies
- data is written to a remote db
- targets in the queue will be picked up later if they expire
- companies that are marked as queued for processing in the db will be picked up later if they failed after the message expiration time of the queue.
- use spot instances to save money ( they can die and everything should be fine )
  
  
Dev Notes
---------
- Improve HTML Scraper to allow crawling of sites with JS redirects and Frames
  - recycle cortex crawler html_file_processor code for stuff below
	- add frame scraper for links
		- store links a 
	- add javascript redirects scraping
		- store as regular link
	- combine all link scraping into one link scrape method
		- regular links, redirect, and frame