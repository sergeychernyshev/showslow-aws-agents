#!/bin/bash

SHOWSLOWBASE='http://www.showslow.com' # e.g. 'http://www.showslow.com'

# Download and install PhantomJS: http://phantomjs.org/download.html
PHANTOMJS='/home/ec2-user/phantomjs/bin/phantomjs'

# Download the latest build of phantomjs version of yslow: https://github.com/marcelduran/yslow/downloads
YSLOWJS='/home/ec2-user/user-repo/yslow.js'

# Logs
YSLOWLOG='/home/ec2-user/user-logs/yslow.log'
PAGESPEEDLOG='/home/ec2-user/user-logs/pagespeed.log'

# Resetting the logs
>$YSLOWLOG
>$PAGESPEEDLOG

if [ "x$1" == "xnew" ]; then
	echo "Testing only recently added URLs" >>$YSLOWLOG
	URLS=`curl -s $SHOWSLOWBASE/monitor.php?new`
else
	echo "Testing all URLs" >>$YSLOWLOG
	URLS=`curl -s $SHOWSLOWBASE/monitor.php`
fi

mkdir -p /home/ec2-user/user-logs/process.cache/

for URL in $URLS
do
	SKIP=0
	if [ "x$1" == "xnew" ]; then
		HASH=`echo "$URL" | md5sum | sed -e 's/\s.*//'`
		if [ -f /home/ec2-user/user-logs/process.cache/$HASH ]; then
			SKIP=1
		fi

		>/home/ec2-user/user-logs/process.cache/$HASH
	fi

	if [ "x$SKIP" == "x1" ]; then
		echo `date` "New URL $URL was already tested before, skipping" >>$YSLOWLOG
	else
		# YSlow
		echo `date` "Testing $URL" >>$YSLOWLOG
		timeout 90 $PHANTOMJS $YSLOWJS -i grade -b $SHOWSLOWBASE/beacon/yslow/ $URL >>$YSLOWLOG
		rm -rf ~/.qws
		rm -rf ~/.fontconfig

		# Google Page Speed
		echo `date` "Testing $URL using Page Speed API" >>$PAGESPEEDLOG
		echo "$URL" | curl "$SHOWSLOWBASE/beacon/pagespeed/?api" -G --data-urlencode u@-
	fi
done