# WordPress Shortcode Class

This library provides a skeleton to build shortcodes for WordPress.
 
## Description
This library provides the skeleton for:
1. Registering the shortcode. If a shortcode exists with the same name, it will automatically remove the shortcode before adding this shortcode.
1. Ensuring that scripts/styles are only printed when the shortcode is being used.
1. Merging shortcode attributes with default attributes.

## The `Shortcode` Base Class
The shortcode is registered at `plugins_loaded` hook or immediately upon class instantiation via singleton implementation: `MyShortcode::get_instance()`.

This class checks the following to determine whether a shortcode exists on a page:
* The title
* The content
* The excerpt
* The Widget Title
* The Custom HTML Widget Content
* The Text Widget
* The Text Widget Content
* The Text Widget Content
* The Navigation Menu Link Attributes
* The Navigation Menu Title

Additionally, the class provides a filter `wps_shortcode_{}_is_active` to further extend checks to determine whether the shortcode exists on a page.

## The `AjaxShortcode` Class
This class extends the `Shortcode` base class providing additional features:
1. Skeleton for registering AJAX calls with checking the ajax refer nonce.
1. `doing_ajax()` static function.
1. `get_random_string()` static function.

## Installation
To install via composer:
```
composer require wpsmith/shortcode
```

Otherwise copy the `Shortcode` (and `AjaxShortcode`) class(es) to your folder. 

## Usage
This library is easy to use in two simple steps:
1. Extend the class implementing the method `public function shortcode( $atts, $content = null ){}`. Note:
    * Your defaults as defined in `get_defaults()` method will already be merged (and also available via `$this->atts`) 
    * Your script(s)/style(s) as enqueued in `enqueue_scripts()` method will already be printed or enqueued.
1. Instantiate the singleton in your theme/plugin: `MyShortcode::get_instance()`.

Optionally, you can do the following:
* Implement `register_scripts()` method to register your script differently from enqueing your script. This is highly recommended if you are using this within a distributed theme or plugin.
* Implement `init()` method to do some more initializing when the shortcode is instantiated.
* Implement `get_defaults()` to set some default parameters to be merged and thus always present.

## Examples
You can find these and more examples at [Shortcodes](https://github.com/wpsmith/Shortcodes).
* [Basic Shortcode [search_form] Example](https://github.com/wpsmith/Shortcodes/blob/master/src/SearchForm.php)
* [Basic Shortcode [email] Example with Defaults](https://github.com/wpsmith/Shortcodes/blob/master/src/Email.php)
* [Shortcode [parallax_image] Example with a script/style](https://github.com/wpsmith/Shortcodes/blob/master/src/ParallaxImage.php)

