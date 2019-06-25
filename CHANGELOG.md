# CHANGELOG Boxalino Magento 2 plugin 

All further changes to the plugin will be added to the changelog.
On every plugin update - please check the file and what needs to be tested on your system.

If you have any question, just contact us at support@boxalino.com


### v1.6.5 - 2019-06-24
* *test* : *php bin/magento setup:di:compile* required
##### 1. Exporter update on Configurable products status
* *description* : Per default M2 behavior, the configurable product must appear disabled if the children are disabled
* *commits* : https://github.com/boxalino/plugin-magento2/commit/1bfa90cda7d1252f9df9a3b7e3b224886ce64db1

### v1.6.4 - 2019-06-14
* *post-deploy step* : *php bin/magento setup:di:compile* required
##### 1. Exporter Scheduler time-range comparison with store time
* *description* : The configured time for Exporter Scheduler should be set to store local time. The exporter run hour/times are saved in UTC.
Logs updated to reflect both UTC and store locale time.
* *commits* : https://github.com/boxalino/plugin-magento2/commit/be5bcdd9d782997e206ebee60a1343138e3a18fa


### v1.6.3 - 2019-06-12
##### 1. .gitignore update
* *description* :  The package can be retrieved via a git pull after a composer install
##### 2. Exporter service timeout response processed as logic exceptions
* *description* : The DI requests for account validation, XML publish and archive export can return a timeout when the DI service is busy.
This does not mean the process failed so the use-cases have been treated as warning/infos when debugging is needed.

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
