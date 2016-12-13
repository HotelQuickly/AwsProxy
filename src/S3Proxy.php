<?php

namespace HQ\AwsProxy;

use Aws\S3\S3Client;


/**
 *  Proxy for Amazon storage web service (AWS S3)
 *
 *  @author Josef Nevoral <josef.nevoral@hotelquickly.com>
 *  @see https://github.com/aws/aws-sdk-php
 *
 */
class S3Proxy
{
	const PRIVATE_ACCESS = 'private';
	const PUBLIC_READ = 'public-read';

	/** Aws\S3\S3Client */
	private $s3Client;

	/** string bucket name used to store data */
	private $bucket;

	private $mandatoryConfigParameters = array(
		'accessKeyId',
		'secretAccessKey',
		'region'
	);

	public function __construct(array $config)
	{
		$this->validateConfigParameters($config);

		// S3 CLIENT
		$this->s3Client = new S3Client(array(
			'region' => $config['region'],
			'version' => '2006-03-01',
			'credentials' => array(
				'key'    => $config['accessKeyId'],
				'secret' => $config['secretAccessKey'],
			)
		));
		$this->bucket = !empty($config['bucket']) ? $config['bucket'] : '';

		// REGISTER STREAM WRAPPER
		$this->registerStreamWrapper();
	}

	public function getBucket()
	{
		if (empty($this->bucket)) {
			throw new \Exception('S3 bucket was not defined. Please define it by calling setBucket() method.');
		}
		return $this->bucket;
	}


	public function setBucket($bucket)
	{
		$this->bucket = $bucket;
		return $this;
	}


	/**
	 * Registers stream wrapper to be able use s3:// protocol
	 * @return [type] [description]
	 */
	public function registerStreamWrapper() {
		$this->s3Client->registerStreamWrapper();
	}

	/**
	 * Uploads file to amazon s3
	 * @param  string $sourcePath path name to file on local storage
	 * @param  string $targetPath      path in which would be image saved in S3
	 * @return
	 */
	public function uploadFile($sourcePath, $targetPath, $publicAccess = true)
	{
		// check if the file does not already exists, if exist, don't upload
		if ($this->isFile($targetPath)) {
			return false;
		}
		return $this->s3Client->putObject(array(
			'Bucket'     => $this->getBucket(),
			'Key'        => $targetPath,
			'SourceFile' => $sourcePath,
			'ACL'        => $publicAccess ? self::PUBLIC_READ : self::PRIVATE_ACCESS
		));
	}

	/**
	 * Store string giving in parameter into file on s3
	 *
	 * @param $filePath
	 * @param $content
	 * @return
	 */
	public function saveContentToFile($filePath, $content)
	{
		return $this->s3Client->putObject([
			'Bucket' => $this->getBucket(),
			'Key' => $filePath,
			'Body' => $content
		]);
	}

	/**
	 * Returns string content of file saved in S3
	 *
	 * @param $filePath
	 * @return string
	 * @throws \Exception
	 */
	public function getFileContent($filePath)
	{
		$result = $this->s3Client->getObject(array(
			'Bucket' => $this->getBucket(),
			'Key'    => $filePath,
		));

		return (string) $result['Body'];
	}

	/**
	 * Checks if file exists on s3 storage
	 * @param  string  $filePath path to file on s3 storage
	 * @return boolean
	 */
	public function isFile($filePath)
	{
		if (substr($filePath, 0, 1) != '/') {
			$filePath = '/'.$filePath;
		}
		return is_file('s3://'.$this->getBucket().$filePath);
	}

	/**
	 * Downloads file from s3 and saves to local disk
	 * @param  string $filePath   path to file on s3
	 * @param  string $targetPath absolute path where to save file on local storage
	 * @return bool
	 */
	public function downloadFile($filePath, $targetPath)
	{
		$result = $this->s3Client->getObject(array(
			'Bucket' => $this->getBucket(),
			'Key'    => $filePath,
			'SaveAs' => $targetPath
		));

		return $result;
	}

	/**
	 * Copies files on s3 storage
	 * @param  string $origFilePath   The name of the source bucket and key name of the source object, separated by a slash (/). Must be URL-encoded
	 * @param  string $targetFilePath new file path
	 * @return mixed
	 */
	public function copyFile($origFilePath, $targetFilePath, $publicAccess = true)
	{
		$origFilePath = str_replace('//', '/', $origFilePath);
		$targetFilePath = str_replace('//', '/', $targetFilePath);
		$result = $this->s3Client->copyObject(array(
			'Bucket' => $this->getBucket(),
			'CopySource' => $origFilePath,
			'Key'    => $targetFilePath,
			'ACL'    => $publicAccess ? self::PUBLIC_READ : self::PRIVATE_ACCESS
		));

		return $result;
	}

	/**
	 * List all files in bucket with given prefix
	 * @param  string $prefix path on S3
	 * @return array of items
	 */
	public function getFiles($prefix = '')
	{
		return $this->getFilesIterator($prefix)->toArray();
	}

	/**
	 * List all files in bucket with given prefix
	 * @param  string $prefix path on S3
	 * @return Iterator
	 */
	public function getFilesIterator($prefix = '', $marker = '')
	{
		return $this->s3Client->getIterator('ListObjects', array(
			'Bucket' => $this->getBucket(),
			'Prefix' => $prefix,
			'Marker' => $marker
		));
	}

	/**
	 * Download all files and saves to local directory
	 * Download only if file does not already exists
	 * @param  string $localDir
	 * @param  string $prefix   relative path to files on s3 storage
	 * @return bool
	 */
	public function downloadAllFiles($localDir, $prefix)
	{
		$files = $this->getFiles($prefix);

		foreach ($files as $key => $file) {
			if ($file['Size'] == 0) {
				continue;
			}
			$filename = basename($file['Key']);
			if (!is_file($localDir . $filename)) {
				$this->downloadFile($file['Key'], $localDir . $filename);
			}
		}

		return true;
	}

	public function deleteFile($filePath)
	{
		if (substr($filePath, 0, 1) != '/') {
			$filePath = '/'.$filePath;
		}
		$result = $this->s3Client->deleteObject(array(
			'Bucket' => $this->getBucket(),
			'Key'    => $filePath
		));

		return $result;
	}

	/**
	 * Returns the URL to an object identified by its key.
	 * If an expiration time is provided, the URL will be
	 * signed and set to expire at the provided time.
	 * @param Object key
	 * @param Expiration time
	 */
	public function getObjectUrl($key, $expires = null)
	{
		return $this->s3Client->getObjectUrl($this->getBucket(), $key, $expires);
	}

	/**
	 * @param string path to file on s3
	 * @param array
	 * @return Aws\Result
	 */
	public function getObject($key, array $options = array())
	{
		return $this->s3Client->getObject(array(
			'Bucket' => $this->getBucket(),
			'Key'    => $key,
		) + $options);
	}

	/**
	 *	Creates copy of an object and delete the original.
	 * @param  string $origFilePath	  Key name of the source object
	 * @param  string $targetFilePath New file path
	 */
	public function moveFile($origFilePath, $targetFilePath) {
		$this->copyFile($this->getBucket() . "/" . $origFilePath, $targetFilePath);
		$this->deleteFile($origFilePath);
	}


	private function validateConfigParameters(array $config)
	{
		foreach ($this->mandatoryConfigParameters as $mandatoryParameter) {
			if (!array_key_exists($mandatoryParameter, $config) || empty($config[$mandatoryParameter])) {
				throw new \InvalidArgumentException('Mandatory parameter "' . $mandatoryParameter . '" is missing.');
			}
		}
	}
}

