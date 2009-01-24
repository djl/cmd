# shrt
shrt is an implementation of [Shaun Inman](http://shauninman.com/)'s [Shortwave](http://shortwaveapp.com/), described thusly:

> an extensible quick-search and shortcut bookmark.

Yes, quite.


## Usage
* Upload shrt.php and point your browser to it.  
* Put the full URL to your shrt/Shortwave text file.
* Drag the bookmarklet onto your bookmarks bar.
* Click bookmarklet.

shrt has all the features of Shortwave plus a few extras:


## Syntax
* `%s` - will be replaced by any arguments you provide, or blank if none are provided.
* `%d` - the domain of the current site.
* `%r` - the full url of the current site.
* %{kittens} - a default argument, to be optionally overridden.


## Examples
See shrts.txt.

## Notes
* shrt can read your current Shortwave files, although Shortwave may not be able to read some of your shrt file, due to the extra features shrt provides.
* When upgrading to a new version, one may need to update one's bookmarklet.
* JavaScript is required.
