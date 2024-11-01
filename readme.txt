=== WR Price List Manager For Woocommerce ===
Contributors: yariko0529
Tags: woocommerce, discounts, price hiding, price, price list, b2b, b2c, price management, discount rules, bulk discount price
Requires at least: 6.0
Tested up to: 6.4.3
Stable tag: 1.0.8
Requires PHP: 5.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Edit prices quickly on a single screen. Create price lists and assign them to user roles. Import prices, hide prices and more.

== Description ==

WR Price List Manager has many features but what stands out is its price lists that can be assigned to any user role, making it easy to manipulate the prices of your products, create promotions or discounts in seconds.

1. Create Price Lists
Create a price list and assign it to any role. With this feature you can have multiple prices to the same product depending the user role.

2. Quick Price Update or One-by-One
Instead of going to update a product one by one, you can edit its price in the same screen

3. Dynamic Sales Price List(Premium)
Based-on Price List, you can create a price list based on other and start editing the price

4. Create New User Roles
Create as many user roles as needed with ease.

5. Hide prices and add-to-cart button
You can hide the prices and the add to the cart button for unregistered users. You can also define a custom message that will show where the price used to be,  you also can put a html meesage.

6. Create Item Price Lists from CSV(Premium)
Upload an CSV file to create price list on WooCommerce.

7. Export Price List to CSV(Premium)
Export your prices, edit and upload them again.

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/wr_price_list` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress

= Does this plugin work with newest WP version and also older versions? =
Yes, this plugin works really fine with WordPress 3.3!
It also works great with WP 5.0 , So you always should run the latest WordPress version for a lot of reasons.

== Screenshots ==

1. Quick Price Update (Ajax)
2. Price updated
3. Price By User Role
4. Create Price List and user roles.
5. Import price or update price, import a csv file with the product is and prices. Also you can create a price list from csv file.
6. Hide price an add to cart button, Choose a default price list for unregistered users / guest.

= Localization =
* English (default) - always included
* Spanish (partially - 80%)

== Installation ==

1. Upload the entire `wr_price_list` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to WR PRICE LIST item menu on the left panel and start creating and playing with your product price :)

== Frequently Asked Questions ==

= Does this plugin work with newest WP version and also older versions? =
Yes, this plugin works really fine with WordPress 4 and above
= Does this plugin have a premium version?
Yes, the plugin has premium and free features.
= Does the plugin have documentation?
Yes, you can find the documentation here https://www.webreadynow.com/docs-category/wr-price-list-manager/

== Changelog ==

= 1.0.0 =
* Initial release
= 1.0.1 =
* Choose the price format (Individual Price)
= 1.0.2 =
* Create discount with two decimal places like 0.708 = 70.8%
*Note:* All my plugins are localized/ translatable by default. This is very important for all users worldwide. So please contribute your language to the plugin to make it even more useful. For translating I recommend the awesome ["Codestyling Localization" plugin](http://wordpress.org/extend/plugins/codestyling-localization/) and for validating the ["Poedit Editor"](http://www.poedit.net/).
= 1.0.4 =
* New Admin UI based on vue
* Mysql query improvement
* Price List based on category removed.
= 1.0.5 =
* Parent list are editable now.
* The product table was optimized to support thousands of products without issue.
* Type and Percent column were removed from the price list view
* Edit Action was removed from the price list view
= 1.0.6 =
* Price List can be exported to csv file
* Grouped and External Product support added
* Spanish 80% Admin ui translated
= 1.0.7 =
* Price format feature was removed to avoid third party plugin collision.
* Price manipulation was added earlier
= 1.0.8 =
* Fix => Empty but existent meta price causing price not being shown on frontend
