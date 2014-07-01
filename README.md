#Riskified Magento extension

##Overview##
This extension allows for automatic and/or manual submission of purchase orders to Riskified for fraud review and guarantee.

If you don't have an existing account, please start by signing up to Riskified [here](www.riskified.com) - it's free and takes just a few minutes.

##Features##

* Automatic/manual submission of orders to review.
* Order cancellation also excludes it from review.
* Magento order status reflects Riskified's review decision.
* Includes a **Sandbox Environment** option for testing and integration.


##Installation##

Depends on Riskified's [PHP SDK](https://github.com/Riskified/php_sdk). If installing manually (without Magento Connect), you'll need to `git clone` the [Repository](https://github.com/Riskified/php_sdk) into `lib/riskified_php_sdk` under your Magento directory.

The rest of the extension is deployed as usual (into `code` and `design` folders)