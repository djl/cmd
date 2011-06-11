kid
====

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
* `%l` - any highlighted text on the current page
* `%{kittens}` - a positional argument, to be optionally overridden.



Compatibility
-------------

kid *should* be able to read your Shortwave and Quix files, but obviously
neither will be able to parse the extra syntax kid provides.

If kid *can't* correctly parse one of these files, it's a bug.



Examples
-----------

See examples.txt.
