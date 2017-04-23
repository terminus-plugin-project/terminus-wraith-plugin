# Terminus Wraith Plugin

Wraith - A Terminus plugin to assist with visual regression testing of [Pantheon](https://pantheon.io/) site environments.

## Usage:
```
$ terminus wraith [--sites site-name.test,site-name.prod --paths home=/,about=/about,news=/news,... --config --spider]
```

## Examples:
Use the previous configuration or prompt for the environments and pages to compare and then generate snapshots:
```
$ terminus wraith
```
Prompt for the environments and pages to compare and then generate snapshots:
```
$ terminus wraith --config
```
Prompt for new pages to compare the my-site.test and my-site.prod environments and then generate snapshots:
```
$ terminus wraith --sites my-site.test,my-site.prod
```
Prompt for new environments to compare the /, /about and /news pages and then generate snapshots:
```
$ terminus wraith --paths home=/,about=/about,news=/news
```
Use the previous configuration or prompt for new environments, crawl to detect pages and then generate snapshots:
```
$ terminus wraith --spider
```

## Installation:

For installation help, see [Extend with Plugins](https://pantheon.io/docs/terminus/plugins/).

```
mkdir -p ~/.terminus/plugins
composer create-project -d ~/.terminus/plugins terminus-plugin-project/terminus-wraith-plugin:~1
```

## Configuration:

Install [Wraith](http://bbc-news.github.io/wraith/) for your [operating system](http://bbc-news.github.io/wraith/os-install.html).  See [http://bbc-news.github.io/wraith/os-install.html](http://bbc-news.github.io/wraith/os-install.html).

## Testing:

Replace `my-site.test` and `my-site.prod` with the site environments you want to test:
```
export TERMINUS_SOURCE_SITE_ENV=my-site.test
export TERMINUS_TARGET_SITE_ENV=my-site.prod
cd ~/.terminus/plugins/terminus-wraith-plugin
composer install
composer test
```

## Help:
Run `terminus help wraith` for help.
