Aws Proxy Library
===

Library with proxy classes for [AWS SDK PHP library](https://github.com/aws/aws-sdk-php).

For documentation of AWS SDK PHP library see [documentation](http://aws.amazon.com/sdkforphp/).


### Implemented proxies for services
- S3 storage service
- DynamoDB

### Usage

#### S3 Storage service
```php
$config = array(
  accessKeyId => xxx,
  secretAccessKeyId => xxx,
  region => 'ap-southeast-1',
  bucket => 'test-bucket' // you can modify later by calling $s3Client->setBucket('another-bucket')
);

$s3Client = new \HQ\Aws\S3Proxy($config);

// $s3FilePath is in format {bucket-name}/{item-key}
$s3Client->downloadFile($s3FilePath, $localFilePath);

// optional 3rd parameter declaring if file should be publicaly accessible (default is false)
$s3Client->uploadFile($sourcePath, $s3FilePath, true);

// determines if given file exists in s3 storage
$s3Client->isFile($s3FilePath);


$s3Client->copyFile($origFilePath, $targetFilePath, true);
$s3Client->moveFile($origFilePath, $targetFilePath);

// returns all files within specified bucket
// optional $prefix parameter specifies path for search
$s3Client->getFiles('/exceptions/');

// saves all files to specified directory
// optional second $prefix parameter to specify path in s3 storage
$s3Client->downloadAllFiles($localDirectoryPath);


$s3Client->deleteFile($filePath);

// return publicly accessible URL for given object
// optional second parameter specify how long should be URL valid
$s3Client->getObjectUrl($key);
```

#### DynamoDB
Proxy automatically converts php arrays to format required by DynamoDB

```php
$config = array(
  accessKeyId => xxx,
  secretAccessKeyId => xxx,
  region => 'ap-southeast-1'
);

$dynamoDbClient = new \HQ\Aws\DynamoDbProxy($config);

// insert data
$this->dynamoDbProxy->setTableName('tableName')
	->insert(array(
		'id' => 23,
		'name' => 'Test name',
		'purpose' => 'To show how'
	));

// update data
$this->dynamoDbProxy
	->setTableName('tableName')
	->update(array('id' => 23), array(
		'name' => 'Correct name',
		'description' => 'New description'
	));

```

Proxy supports primary key consisting with multiple values.

For update, you need to provide array of values for primary key

```php
$this->dynamoDbProxy
	->setTableName('tableName')
	->update(array(
		'id' => 23,
		'secondaryKey' => 'secondaryValue'
	), array(
		'name' => 'Correct name',
		'description' => 'New description'
	));

## The MIT License (MIT)

Copyright (c) 2014 Hotel Quickly Ltd.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
