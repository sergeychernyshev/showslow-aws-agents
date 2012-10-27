#!/bin/bash

SHOWSLOWBASE='http://www.showslow.com' # e.g. 'http://www.showslow.com'

URLS=`curl $SHOWSLOWBASE/monitor.php`

# Download and install PhantomJS: http://phantomjs.org/download.html
PHANTOMJS='~/phantomjs/bin/phantomjs'

# Download the latest build of phantomjs version of yslow: https://github.com/marcelduran/yslow/downloads
YSLOWJS='./yslow.js'

for URL in $URLS
do
	echo "$URL" | $PHANTOMJS $YSLOWJS -i grade -b $SHOWSLOWBASE/beacon/yslow/ >/dev/null
done
