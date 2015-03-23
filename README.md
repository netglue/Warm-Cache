# CLI Utility to Warm UP your Cache...

**Warning** - No tests yet, use at your own risk. This was chucked together pretty quick.

This is a simple utility to warm up your cache from a sitemap file.

It requires [box-project](http://box-project.org) to build the phar, so install that somewhere first by reading the docs on the [box website](http://box-project.org)

Once you've cloned this, run a `composer install` followed by a `box build` and you should get a phar in the project root that you can distribute wherever you want to put it.

## Using the tool

    $ ./cachewarm.phar warm http://example.com/sitemap.xml

If you are warming something up with an invalid SSL cert, you can add `--unsafe` to turn off cURL's SSL cert verification thus:
    
    $ ./cachewarm.phar warm https://example.com/sitemap.xml --unsafe

Increase vebosity to get more info about the urls found/crawled:
    
    $ ./cachewarm.phar warm http://example.com/sitemap.xml -vv

Sitemap index files are fine, so you only need to point it at your main index file.

Bear in mind that there's no threads/workers - it just hits each page sequentially so on a large site, this could take a while.


---
[Made in Devon by Net Glue](https://netglue.uk)



