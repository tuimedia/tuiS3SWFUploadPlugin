tuiS3SWFUploadPlugin
====================

A simple Symfony 1.4 form widget for embedding a SWFUpload button that uploads
to an Amazon S3 bucket. The widget handles generating the S3 policy and
setting up the correct parameters, with reasonable defaults.

Read the tuiWidgetS3SWFUpload class to see the available options. By default,
files are uploaded to your bucket in an "uploads/" folder, and are private.
There is no restriction on file type, and a 2Gb file size restriction.


Installation
------------

1. Download the package and put it into your project's plugins folder, e.g.
	`plugins/tuiS3SWFUploadPlugin` 
2. Add `tuiS3SWFUploadPlugin` to your enabled plugins in the 
	ProjectConfiguration class 
3. Publish the plugin assets: `./symfony plugin:publish-assets`


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

Tips
----

* You can override the default events by setting the `events.js` option when
  you declare the widget in your form class. One reason you might want to do
  this is to bind an action to the `uploadComplete` event to inform your
  Symfony app that a file has been successfully uploaded (or set a hidden
  input in your form).


Known issues
------------

* Pretty sloppy code
* No tests
* Lots of config options, but really only designed to work with a single file 
	upload.
* Requires and includes jQuery and the jquery-swfupload plugin. Should be   
	optional, but isn't. (Well, it's sort of optional - if you don't call 
	`use_javascripts_for_form($form)` in your template.)
* In case you aren't familiar with SWFUpload, it doesn't integrate with your 
	actual form at all. That's why the example sets a "pass" validator on the 
	widget.


TODO
----

* Optionally set the form field to the filename of the uploaded file(s)
* Documentation
* Tests
