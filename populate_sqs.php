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
	die("Usage: populate_sqs.php [new]\n");
}

if ($new) {
	$queueName .= '-new';
}

// Getting execution parameters from the SQS message
$service = new Amazon_SQS_Client(
				SQS_AWS_ACCESS_KEY_ID,
				SQS_AWS_SECRET_ACCESS_KEY,
				array('ServiceURL' => 'http://queue.amazonaws.com')
);

$queueURL = null;

// get queue
try {
	$response = $service->createQueue(array(
		'QueueName' => $queueName,
		'DefaultVisibilityTimeout' => DEFAULT_VISIBILITY_TIMEOUT
			));

	if ($response->isSetCreateQueueResult()) {
		$createQueueResult = $response->getCreateQueueResult();
		if ($createQueueResult->isSetQueueUrl()) {
			$queueURL = $createQueueResult->getQueueUrl();
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

if (is_null($queueURL)) {
	die("Can't get queue URL for queue: $queueName\n");
}

$urls_source = SHOWSLOW_BASE . '/monitor.php';
if ($new) {
	$urls_source .= '?new';
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $urls_source);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_ENCODING, "gzip");
$urls = curl_exec($ch);

$urls_array = explode("\n", $urls);

while (count($urls_array) > 0) {
	$chunk = array_splice($urls_array, 0, URLS_IN_CHUNK);

	try {
		$response = $service->sendMessage(array(
			'QueueUrl' => $queueURL,
			'MessageBody' => implode("\n", $chunk)
				));

		if ($response->isSetSendMessageResult()) {
			echo("            SendMessageResult\n");
			$sendMessageResult = $response->getSendMessageResult();
			if ($sendMessageResult->isSetMessageId()) {
				echo("                MessageId\n");
				echo("                    " . $sendMessageResult->getMessageId() . "\n");
			}
			if ($sendMessageResult->isSetMD5OfMessageBody()) {
				echo("                MD5OfMessageBody\n");
				echo("                    " . $sendMessageResult->getMD5OfMessageBody() . "\n");
			}
		}
		if ($response->isSetResponseMetadata()) {
			echo("            ResponseMetadata\n");
			$responseMetadata = $response->getResponseMetadata();
			if ($responseMetadata->isSetRequestId()) {
				echo("                RequestId\n");
				echo("                    " . $responseMetadata->getRequestId() . "\n");
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