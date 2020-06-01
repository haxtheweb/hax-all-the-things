=== haxtheweb ===
Contributors: btopro
Donate link: https://haxtheweb.org/
Tags: editor, haxtheweb, disable gutenberg, classic editor
Requires at least: 4.9
Tested up to: 5.4
Stable tag: 3.9.4
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Here is a short description of the plugin.  This should be no more than 150 characters.  No markup here.

== Screenshots ==

1. Showcasing the editor highlighting a video that's been uploaded via HAX.

== Description ==
## haxtheweb
Bringing the HAX block editor to ClassicPress & WordPress. [HAX](https://haxtheweb.org/) is short for headless authoring experience, meaning that it is a block editor that is disconnected from the CMS its used in. HAX recognizes editable blocks by using the [web component standard](https://www.webcomponents.org/). HAX provides editing capabilities in a way that attempts to write clean HTML markup the same way an expert end-user could, but without ever touching code! The code is writen in a way that experts and developers can jump in and modify as needed given the semantic nature of webcomponents!

## 3rd party dependencies
While you can host assets locally, the HAX editor defaults to leveraging the Penn State Web components CDN.
You can modify this on the Settings -> Writing page. Additional details on this service:
- Penn State CDN domain https://cdn.webcomponents.psu.edu/ is served from Amazon CloudFront
- You can view assets that are exposed via CDN here https://webcomponents.psu.edu/ in StorybookJS
- Privacy polcies the university adheres to: https://policy.psu.edu/policies/ad53
- The source of HAX and your web components is configurable and you can change it to leverage your own (details below)

## Dependencies
- If using WordPress, you'll need to get the [Classic Editor plugin](https://wordpress.org/plugins/classic-editor/)
  - ClassicPress does not have this dependency
- Make sure that your Permalink settings are for pretty links or calls to load data via JSON will fail https://stackoverflow.com/questions/44204307/rest-api-init-event-not-fired

## Usage
This should give you the dependencies you need to get going.
1. Download this plugin and place it in wp-content/plugins/haxtheweb
2. If using WordPress you'll also need the Classic Editor plugin; and then you'll have to enable it as the default editor (wp-admin/options-writing.php)
3. Enable the Plugins (wp-admin/plugins.php)
4. Make sure Settings -> Permalinks is set to "Post name"
5. Go to Settings -> Writing and scroll down to change your settings
6. Go to edit or create a new page / post
7. Enjoy HAX'ing the web

## Configuration
HAX adds options to the Writing Settings page (wp-admin/options-writing.php) to allow for further integrations and customizations though the default settings are fine to get up and started with.

## Note on usage
The default is to serve the Javascript required for HAX and its web components from a CDN. We default to a Penn State mirror of the required assets so you can get up and running quickly. We recommend that if you choose to go into production with HAX, that you look at doing a build routine locally (outlined below) or leveraging one of the faster CDNs available.

## Hooking up additional "apps" in the "Find" area
To connect to popular services like YouTube, Flickr, and Vimeo you'll need an API key. You can find details on how to get these keys as well as where to put them on the Writing Settings (wp-admin/options-writing.php) page.

== Frequently Asked Questions == 

### Developer functions
By default, the auto-loaded elements (things you make with HAX and hit save) need to have the website taught how to render. This means that their web component JS files will be added to the site in order for them to render for users. This list can be modified on the Writing Settings page wp-admin/options-writing.php.

## Front end Developers
You may build HAX from source if needed. HAX defaults to use CDNs which will effectively point to
this directory or some mutation of it -- https://github.com/elmsln/HAXcms/tree/master/build

If you want to build everything from source, your welcome to use yarn / npm to do so though our
build routine effectively will end in the same net result.  If you want to do custom build routines
such as rollup or webpack and not use our prebuilt copies / split build approaches, then your welcome
to check the box related to not loading front end assets in the settings page in order to tailor
the build to your specific needs.

## Getting dependencies
You need polymer cli (not polymer but the CLI library) in order to interface with web components in your site. Get polymer cli installed prior to usage of this (and (yarn)[https://yarnpkg.com/lang/en/docs/install/#mac-stable] / an npm client of some kind)
```bash
$ yarn global add polymer-cli
```
Perform this on your computer locally, this doesn't have to be installed on your server.

## Usage

- Find `CopyThisStuff` directory in `/wp-content/plugins/haxtheweb`.
- create a `/wp-content/haxtheweb` directory
- copy the files from `CopyThisStuff` into `/wp-content/haxtheweb`

Then run the following (from the directory you copied it over to) in order to get dependencies:
```bash
$ yarn install
```
Now run `polymer build` and you'll have files in `build/` which contain everything you'll need to get wired up to web components in your site. Modifying build.js or package.json can be used in order to get new elements and have them be implemented.

### Shouldn't I put web components in my theme?
We don't think so. While it may seem counter intuitive, the theme layer should be effectively implementing what the site is saying is available. If you think of standard HTML tags are being part of this (p, div, a, etc) then it makes a bit more sense. You don't want functional HTML components to ONLY be supplied if your theme is there, you want your theme to implement and leverage the components.

## New to web components?
We built our own tooling to take the guess work out of creating, publishing and testing web components for HAX and other projects. We highly recommend you use this tooling though it's not required:
- https://open-wc.org - great, simple tooling and open community resource
- https://github.com/elmsln/wcfactory - Build your own web component library
- https://github.com/elmsln/lrnwebcomponents - Our invoking of this tooling to see what a filled out repo looks like

== Upgrade Notice ==
Worth upgrading to 3.9.2+ as it seeks wordpress.org official registry!

== Changelog ==
3.9.3 - notice fix on some pages
3.9.2 - Media integration and attachments!
3.9.1 - Search Media directly from HAX editor!
