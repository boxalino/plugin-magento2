# Boxalino Magento 2 plug-in

## Introduction

Welcome to the Boxalino Magento 2.x plugin.

The Boxalino plugin for Magento enables you to easily and quickly benefit from all the functionalities of Boxalino Intelligence:

1. Boxalino Intelligent Search with auto-correction and sub-phrases relaxation
2. Faceted search with advanced multi-type facets (refinement criteria), including the capacity to create smart facets based on unstructured textual content with our text-mining capacities and soft-facets to boost best scoring products with our unique smart-scoring algorithms.
3. Boxalino Autocomplete with advanced textual and product suggestion while you are typing (even if you type it wrong)
4. Boxalino Recommendations for real-time personalized product suggestions
5. Boxalino Optimization platform to improve step-by-step your online sales performance thanks to our revolutionary self-learning technology based on statistical testing of marketing strategies (a/b testing, self-learning clusters, and much more)

The Boxalino plugin for Magento pre-integrates the most important key technical components of Boxalino (so you don't have to):

1. Data export (including products, customers and transaction exports for multi-shops with test and live accounts and supporting regular delta synchronizations)
2. Boxalino tracker (pre-integration of Boxalino JavaScript tracker, our own tracker which follows strictly the Google Analytics model).
3. Search, Autocomplete and layered navigation (faceted navigation) with all intelligence functionalities pre-integrated (auto-correction, sub-phrases relaxation, etc.)
4. Similar and Complementary recommendations on product page and cross-selling on basket (cart) page
5. Layered navigation, to let Boxalino optimize the entire product navigation on your web-site

In addition, it is very easy to extend this pre-installed set-up to benefit from the following additional features:

1. Recommendations everywhere (easy to extend recommendations widgets on the home page, category pages, landing pages, content pages, etc.).
2. Quick-finder to enable new ways to find product with simple search criteria and compine it with soft-facets with our unique smart-scoring capacities (see an example here with the gift-finder of www.geschenkidee.ch).
3. Personalized newsletter & trigger personalized mail (use the base of data export and tracking from our plugin to simply integrate personalized product recommendations in your e-mail marketing activities and push notifications on your mobile app)
4. Advanced reporting to integrate any learnings and analysis of your online behaviors in other Business Intelligence and Data Mining projects with our flexible Reporting API functionalities

If you need more information on any of these topics, please don't hesitate to contact Boxalino at sales@boxalino.com. We will be glad to assist you!

N.B.: This project is for Magento 2, in case you need our plugin for Magento 1, please go to https://github.com/boxalino/plugin-magento1-v2)

## Installation

1. Download the archive and extract to app/code/Boxalino/Intelligence (create the folder if it doesn't already exist) or via composer:
	```
	composer require boxalino/plugin-magento2
	```
2. Set chmod for Boxalino directory and files:
	```
	chmod 755 -R app/code/Boxalin
	```
3. Upgrade with the module.
	```
	php bin/magento setup:upgrade
	```
4. Update the administrator role:
    * System > Permissions > Roles > Administrators - Save Role
5. Indicate your account name and password in the Store -> Configuration -> Boxalion -> General
6. Run a full data sync (direct command line from your main magento folder): php bin/magento indexer:reindex boxalino_indexer
7. Delete all folders under /pub/static/frontend and /pub/static/_requirejs/frontend and deploy static view files.
	```
	php bin/magento setup:static-content:deploy
	```
8. Activate the search, facets, autocompletion and recommendations (one after the other).
9. Set up a an indexing cronjob, running at least one full index per day. Use the delta indexer if you want to update more than once per hour.

## Documentation

The latest documentation is available upon request.

## Contact us!

If you have any question, just contact us at support@boxalino.com