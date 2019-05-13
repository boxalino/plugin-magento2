# CHANGELOG Boxalino Magento 2 plugin 

All further changes to the plugin will be added to the changelog.
On every plugin update - please check the file and what needs to be tested on your system.

If you have any question, just contact us at support@boxalino.com


### v1.6.2 - 2019-04-26
##### 1. Integration of Exporter Scheduler for delta-full exports
* *setup version* : 1.0.3
* *setup change* : added new column to _boxalino_export_ table to stash updated product IDs in between delta runs
* *description* : Exporter Scheduler is to be used to control the timing for delta exports: start-end hour for delta runs (ex: from 8am - 10pm), minimum time interval between 2 delta exports (in minutes) 
* *configuration path* : "Stores -> Configuration -> Boxalino -> Exporter", tab "Scheduler". 
* *updates* :  
1. The delta exports will only run in the configured time interval
2. The full exporter can stop the delta exporter if it is running
3. New event listener on catalog_category_save_after (to track product updates)
4. Boxalino/Intelligence/Model/Indexer/BxIndexer has been removed
5. Transactions exporter has been removed.

**Default - "no"**: by default, the scheduler is disabled as crons are usually managed by the server.

##### 2. Indexer log messages prefix update
* *description* : The exporter logs prefix has been changed from "bxLog" to "BxIndexLog" 

##### 3. Throw LogicException on exporter server connection timeout
* *description* : For the configured _Boxalino Response Wait Time_ parameter, a logic exception will be fired (instead of LocalizedException).
This is a normal accepted flow (the Boxalino DI server sends response once the archive was processed, which is longer than most server timeouts)

### v1.6.0 - 2019-04-05
###### 1. Is numeric validation on collection offset
Page number can not be non-numeric (input required by the solr engine). The Magento default will be used as a fallback strategy.
