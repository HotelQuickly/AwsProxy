<?php


namespace HQ\AwsProxy;

use Aws\DynamoDb\DynamoDbClient;
use Aws\Sqs\SqsClient;

/**
 * Class DynamoDbProxy
 *
 * @author Josef Nevoral <josef.nevoral@hotelquickly.com>
 */
class SqsProxy
{
	private $mandatoryConfigParameters = array(
		'accessKeyId',
		'secretAccessKey',
		'region'
	);

	/** @var SqsClient  */
	private $sqsClient;

	/** @var  string */
	private $queueUrl;

	public function __construct(array $config)
	{
		$this->validateConfigParameters($config);

		// SQS CLIENT
		$this->sqsClient = SqsClient::factory(array(
			'key'    => $config['accessKeyId'],
			'secret' => $config['secretAccessKey'],
			'region' => $config['region'],
			'version' => '2012-11-05'
		));
	}


	/**
	 * @param array $data
	 * @throws \Exception
	 */
	public function insert(array $data)
	{
		if ( ! $this->queueUrl) {
			throw new \Exception('Queue URL needs to be set to existing queue in SQS, now it is empty');
		}

		$this->sqsClient->sendMessage(array(
			'QueueUrl' => $this->queueUrl,
			'MessageBody' => json_encode($data)
		));
	}


	/**
	 * @param int $limit
	 * @return mixed|null
	 * @throws \Exception
	 */
	public function getMessages($limit = 1)
	{
		if ( ! $this->queueUrl) {
			throw new \Exception('Queue URL needs to be set to existing queue in SQS, now it is empty');
		}

		$response = $this->sqsClient->receiveMessage(array(
			'QueueUrl' => $this->queueUrl,
			'MaxNumberOfMessages' => $limit
		));

		return $response->get('Messages');
	}


	/**
	 * @param $receiptHandle
	 * @return \Guzzle\Service\Resource\Model
	 */
	public function deleteMessage($receiptHandle)
	{
		return $this->sqsClient->deleteMessage(array(
			'QueueUrl' => $this->queueUrl,
			'ReceiptHandle' => $receiptHandle
		));
	}

	/**
	 * @param string $queueUrl
	 */
	public function setQueueUrl($queueUrl)
	{
		$this->queueUrl = $queueUrl;
		return $this;
	}

	/**
	 * @param array $config
	 */
	private function validateConfigParameters(array $config)
	{
		foreach ($this->mandatoryConfigParameters as $mandatoryParameter) {
			if (!array_key_exists($mandatoryParameter, $config) || empty($config[$mandatoryParameter])) {
				throw new \InvalidArgumentException('Mandatory parameter "' . $mandatoryParameter . '" is missing.');
			}
		}
	}

}
