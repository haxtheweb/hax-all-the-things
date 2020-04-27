## Usage

This should give you the dependencies you need to get going.
1. Enable the Web components module
2. The default is to serve JS assets up from a CDN.
   Should you need to change this keep reading into building your own assets.

NOTE on Text Formats: Web components are designed to work with nodes with bodies in the
default Full HTML format where "Limit allowed HTML tags and correct faulty HTML"
is unchecked, or with formats with similarly permissive settings.

### Developer settings
There are some non-UI based settings that developers can set if they are running into issues with complex integrations that are environment specific. If they need to serve build.js locally as opposed to via CDN, when leveraging a CDN, then `$settings['webcomponents_project_local_build_file'] = true;` can be used to achieve this in settings.php. This can also be useful if you've forked the recommended build.js file that comes from our supported unbundled-build routine.

## Proxie environments
To upload media in a proxied environment you may need to add `$settings['base_url'] = "https://yourdomain.com";` to `settings.php` which has the actual full base url to the server. Most configurations this setting won't be needed but in testing proxies can cause issues as far as how this address is default configured in Drupal.

# Front end Developers
If you want to build locally in order to add your own custom web components then we've built our unbundled builds tooling to simplify this -- https://github.com/elmsln/unbundled-webcomponents

## Getting dependencies
You need polymer cli (not polymer but the CLI library) in order to interface with web components in your site. Get polymer cli installed prior to usage of this (and (yarn)[https://yarnpkg.com/lang/en/docs/install/#mac-stable] / an npm client of some kind)
```bash
$ yarn global add polymer-cli
```
Perform this on your computer locally, this doesn't have to be installed on your server.

## Usage

- Find https://github.com/elmsln/unbundled-webcomponents and run the tooling to create your build (`yarn install` then `yarn run build`)
- create a `/sites/all/libraries/webcomponents` directory
- copy the files from https://github.com/elmsln/unbundled-webcomponents into `/sites/all/libraries/webcomponents`

### Shouldn't I put web components in my theme?
We don't think so. While it may seem counter intuitive, the theme layer should be effectively implementing what the site is saying is available. If you think of standard HTML tags are being part of this (p, div, a, etc) then it makes a bit more sense. You don't want functional HTML components to ONLY be supplied if your theme is there, you want your theme to implement and leverage the components. Our autoloading script will automatically hydrate web components that are detected.

## New to web components?
We built our own tooling to take the guess work out of creating, publishing and testing web components for HAX and other projects. We highly recommend you use this tooling though it's not required:
- https://open-wc.org - great, simple tooling and open community resource
- https://github.com/elmsln/unbundled-webcomponents - build for lazy loading in any application, Drupal or otherwise
- https://github.com/elmsln/wcfactory - Build your own web component library at scale
- https://github.com/elmsln/lrnwebcomponents - Our invoking of this tooling to see what a filled out repo looks like