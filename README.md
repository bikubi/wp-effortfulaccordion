# Effortful Accordion

A WordPress plugin. Transforms content-with-headings into an accordion, tabbed content or columns.

## Config

The plugin looks for a configuration file in your theme, since the layout depends on the theme.
Put it in the most convenient of following places:

```sh
.../wp-content/yourtheme/wp-effortfulaccordion-config.php
.../wp-content/yourtheme/lib/wp-effortfulaccordion-config.php # sage 8
.../wp-content/yourtheme/app/wp-effortfulaccordion-config.php # sage 9
```

## Usage

The main function `content_split`, as well as the Shortcode, take two optional parameters:

1. `mode`, defaults to `accordion`. Check the code for other values and the markup this produces.
2. `h`, which is the HTML heading-level at which the content is split. Range: `1`-`6`, defaults to `2`, meaning "split at `<h2>`s".

##Setup implicit/automatic content transformation

To run, say, on each page, or each page with template Foo, filter `the_content`, running the content through the main function.
See `config-example.php`.

For **accordions**, define your preferred Bootstrap version (3 and 4 are supported) like so:

```php
define('bootstrap_version', 4);
```

If you don't use Bootstrap in your theme, you can `wp_*_enqueue` `effortfulaccordion.js` and `effortfulaccordion.css`, which contain the relevant bits. In this case, `bootstrap_version` must be `3`.

## Explicit content transformation via Shortcode

```html
Content content content.
[accordion mode="accordion" h="2"]
<h2>First heading<h2>
Content related to the first heading.
<h2>Second heading</h2>
Content related to the second heading.
[/accordion]
More content content content.
```

## TODO

* All modes except `accordion` are untested, and need fallback CSS.
* Makefile (?) for effortfulaccordion.scss→css
