cmd
---

cmd is an extensible bookmarklet command thing.



Usage
-----

1. Upload cmd.php and point your browser to it.
2. Enter the URL to your shortcuts file.
3. Drag the bookmarklet onto your bookmarks bar.
4. Click bookmarklet.



Syntax
------

cmd supports the [Shortwave](http://shortwaveapp.com)'s syntax:

* `%s` - the provided search string.
* `%d` - the domain of the current site.
* `%r` - the full URL of the current site.
* `%t` - the title of the current page.
* `>` - comment to the end of the line.


Plus some extras:

* `%{foo}` - a default argument. Supports nested tokens (e.g. `%{%d}`)


Alternatives
------------

* [Shortwave](http://shortwaveapp.com)
* [cmd](http://swtch.com/cmd/) 
* [PL CMDLINE](http://phoboslab.org/cmd/)
* [Yubnub](http://www.yubnub.org/)
