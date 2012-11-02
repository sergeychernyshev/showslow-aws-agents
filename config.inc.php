<?php

define('DEBUG', true);
#define('DEBUG', false);

$system_ini_file = "/home/ec2-user/system.ini";
if (file_exists('system.ini')) {
	$system_ini_file = 'system.ini';
}

$system_ini = parse_ini_file($system_ini_file, true);

define('SQS_AWS_ACCESS_KEY_ID', $system_ini['aws']['aws-key']);
define('SQS_AWS_SECRET_ACCESS_KEY', $system_ini['aws']['aws-secret']);

echo "AWS Key: " . SQS_AWS_ACCESS_KEY_ID . "\n";
echo "AWS Secret: " . SQS_AWS_SECRET_ACCESS_KEY . "\n";

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

define('WAIT_BETWEEN_TESTS', 300);