This is an example form that adds a jQuery UI progress bar on upload, then
fades it out on completion. It gets the AWS configuration from app.yml.


```php
<?php

class TestForm extends BaseForm
{

  public function configure()
  {

    $this->widgetSchema['upload'] = new tuiWidgetS3SWFUpload(array(
      'aws_accesskey' => sfConfig::get('app_s3upload_accesskey'),
      'aws_secret'    => sfConfig::get('app_s3upload_secret'),
      'aws_bucket'    => sfConfig::get('app_s3upload_bucket'),
      'rrs'           => true,
      
      'events.js'     => '
        .bind("fileQueued", function(event, file){
          $(this).swfupload("startUpload");
          $(".progress", this).progressbar({
            value: 0,
            complete: function(event, ui) {
              $(this).fadeOut();
            }
          })
          .css({
            "width": "250px",
            "height": "18px"
            
          });
        })
        .bind("uploadProgress", function(event, file, bytesLoaded){
          $(".progress", this).progressbar("value", (bytesLoaded / file.size) * 100);
        })
      ',
      
    ));
    $this->validatorSchema['upload'] = new sfValidatorPass;

  }


  public function getJavascripts()
  {
    $javascripts = parent::getJavascripts();
    $javascripts[] = 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.13/jquery-ui.min.js';
    return $javascripts;
  }
  
  public function getStylesheets()
  {
    $stylesheets = parent::getStylesheets();
    $stylesheets['http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/cupertino/jquery-ui.css'] = 'screen';
    return $stylesheets;
  }

}
?>
```
