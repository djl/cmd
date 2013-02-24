kid
---

kid is an extensible bookmarklet command thing. It's similar to
[Shortwave](http://shortwaveapp.com).



Usage
-----

1. Upload kid.php and point your browser to it.
2. Enter the URL to your shortcuts file.
3. Drag the bookmarklet onto your bookmarks bar.
4. Click bookmarklet.



Syntax
------

kid features the standard Shortwave tokens:

* `%s` - the provided search string.
* `%d` - the domain of the current site.
* `%r` - the full URL of the current site.
* `%t` - the title of the current page.
* `>` - comment to the end of the line.


Plus some extras:

* `%{foo}` - a default argument. Supports nested tokens (e.g. `%{%d}`)
