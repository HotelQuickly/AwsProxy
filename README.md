Aws Proxy Library
===

Library with proxy classes for [AWS SDK PHP library](https://github.com/aws/aws-sdk-php).

For documentation of AWS SDK PHP library see [documentation](http://aws.amazon.com/sdkforphp/).


### Implemented proxies for services
- S3 storage service

### Usage

```php
$config = array(
  accessKeyId => xxx,
  secretAccessKeyId => xxx,
  region => 'ap-southeast-1',
  bucket => 'test-bucket'
);

$s3Client = new \HQ\Aws\S3Proxy($config);

$s3Client->downloadFile($s3FilePath, $localFilePath);
```
