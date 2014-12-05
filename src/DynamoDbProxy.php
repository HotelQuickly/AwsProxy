<?php


namespace HQ\AwsProxy;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Enum\AttributeAction;
use Aws\DynamoDb\Enum\Type;
use Aws\DynamoDb\Marshaler;
use Nette\Diagnostics\Debugger;

/**
 * Class DynamoDbProxy
 *
 * @author Josef Nevoral <josef.nevoral@hotelquickly.com>
 */
class DynamoDbProxy
{
	private $mandatoryConfigParameters = array(
		'accessKeyId',
		'secretAccessKey',
		'region'
	);

	/** @var DynamoDbClient  */
	private $dynamoDbClient;

	/**
	 * Marshals JSON documents or array representations of JSON documents
	 * into the parameter structure required by DynamoDB. Also allows for unmarshaling.
	 *
	 * @var Marshaler
	 * @see http://docs.aws.amazon.com/aws-sdk-php/latest/class-Aws.DynamoDb.Marshaler.html
	 */
	private $marshaler;

	/**
	 * DynamoDB table name
	 * @var string
	 */
	private $tableName;

	public function __construct(array $config)
	{
		$this->validateConfigParameters($config);

		// DynamoDb CLIENT
		$this->dynamoDbClient = DynamoDbClient::factory(array(
			'key'    => $config['accessKeyId'],
			'secret' => $config['secretAccessKey'],
			'region' => $config['region']
		));
	}


	public function insert(array $data)
	{
		if (!isset($data['id'])) {
			throw new \InvalidArgumentException('Primary column "id" is missing in data to save. Please generate it and provide with data.');
		}

		return $this->dynamoDbClient->putItem(array(
			'TableName' => $this->tableName,
			'Item' => $this->getMarshaler()->marshalItem($data)
		));
	}


	public function update(array $primaryKey, array $data)
	{
		return $this->dynamoDbClient->updateItem(array(
			'TableName' => $this->tableName,
			"Key" => $this->preparePrimaryKey($primaryKey),
			'AttributeUpdates' => $this->prepareDataForUpdate($data)
		));
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

	/**
	 * @return Marshaler
	 */
	public function getMarshaler()
	{
		if (!$this->marshaler) {
			$this->marshaler = new Marshaler();
		}
		return $this->marshaler;
	}

	/**
	 * @param string $tableName
	 */
	public function setTableName($tableName)
	{
		$this->tableName = $tableName;
		return $this;
	}


	/**
	 * Transform array data to DynamoDb format
	 *
	 * @param array $data
	 * @return array
	 */
	private function prepareDataForUpdate(array $data)
	{
		$result = array();
		foreach ($data as $key => $value) {
			$result[$key] = array(
				'Action' => AttributeAction::PUT,
				'Value' => array(
					$this->getValueType($value) => $value
				)
			);
		}
		Debugger::barDump($result);
		return $result;
	}


	/**
	 * @param $value
	 *
	 * @return string
	 */
	private function getValueType($value)
	{
		if (is_numeric($value)) {
			return Type::NUMBER;
		}

		if (is_array($value)) {
			return Type::STRING_SET;
		}

		return Type::STRING;
	}


	/**
	 * @param array $primaryKey
	 *
	 * @return array
	 */
	private function preparePrimaryKey(array $primaryKey)
	{
		$result = array();
		foreach ($primaryKey as $key => $value) {
			$result[$key] = array(
				$this->getValueType($value) => $value
			);
		}
Debugger::barDump($result);
		return $result;
	}


}