# CHANGELOG Boxalino Magento 2 plugin 

All further changes to the plugin will be added to the changelog.
On every plugin update - please check the file and what needs to be tested on your system.

If you have any question, just contact us at support@boxalino.com

### Version History
**[v3.0.0: 2020-10-02](#v3.0.0)**<br>
**[v2.8.9: 2020-09-10](#v2.8.9)**<br>
**[v2.8.8: 2020-08-26](#v2.8.8)**<br>
**[v2.8.7: 2020-08-14](#v2.8.7)**<br>
**[v2.8.0: 2020-05-18](#v2.8.0)**<br>
**[v2.7.0: 2020-05-13](#v2.7.0)**<br>
**[v2.6.4 : 2020-03-23](#v2.6.4)**<br>
**[v2.6 : 2020-02-11](#v2.6)**<br>
**[v2.0.0 : 2020-01-15](#v2.0.0)**<br>
**[v1.6.7 : 2019-10-10](#v1.6.7)**<br>
**[v1.6.6 : 2019-06-10](#v1.6.6)**<br>
**[v1.6.5 : 2019-06-24](#v1.6.5)**<br>
**[v1.6.4 : 2019-06-14](#v1.6.4)**<br>
**[v1.6.3 : 2019-06-12](#v1.6.3)**<br>
**[v1.6.2 : 2019-04-26](#v1.6.2)**<br>

<a name="v3.0.0"></a>
### v3.0.0 : 2020-10-02
_post-deployment integration test_: test for the addToBasket TEMPLATE event; test for overlays; delta export test;

1. Delta Export Updates
* _description_ : The delta can be run either via Magento2 mview, cronjob or cli. When exported via cronjob/cli - the updated_at field is the base for check. The product parent & children are exported alike.
* _commit_ :  https://github.com/boxalino/plugin-magento2/commit/11d07bba091ccdfeb916f0926c13a70d80ac8d16
https://github.com/boxalino/plugin-magento2/commit/42b532bec217e50ac51d87b673c7c8930d629107
https://github.com/boxalino/plugin-magento2/commit/a9a46a1ceac572586cffdb45c795fc30e19c7e38

2. Tracker Updates
* _description_ : The server-side addToBasket event is always tracked; JS addToBasket track via template Boxalino_Intelligence::script.phtml
* _commit_ :  https://github.com/boxalino/plugin-magento2/commit/51046812970ba45e011ec43e85efca4ac0ac1e73 

3. Customer Export Fixes
* _commit_ :  https://github.com/boxalino/plugin-magento2/commit/85d5ee91863e0022f54703e720f171f782f86668


<a name="v2.8.9"></a>
### v2.8.9 : 2020-09-10
_post-deployment integration test_: test for the discountedPrice and system price values for different products types; 

1. Extended price export logic
* _description_ : 2 new fields added for price export which reflect the Magento2 indexed content (min_price and final_price). The logic for discountedPrice has been changed as well to reflect the use of min_price when set.
* _commit_ :  https://github.com/boxalino/plugin-magento2/commit/f0336a9836b2678ec398c4a561504f8072e4d6e5


<a name="v2.8.8"></a>
### v2.8.8 : 2020-08-26
_post-deployment integration test_: test for the addToBasket event to be triggered from all the templates that display products; 

1. Disable the "addToBasket" server-side event when the Narrative JS is enabled
* _description_ : The "addToBasket" event is no longer triggered server-side for the Narrative JS.  Manual integration required in the e-shop theme. The addToBasket event has to be integrated by the client.
* _commit_ :  https://github.com/boxalino/plugin-magento2/commit/086997d8d39484754adccf21895963fbf7d0befc

2. *addToBasket* track event for Narrative JS
* _description_ : The script.phtml template has _guideline logic_ on how the **addToBasket** event can be fired; 
* _integration_ : If your theme allows to buy configurable/grouped-products from the listing - the JS trigger requires updates.
* _commit_ :  https://github.com/boxalino/plugin-magento2/commit/6639086043b2d5d8c0653e8227296d7c3980cab0

3. Delta exporter fix for the batch size
* _description_ : The batch size for delta set to 100.000; can be rewritten by the client.
* _commit_ : https://github.com/boxalino/plugin-magento2/commit/a897f8a5fd1625cea7c6472fa8760415eb81e852

<a name="v2.8.7"></a>
### v2.8.7 : 2020-08-14
_post-deployment integration test_: test for the addToBasket event to be triggered from all the templates that display products; 

* _description_ : Improvements on how the addToBasket is tracked within/outside Boxalino response templates.  Manual integration required in your e-shop theme. The addToBasket event has to be integrated by the client.
* _integration steps_ : _the list block and add-to-basket button requires to be updated_

1. Follow the sample markup showcasing 1 container with 1 item. If prior integration was done, update the _add to basket button_:
```
<div class="bx-narrative" data-bx-variant-uuid="<?php echo $this->getRequestUuid();?>" data-bx-narrative-name="products-list" data-bx-narrative-group-by="<?php echo $this->getRequestGroupBy();?>">
    <div class="bx-narrative-item" data-bx-item-id="<?php echo $product->getId();?>">
        /** product item **/ 
        /** product add to basket **/
        <div>
            <input type="text" class="bx-basket-quantity">
            <span class="bx-basket-currency">CHF</span>
            <span class="bx-basket-price">77</span>
            <button class="bx-basket-add">+</button>
        </div>
    </div>
</div>
```

If not all product blocks (recommendations, listing, etc) are returning Boxalino content, the following markup must be used for *detached* product items:

```
<div class="bx-narrative-item" data-bx-item-id="D">
    <a href="https://example.org/detached">
        <img src="https://placeimg.com/180/400/animals/detached">
        <p>Detached item</p>
    </a>
    <div>
        <input type="text" class="bx-basket-quantity">
        <span class="bx-basket-price">CHF 32</span>
        <button class="bx-basket-add">+</button>
    </div>
</div>
```

<a name="v2.8.0"></a>
### v2.8.0 : 2020-05-18
_post-deployment integration test_: test the custom XML/CMS recommendations sliders/blocks; 
enable the narrative tracker (as described in the integration steps); 
update your custom narrative templates

* _description_ : A new tracker system can be enabled which will allow Boxalino to track the actions of your user on predefined response blocks (navigation, search, recommendations). 
*Manual integration required in your e-shop theme.*

* _integration steps_ : 

*1*. Follow the sample markup showcasing 1 container with 1 item:
```
<div class="bx-narrative" data-bx-variant-uuid="<?php echo $this->getRequestUuid();?>" data-bx-narrative-name="products-list" data-bx-narrative-group-by="<?php echo $this->getRequestGroupBy();?>">
    <div class="bx-narrative-item" data-bx-item-id="<?php echo $product->getId();?>">
        /** product item **/ 
    </div>
</div>
```

The following theme templates require to include the above sample (bx-narrative/bx-narrative-item classes & data-attributes):
- Magento_Catalog::product/list.phtml (search, navigation)
- Magento_Catalog::product/list/items.phtml (cross-sell/up-sell/related/etc)

>Sample as done on _recommendation.phtml_:
https://github.com/boxalino/plugin-magento2/blob/master/view/frontend/templates/product/recommendation.phtml#L10
https://github.com/boxalino/plugin-magento2/blob/master/view/frontend/templates/product/recommendation.phtml#L15

>Sample as done on a _narrative_:
https://github.com/boxalino/plugin-magento2/blob/master/view/frontend/templates/journey/product/list.phtml#L7
https://github.com/boxalino/plugin-magento2/blob/master/view/frontend/templates/journey/product/view.phtml#L11

*2*. *Enable* the narrative tracker via configuration: Boxalino Extension->General->Narrative Tracker->Enable. Save, clear cache and test.

*3*. *Test*: Once the tracker is presumably set up correctly the testing process can be simplified by entering debug-mode.
Run the following script in your browser's console to enter debug-mode for the duration of the session.
         
 ```_bxq.push(['debugCookie', true]); ```        

*4*. Deploy your theme updates.

<a name="v2.7.0"></a>
### v2.7.0 - 2020-05-33
* *integration steps* : check out the templates rendering product collection; 
adapt them in your own theme if your project uses Boxalino blocks as visual element model
* *test* : narratives, product listing visual elements, simple product visual element
##### 1. Narrative blocks update (ProductList & ProductView)
* *description* : Allows easily extensibility of default plugin behavior.
* *commit* : https://github.com/boxalino/plugin-magento2/commit/33a4cd2908fc949a0160720a0b10c00bcfad00d2

<a name="v2.6.4"></a>
### v2.6.4 - 2020-03-23
* *test* : facets on navigation, search, narratives
##### 1. API DI for the Boxalino Adapter
* *description* : Allows easily extensibility of default plugin behavior.
* *commits* : https://github.com/boxalino/plugin-magento2/commit/437579fdadef9f6bc1f18e84cf53b9a94a61b6a7


<a name="v2.6"></a>
### v2.6 - 2020-02-11
* *test* : navigation, search, overlay, banners, recommendations, product status
##### 1. Allow empty search (returns full product collection)
* *description* : Allows empty search requests. Enable via Magento Configurations.
* *commits* : https://github.com/boxalino/plugin-magento2/commit/0af2b6365ea3d1d1c98aabc9e4fca845d6577e3a
https://github.com/boxalino/plugin-magento2/commit/984f347c949ef3a65b0668af7b492007df8b0fa1
##### 2. Exporter updates (category, product status, transactions)
* _description_ : Minor fixes for data exporter.
* *commits* : https://github.com/boxalino/plugin-magento2/commit/5082081c0bea828613576ef40d9d30bdaf8379f6
https://github.com/boxalino/plugin-magento2/commit/97b49c5498d63c164bd4e5e7da9fef0a31179db2
https://github.com/boxalino/plugin-magento2/commit/3317167a712c7835710e1610cc0f9d12b3bce2e5
https://github.com/boxalino/plugin-magento2/commit/3efdcecdc19c4a2606d9a75b277efc7509543a44
##### 3. New narrative requests logic
* _description_ : The new narrative origin block allows to group multiple narrative choices on a single page with isolated logic.
* _commits_ : https://github.com/boxalino/plugin-magento2/commit/039774e688f15c07b9aa94438eaf7a6632fb0d3d


<a name="v2.0.0"></a>
### v2.0.0 - 2020-01-15
* *test* : navigation, search, overlay, banners, recommendations
##### 1. Recommendations on a no-results search page
* *description* : If a search request has no product/blog matches, a widget is displayed. Enable via backend configurations Boxalino -> Recommendations -> No results
* *commits* : https://github.com/boxalino/plugin-magento2/commit/581148ca9ed55dae9ad28ec05151c8f98cb8474a
##### 2. Adding custom sorting options
* *configuration path* : "Boxalino -> Search-Navigation", tab "Advanced -> Custom sort option mapping"
* _description_ : If your system has created custom logic/fields for sorting, it has to be used. Map your system field (used for sorting) to a Boxalino field.
* *commits* : https://github.com/boxalino/plugin-magento2/commit/42d3a7b637c514600ce040f65adf79d47bab33e8
##### 3. User-Friendly view for debugging Boxalino responses
* _description_ : For developers - use &boxalino_response=true OR boxalino_request=true as an URL parameter to see the content requested/returned by the SOLR index as JSON.
##### 4. Minor adjustments for other interceptors integration
* _description_ : Disabling all features by default; Check for navigation context (on category view);


<a name="v1.6.7"></a>
### v1.6.7 - 2019-10-10
##### 1. XML configuration data-type update
* *commits* : https://github.com/boxalino/plugin-magento2/commit/c8f3956fe2599be61969c05df589c6b63f5e9607
##### 2. Overlay styles update for M2.3.3
* *commits* : https://github.com/boxalino/plugin-magento2/commit/e56a28edbfea2a589c655236ef6c5afc4ecd158e


<a name="v1.6.6"></a>
### v1.6.6 - 2019-07-10
* *test* : navigation, search, add to basket tracker
##### 1. PHP7.2, Magento2.3.2 compatibility fixes
* *description* : Tested M2.3.2 and PHP7.2 compatibility
* *commits* : https://github.com/boxalino/plugin-magento2/commit/fcef82879d3e282cf856e93dfb40dc4752986085
https://github.com/boxalino/plugin-magento2/commit/372000b4afbbc04538abb2a1d6ec42069d8095c6
##### 2. Add to cart tracker updates
* *description* : Update on add to cart tracker
* *commits* : https://github.com/boxalino/plugin-magento2/commit/c64de811f998471ea9ec53c6cd4d0937532403ab

<a name="v1.6.5"></a>
### v1.6.5 - 2019-06-24
* *test* : *php bin/magento setup:di:compile* required
##### 1. Exporter update on Configurable products status
* *description* : Per default M2 behavior, the configurable product must appear disabled if the children are disabled
* *commits* : https://github.com/boxalino/plugin-magento2/commit/1bfa90cda7d1252f9df9a3b7e3b224886ce64db1

<a name="v1.6.4"></a>
### v1.6.4 - 2019-06-14
* *post-deploy step* : *php bin/magento setup:di:compile* required
##### 1. Exporter Scheduler time-range comparison with store time
* *description* : The configured time for Exporter Scheduler should be set to store local time. The exporter run hour/times are saved in UTC.
Logs updated to reflect both UTC and store locale time.
* *commits* : https://github.com/boxalino/plugin-magento2/commit/be5bcdd9d782997e206ebee60a1343138e3a18fa

<a name="v1.6.3"></a>
### v1.6.3 - 2019-06-12
##### 1. .gitignore update
* *description* :  The package can be retrieved via a git pull after a composer install
##### 2. Exporter service timeout response processed as logic exceptions
* *description* : The DI requests for account validation, XML publish and archive export can return a timeout when the DI service is busy.
This does not mean the process failed so the use-cases have been treated as warning/infos when debugging is needed.

<a name="v1.6.2"></a>
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
