<?php

require_once dirname(__FILE__) . '/config.inc.php';

// SQS setup
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/sqs/src');

function __autoload($className) {
	$filePath = str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
	$includePaths = explode(PATH_SEPARATOR, get_include_path());
	foreach ($includePaths as $includePath) {
		if (file_exists($includePath . DIRECTORY_SEPARATOR . $filePath)) {
			require_once $filePath;
			return;
		}
	}
}

// if file name was not passed in the command line, query SQS for a message
$new = false;
if ($argc == 2) {
	$new = ($argv[1] == 'new' ? true : false);
} else if ($argc > 2) {
	die("Usage: showslow.php [new]\n");
}


$queue_cache_file_name = QUEUE_URL_CACHE;
if ($new) {
	$queueName .= '-new';
	$queue_cache_file_name .= '-new';
}

if (DEBUG) {
	echo("[" . date('r') . "] ShowSlow tester started using $queueName queue\n");
}

// Instantiate the class
$SQS = new Amazon_SQS_Client(SQS_AWS_ACCESS_KEY_ID, SQS_AWS_SECRET_ACCESS_KEY);
$queueURL = null;


if (file_exists($queue_cache_file_name)) {
	$queueURL = file_get_contents($queue_cache_file_name);
} else {
	// get queue
	try {
		$response = $SQS->createQueue(array(
			'QueueName' => $queueName,
			'DefaultVisibilityTimeout' => DEFAULT_VISIBILITY_TIMEOUT
				));

		if ($response->isSetCreateQueueResult()) {
			$createQueueResult = $response->getCreateQueueResult();
			if ($createQueueResult->isSetQueueUrl()) {
				$queueURL = $createQueueResult->getQueueUrl();
				file_put_contents($queue_cache_file_name, $queueURL);
			}
		}
	} catch (Amazon_SQS_Exception $ex) {
		echo("Caught Exception: " . $ex->getMessage() . "\n");
		echo("Response Status Code: " . $ex->getStatusCode() . "\n");
		echo("Error Code: " . $ex->getErrorCode() . "\n");
		echo("Error Type: " . $ex->getErrorType() . "\n");
		echo("Request ID: " . $ex->getRequestId() . "\n");
		echo("XML: " . $ex->getXML() . "\n");
	}
}

if (is_null($queueURL)) {
	die("Can't get queue URL for queue: $queueName\n");
}

# do this until we have nothing to encode
while (1) {
	$urls = array();
	$messageHandle = null;
	try {
		$response = $SQS->receiveMessage(array(
			'QueueUrl' => $queueURL,
			'MaxNumberOfMessages' => 1
				));

		if ($response->isSetReceiveMessageResult()) {
			$receiveMessageResult = $response->getReceiveMessageResult();
			$messageList = $receiveMessageResult->getMessage();
			foreach ($messageList as $message) {
				if ($message->isSetReceiptHandle()) {
					$messageHandle = $message->getReceiptHandle();
				}
				if ($message->isSetBody()) {
					if (DEBUG) {
						echo("SQS Message Body: [" . $message->getBody() . "]\n");
					}

					$urls = explode("\n", $message->getBody());
				}
			}
		}
	} catch (Amazon_SQS_Exception $ex) {
		echo("Caught Exception: " . $ex->getMessage() . "\n");
		echo("Response Status Code: " . $ex->getStatusCode() . "\n");
		echo("Error Code: " . $ex->getErrorCode() . "\n");
		echo("Error Type: " . $ex->getErrorType() . "\n");
		echo("Request ID: " . $ex->getRequestId() . "\n");
		echo("XML: " . $ex->getXML() . "\n");
	}

	if (count($urls) == 0 || is_null($messageHandle)) {
		echo "\n[" . date('r') . "] Sleeping for " . WAIT_BETWEEN_TESTS . " seconds before exiting";
		sleep(WAIT_BETWEEN_TESTS);
		exit(4); // nothing to test
	}

	testURLs($urls, $messageHandle);
}

function testURLs($urls, $messageHandle = null) {
	global $new, $SQS, $queueURL;

	$cnt = count($urls);

	if (DEBUG) {
		echo("\n[" . date('r') . "] Testing: $cnt urls\n");
	}

	foreach ($urls as $url) {
		if ($new) {
			$flag_file_name = TEMP_FOLDER . "/process.cache/" . md5($url);

			if (file_exists($flag_file_name)) {
				echo("New URL was already tested: $url ... skipping\n");
				continue;
			}

			file_put_contents($flag_file_name, '');
		}

		// Execute YSlow using Phantom JS
		$exec_string = "timeout 90 " . PHANTOMJS . " " . YSLOWJS . " -i grade -b " .
				SHOWSLOW_BASE . "/beacon/yslow/ $url >>" . YSLOWLOG;

		if (DEBUG) {
			echo("Executing: $exec_string\n");
		}

		$output = shell_exec($exec_string);

		if (DEBUG) {
			echo("Output: [$output]\n");
		}

		// Running Page Speed Service API
		if (DEBUG) {
			echo("\n[" . date('r') . "] Testing $url using Page Speed API\n");
		}
		$pagespeed_api_url = SHOWSLOW_BASE . "/beacon/pagespeed/?api&u=" . urldecode($url);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $pagespeed_api_url);
		curl_exec($ch);
	}

	if (!is_null($messageHandle)) {
		// OK, now let's delete it from queue to make sure we don't encode multiple times
		try {
			$SQS->deleteMessage(array(
				'QueueUrl' => $queueURL,
				'ReceiptHandle' => $messageHandle
			));
		} catch (Amazon_SQS_Exception $ex) {
			echo("Caught Exception: " . $ex->getMessage() . "\n");
			echo("Response Status Code: " . $ex->getStatusCode() . "\n");
			echo("Error Code: " . $ex->getErrorCode() . "\n");
			echo("Error Type: " . $ex->getErrorType() . "\n");
			echo("Request ID: " . $ex->getRequestId() . "\n");
			echo("XML: " . $ex->getXML() . "\n");
		}
	}
}

echo "\n[" . date('r') . "] Sleeping for " . WAIT_BETWEEN_TESTS . " seconds before exiting";
sleep(WAIT_BETWEEN_TESTS);
exit(1); // completed
