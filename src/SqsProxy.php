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

		// DynamoDb CLIENT
		$this->sqsClient = SqsClient::factory(array(
			'key'    => $config['accessKeyId'],
			'secret' => $config['secretAccessKey'],
			'region' => $config['region']
		));
	}


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
