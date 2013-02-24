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

* `%s` - the provided search string.
* `%d` - the domain of the current site.
* `%r` - the full URL of the current site.
* `%t` - the title of the current page.
* `>` - comment to the end of the line.


Plus some extras:

* `%{kittens}` - a default argument.

  If no search string is given, `kittens` will be used in it's
  place. Other tokens can be nested inside of these (e.g. `%{%d}`).
