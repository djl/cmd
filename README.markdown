# shrt
shrt is an implementation of [Shaun Inman](http://shauninman.com/)'s [Shortwave](http://shortwaveapp.com/), described thusly:

> an extensible quick-search and shortcut bookmark.


## Usage
* Upload shrt.php and point your browser to it.  
* Enter URL to your shrt/Shortwave text file.
* Drag the bookmarklet onto your bookmarks bar.
* Click bookmarklet.

shrt has all the features of Shortwave plus a few extras:


## Syntax
* `%s` - will be replaced by any arguments you provide, or blank if none are provided.
* `%d` - the domain of the current site.
* `%r` - the full url of the current site.
* `%{kittens}` - a default argument, to be optionally overridden.

Arguments can also be nested inside of one another:

* `%{%d}` - `%d` argument nested inside of default argument.
* `%{%r}` - `%r` argument nested inside of default argument.


## Examples
See shrts.txt.


## Notes
* shrt can read your current Shortwave files, but Shortwave may not be able to read some of your shrt file, due to the extra features shrt provides.
* When upgrading to a new version or changing settings, an update to your bookmarklet may be required.
* shrt is not optimized at all, probably nowhere near the speed it could be.
* JavaScript is required.

## Todo
* Process incoming shrt as file is parsed.
* Update help page to better show search arguments.
* Make `help` an optional trigger.
* Allow punctuation in triggers: `ip?` `down?` `!!`