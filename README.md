# Parse-Url

Wrap anything you want to be processed between the tag pairs.

    {exp:parse_url total="100"}

        text you want processed

    {/exp:parse_url}

## PARAMETERS:

The `parts` parameter lets you specify what parts of the URL to keep:

- `scheme` - e.g. http
- `host`
- `port`
- `user`
- `pass`
- `path`
- `query` - after the question mark ?
- `fragment` - after the hashmark #

Include multiple ones like so `parts="scheme|host|path|query|fragment"`

--------

The `omit` parameter lets you remove a certain string from the URLs. Separate multiple strings with a bar (|).

--------

The `find_uris` parameter lets you control auto-discovery of URLs. If set to "no" it will treat the entire input as a URL. [default: yes]

Version 1.1.1
***************************
Fixed a bug where the auto linking was interfering with this plugin's processing of URLs.

Version 1.2
***************************
Updated plugin to be 2.0 compatible

Version 1.2.1
***************************
Added an find_uris parameter to control auto-discovery, which breaks some complex URLs.
