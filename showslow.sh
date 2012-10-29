#!/bin/bash

SHOWSLOWBASE='http://www.showslow.com' # e.g. 'http://www.showslow.com'

# Download and install PhantomJS: http://phantomjs.org/download.html
PHANTOMJS='/home/ec2-user/phantomjs/bin/phantomjs'

# Download the latest build of phantomjs version of yslow: https://github.com/marcelduran/yslow/downloads
YSLOWJS='/home/ec2-user/user-repo/yslow.js'

# Logs
SHOWSLOWLOG='/home/ec2-user/user-logs/showslow.log'
YSLOWLOG='/home/ec2-user/user-logs/yslow.log'
PAGESPEEDLOG='/home/ec2-user/user-logs/pagespeed.log'

# Resetting the logs
>$YSLOWLOG
>$PAGESPEEDLOG

if [ "x$1" == "xnew" ]; then
	echo `date` "Testing only recently added URLs" >>$SHOWSLOWLOG
	LISTSERVICE="$SHOWSLOWBASE/monitor.php?new"
else
	echo `date` "Testing all URLs" >>$SHOWSLOWLOG
	LISTSERVICE="$SHOWSLOWBASE/monitor.php"
fi

mkdir -p /home/ec2-user/user-logs/process.cache/

PARNUM=3
FOLDER=/tmp/urls.$$
mkdir -p $FOLDER

echo Retrieving list of URLs from $LISTSERVICE >>$SHOWSLOWLOG
curl -s $LISTSERVICE > $FOLDER/urls.txt
TOTALLINES=`wc -l < $FOLDER/urls.txt`
LINESPERCHUNK=`expr $TOTALLINES / $PARNUM + 1`
(cd $FOLDER; split -l $LINESPERCHUNK $FOLDER/urls.txt urls_)
rm $FOLDER/urls.txt

function proc {
	URLS=`cat $1`

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
			echo `date` "New URL $URL was already tested before, skipping" >>$SHOWSLOWLOG
		else
			# YSlow
			echo `date` "Testing $URL" >>$YSLOWLOG
			timeout 90 $PHANTOMJS $YSLOWJS -i grade -b $SHOWSLOWBASE/beacon/yslow/ $URL >>$YSLOWLOG
			rm -rf ~/.qws
			rm -rf ~/.fontconfig

			# Google Page Speed
#			echo `date` "Testing $URL using Page Speed API" >>$PAGESPEEDLOG
#			echo "$URL" | curl -s "$SHOWSLOWBASE/beacon/pagespeed/?api" -G --data-urlencode u@-
		fi
	done
}

echo "Parallel processes: $PARNUM" >>$SHOWSLOWLOG
echo "Total number of URLs: $TOTALLINES" >>$SHOWSLOWLOG
echo "URLs per process: $LINESPERCHUNK" >>$SHOWSLOWLOG

LISTS=`ls -1 $FOLDER`

for LIST in $LISTS
do
	proc $FOLDER/$LIST &
done

wait

rm -rf $FOLDER

