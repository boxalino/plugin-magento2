# boxalino Magento 2 plug-in
## Introduction

The boxalino Magento 2 plug-in, which was created for the release of Magento 2, is the successor of our boxalino Magento plug-in. 
This plug-in allows you to synchronize products from your Magento 2 shop with our Data Intelligence to generate personalized search results and recommendations. 
Thanks to the integration of our boxalino Client SDK it's now more convenient than ever to use and integrate our Thrift client in to your shop. 

## Installation

1. Copy the Boxalino folder with all files in your app/code folder (create the folder if it doesn't already exist).
2. Set chmod for Boxalino directory and files:
    * chmod 755 -R app/code/Boxalino
3. Upgrade with the module
	* run the command line (from your main magento folder): php bin/magento setup:upgrade
4. Update the administrator role:
    * System > Permissions > Roles > Administrators - Save Role
5. Indicate your account name and password in the Store -> Configuration -> Boxalion -> General
6. Run a full data sync (direct command line from your main magento folder): php bin/magento indexer:reindex boxalino_indexer
7. Delete all folders under /pub/static/frontend and /pub/static/_requirejs/frontend. 
    * run the command line: php bin/magento setup:static-content:deploy de_CH (language is optional).
8. Activate the search, facets, autocompletion and recommendations (one after the other).
9. Set up a an indexing cronjob, running at least one full index per day. Use the delta indexer if you want to update more than once per hour.

## Documentation

The documentation is currently being created, a link will be added soon.

## Contact us!

If you have any question, just contact us at support@boxalino.com