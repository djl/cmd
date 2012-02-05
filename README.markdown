kid
===

kid is an extensible bookmarklet command thing. It's similar to
[Shortwave][shortwave] and [Quix][quix].

[shortwave]: http://shortwaveapp.com
[quix]: http://quixapp.com



Usage
-----

1. Upload kid.php and point your browser to it.
2. Enter the URL to your shortcuts file.
3. Drag the bookmarklet onto your bookmarks bar.
4. Click bookmarklet.



Syntax
------

kid features the standard Shortwave/Quix tokens:

* `%s` - will be replaced by any arguments you provide.
* `%d` - the domain of the current site.
* `%r` - the full URL of the current site.
* `%t` - the title of the current page
* `>` - comment to the end of the line.

Plus some extras:

* `%c` - the full given command.
* `%{kittens}` - a default argument. If no argument is passed, `kittens` will
be used.



Examples
--------

See examples.txt.
