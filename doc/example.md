A simple example that allows any file up to 2G to be uploaded to an S3 bucket.


```php
<?php

class TestMultiForm extends BaseForm
{

  public function configure()
  {
    
    $this->setWidgets(array(
      'upload' => new tuiWidgetS3SWFUpload(array(
        'aws_accesskey' => 'your access key here',
        'aws_secret'    => 'your secret key here',
        'aws_bucket'    => 'bucket name',
        'rrs'           => 'REDUCED_REDUNDANCY',
        
        'swfupload_file_size_limit' => '2G',
        'swfupload_file_types' => '*.*',
        'swfupload_file_types_description' => 'Video files',
        'collapse_queue_on_init' => false,
      )),
    ));

    $this->setValidators(array(
      'upload' => new sfValidatorPass(),
    ));


  }

}


?>
```
