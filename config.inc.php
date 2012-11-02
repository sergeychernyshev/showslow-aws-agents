<?php

define('DEBUG', true);
#define('DEBUG', false);

define('SQS_AWS_ACCESS_KEY_ID', 'AKIAIUZT75QKUMWH5YGQ');
define('SQS_AWS_SECRET_ACCESS_KEY', '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');


/////////////////// SET PARAMETERS HERE /////////////////////
$queueName = 'ShowSlowTests';

define('SHOWSLOW_BASE', 'http://www.showslow.com');
define('TEMP_FOLDER', '/home/ec2-user/user-logs');
define('QUEUE_URL_CACHE', TEMP_FOLDER . '/.queueURLCache');

// time during which we expect the result to be produced for each chunk
define('DEFAULT_VISIBILITY_TIMEOUT', 9000);
define('URLS_IN_CHUNK', 100);

# Download and install PhantomJS: http://phantomjs.org/download.html
define('PHANTOMJS', '/home/ec2-user/phantomjs/bin/phantomjs');

# Download the latest build of phantomjs version of yslow: https://github.com/marcelduran/yslow/downloads
define('YSLOWJS', '/home/ec2-user/user-repo/yslow.js');

define('SHOWSLOWLOG', TEMP_FOLDER . '/showslow.log');
define('YSLOWLOG', TEMP_FOLDER . '/yslow.log');
define('PAGESPEEDLOG', TEMP_FOLDER . '/pagespeed.log');