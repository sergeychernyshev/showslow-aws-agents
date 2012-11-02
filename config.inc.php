<?php
define('DEBUG', true);
#define('DEBUG', false);

define('SQS_AWS_ACCESS_KEY_ID', 'AKIAIUZT75QKUMWH5YGQ');
define('SQS_AWS_SECRET_ACCESS_KEY', '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');


/////////////////// SET PARAMETERS HERE /////////////////////
$showslow_base = 'http://www.showslow.com/';
$urls_in_chunk = 100;
$queueName = 'ShowSlowTests';
$tempFolder = '/home/user/user-logs/';
$queueURLCache = $tempFolder.'.queueURLCache';
$destinationPermissions = S3::ACL_PUBLIC_READ;

// time during which we expect the result to be produced - when timeout is over
// message is going to be released to next encoder and we can have too much work done.
#$DefaultVisibilityTimeout = 180;
$DefaultVisibilityTimeout = 30;
