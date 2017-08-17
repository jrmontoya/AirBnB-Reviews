# AirBnB-Reviews

This plugin allows you to import listing reviews from AirBnB into WordPress.

By default, listings are imported as posts but there are a few hooks that you can use to customize the import process. Check the code to find them and how to use them.

**USAGE**

Once you activated the plugin you will find a new "AirBnB Reviews" entry in the "Settings" menu or click on "Settings" in the installed plugins list. The page will ask you for your AirBnB API Key and the update frequency of the reviews you want to import.

You can get your API Key following this instructions:
Log into Airbnb.com, open up the web developer console, go to the network tab, filter by type json, and look at the url and find "key".

Then you can use the shortcode **[airbnb-reviews id="LISTING-ID"]** in your content and widgets.

**FAIR WARNING & DISCLAIMER**

This plugin uses the AirBnB private API and thus, if you are an AirBnB user, you will probably violate the AirBnB terms of services by using it.

You will also violate AirBnB intellectual property if you use it to download and distribute verified photos (those pictures that have been taken by a photograph AirBnB send for free).

As such you are solely responsible for using this plugin. This developer will not be liable for any damages you may suffer in connection with using, modifying, or distributing this plugin. In particular, this developer will not be liable for any loss of revenue you may incur if your AirBnB account is suspended following your use of this plugin.

Thanks to [Claude Vedovini](https://vedovini.net) for part of this content and his plugin [Simple AirBnB Listings Importer](https://wordpress.org/plugins/simple-airbnb-listings-importer/)
