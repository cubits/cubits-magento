cubits-magento
================

Accept Bitcoin on your Magento-powered website with Cubits.

Download the plugin in our shopping cart section.

Installation
-------

Download the plugin, initialise the submodule and copy the 'app' folder to the root of your Magento installation.

If you don't have a Cubits account, sign up at https://cubits.com/merchant.


Custom events
-------

The plugin sends two events - 'cubits_callback_received' when a callback is received, and 'cubits_order_cancelled' when an order is cancelled. You can use these events to implement custom functionality on your Magento store.

cubits-php
================
from the root of the plugin

1. git submodule init

2. git submodule update
