#!/bin/bash

SHOWSLOWBASE='http://www.showslow.com' # e.g. 'http://www.showslow.com'

if [ "x$1" == "xnew" ]; then
	echo "Testing only recently added URLs"
	URLS=`curl -s $SHOWSLOWBASE/monitor.php?new`
else
	echo "Testing all URLs"
	URLS=`curl -s $SHOWSLOWBASE/monitor.php`
fi

# Download and install PhantomJS: http://phantomjs.org/download.html
PHANTOMJS='/home/ec2-user/phantomjs/bin/phantomjs'

# Download the latest build of phantomjs version of yslow: https://github.com/marcelduran/yslow/downloads
YSLOWJS='/home/ec2-user/user-repo/yslow.js'
YSLOWLOG='/home/ec2-user/user-logs/yslow.log'

# Resetting the log
>$YSLOWLOG

for URL in $URLS
do
	echo `date` "Testing $URL" >>$YSLOWLOG
	$PHANTOMJS $YSLOWJS -i grade -b $SHOWSLOWBASE/beacon/yslow/ $URL >>$YSLOWLOG
	rm -rf ~/.qws
done
