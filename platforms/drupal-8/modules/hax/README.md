## Usage

This should give you the dependencies you need to get going.
1. Enable the HAX module and any dependencies it requires.
2. Go to the permissions page to ensure users have the 'use hax' permission
   checked. Once this is checked then people will start to see a 'HAX Authoring'
   local menu item / tab / contextual option show up when they have access to
   edit a node. If you want users to be able to upload files, grant the
   'Upload files via HAX editor' permission.
3. The default is to serve JS assets up from a CDN.
   Should you need to change this keep reading into building your own assets.

NOTE on Text Formats: HAX is designed to work with nodes with bodies in the
default Full HTML format where "Limit allowed HTML tags and correct faulty HTML"
is unchecked, or with formats with similarly permissive settings. For this
reason, it is advisable to only allow trusted users to access HAX.

## Settings

The settings page has ways of hooking up youtube, vimeo and more via the "App
store" concept built into HAX. You can also make small tweaks to your needs on
this page.

## End user

Go to the node's hax tab, then hit the pencil in the top right. When your done
editing hit the power button again and it should confirm that it has saved back
to the server. Congratulations on destoying the modules you need to build an
awesome site!

### Developer settings
There are some non-UI based settings that developers can set if they are running into issues with complex integrations that are environment specific. If they need to serve build.js locally as opposed to via CDN, when leveraging a CDN, then `$settings['hax_project_local_build_file'] = true;` can be used to achieve this in settings.php. This can also be useful if you've forked the recommended build.js file that comes from our supported unbundled-build routine.

#### Centralized appstore definition
So you're running HAX across multiple Drupal and non-Drupal properties. Congrats! Your site builders are going to benefit from a unified UX pattern across applications, realizing the true vision of HAX. If you use `$settings['hax_custom_appstore'] = "https://what.ever.location.this.is/appstore.json";` and deliver a valid appstore specified document, then you'll be able to skip the settings baked into the UI in the platform and opt for knowing what you're doing to implement at scale.  This setting also isn't exposed because it would be confusing for most users since you have to have a larger content network strategy in order to even need this. If you need to learn how to write appstore or what it looks like -- https://haxtheweb.org/hax-appstore-specification

## Proxie environments
To upload media in a proxied environment you may need to add `$settings['base_url'] = "https://yourdomain.com";` to `settings.php` which has the actual full base url to the server. Most configurations this setting won't be needed but in testing proxies can cause issues as far as how this address is default configured in Drupal.

# Front end Developers
You may build HAX from source if needed. HAX defaults to use CDNs which will effectively point to
this directory or some mutation of it -- https://github.com/elmsln/HAXcms/tree/master/build

If you want to build locally in order to add your own custom web components then we've built our unbundled builds tooling to simplify this -- https://github.com/elmsln/unbundled-webcomponents

## Getting dependencies
You need polymer cli (not polymer but the CLI library) in order to interface with web components in your site. Get polymer cli installed prior to usage of this (and (yarn)[https://yarnpkg.com/lang/en/docs/install/#mac-stable] / an npm client of some kind)
```bash
$ yarn global add polymer-cli
```
Perform this on your computer locally, this doesn't have to be installed on your server.

## Usage

- Find `CopyThisStuff` directory in `/modules/hax` or `/sites/all/modules/hax`.
- create a `/sites/all/libraries/webcomponents` directory
- copy the files from `CopyThisStuff` into `/sites/all/libraries/webcomponents`

Then run the following (from the directory you copied it over to) in order to get dependencies:
```bash
$ yarn install
```
Now run `polymer build` and you'll have files in `build/` which contain everything you'll need to get wired up to web components in your site. Modifying build.js or package.json can be used in order to get new elements and have them be implemented.

### Shouldn't I put web components in my theme?
We don't think so. While it may seem counter intuitive, the theme layer should be effectively implementing what the site is saying is available. If you think of standard HTML tags are being part of this (p, div, a, etc) then it makes a bit more sense. You don't want functional HTML components to ONLY be supplied if your theme is there, you want your theme to implement and leverage the components. Our autoloading script will automatically hydrate web components that are detected.

## New to web components?
We built our own tooling to take the guess work out of creating, publishing and testing web components for HAX and other projects. We highly recommend you use this tooling though it's not required:
- https://open-wc.org - great, simple tooling and open community resource
- https://github.com/elmsln/unbundled-webcomponents - build for lazy loading in any application, Drupal or otherwise
- https://github.com/elmsln/wcfactory - Build your own web component library at scale
- https://github.com/elmsln/lrnwebcomponents - Our invoking of this tooling to see what a filled out repo looks like