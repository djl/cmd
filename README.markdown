# shrt
shrt is an implementation of [Shaun Inman](http://shauninman.com/)'s [Shortwave](http://shortwaveapp.com/), described thusly:

> an extensible quick-search and shortcut bookmark.


## Usage
* Upload shrt.php and point your browser to it.  
* Enter URL to your shrt/Shortwave text file.
* Drag the bookmarklet onto your bookmarks bar.
* Click bookmarklet.


## Syntax
shrt features the standard Shortwave syntax:

* `%s` - will be replaced by any arguments you provide, or blank if none are provided.
* `%d` - the domain of the current site.
* `%r` - the full url of the current site.
* `>` - comment to the end of the line.

Plus some extras:

* `%{kittens}` - a default argument, to be optionally overridden.
* `%{%d}` - `%d` argument nested inside of default argument.
* `%{%r}` - `%r` argument nested inside of default argument.


## Examples
See shrts.txt.


## Notes
* JavaScript is required.
* shrt can read your current Shortwave file, but Shortwave may not be able to read your shrt file.
* When upgrading to a new version or changing settings, an update to your bookmarklet may be required.
* shrt is not optimized at all, probably nowhere near the speed it could be.


## Todo
* Update help page to better show search arguments.
* Make `help` an optional trigger.
* Allow punctuation in triggers: `ip?` `down?` `!!`
* Basic logic based on given arguments. e.g. If  `foo` is given an argument, do `x`, otherwise do `y`
