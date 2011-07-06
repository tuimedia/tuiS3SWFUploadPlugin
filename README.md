tuiS3SWFUploadPlugin
====================

This widget extends sfWidgetFormInputSWFUploadPlugin (which you'll need to 
install yourself) and makes necessary changes to allow the widget to upload
to Amazon S3. It handles generating the S3 policy and signature, overrides 
a few of the sfWidgetFormInputSWFUploadPlugin defaults, and disables the 
swfupload.cookie.js plugin, which is a common cause of upload issues. 
Finally, it uses an updated SWFUpload.js version, 2.5beta4.

The configuration and use is the same as sfWidgetFormInputSWFUploadPlugin, 
with only a few additional options:

`aws_accesskey`: (string)Your Amazon AWS access key
`aws_secret`: (string) Your Amazon AWS secret key
`aws_bucket`: (string) The bucket name to upload to
`rrs`: (boolean, default false) Whether to use Reduced Redundancy Storage
`key`: (string, default `uploads/${filename}`) The location to upload to - `${filename}` is replaced with the uploaded file's original name by Amazon S3.
`acl`: (string, default `private`) The ACL to set on the uploaded file


Disclaimer
----------

This plugin is unmaintained and unsupported. It was written in haste, and may
not work for you. It worked for us when we needed it, and you may find it
useful, but we make no guarantees.

Installation
------------

1. Download and install [sfWidgetFormInputSWFUploadPlugin][]
2. Download this package and put it into your project's plugins folder, e.g.
	`plugins/tuiS3SWFUploadPlugin` 
3. Add `tuiS3SWFUploadPlugin` to your enabled plugins in the 
	ProjectConfiguration class 
4. Publish the plugin assets: `./symfony plugin:publish-assets`

[sfWidgetFormInputSWFUploadPlugin]: http://www.symfony-project.org/plugins/sfWidgetFormInputSWFUploadPlugin


Example usage
-------------

```php
<?php

class TestForm extends BaseForm
{
  
  public function configure()
  {
    
    $this->widgetSchema['upload'] = new tuiWidgetS3SWFUpload(array(
      'aws_accesskey' => 'access-key-here',
      'aws_secret'    => sfConfig::get('app_s3_secret', '(for example)'),
      'aws_bucket'    => 'example',
    ));
    $this->validatorSchema['upload'] = new sfValidatorPass;
    
  }
  
  
}
?>
```


Known issues
------------

* In case you aren't familiar with SWFUpload, it doesn't integrate with your 
	actual form at all. That's why the example sets a "pass" validator on the 
	widget.


TODO
----

* Optionally set the form field to the filename of the uploaded file(s)
* Documentation
* Tests
