<?php

class tuiWidgetImageLibrary extends sfWidgetForm
{
  
  public function __construct(array $options = array(), array $attributes = array())
  {
    // Set defaults
    $options = array_merge(
      array(
        // 'needs_multipart' => true,
      ),
      $options
    );
    
    
    parent::__construct($options, $attributes);
  }
  
  
  public function getJavascripts()
  {
    return array(
      'http://ajax.googleapis.com/ajax/libs/jquery/1.6/jquery.min.js',
      '/tuiS3SWFUploadPlugin/js/swfupload.js',
      'http://cachedcommons.org/cache/jquery-swfupload/1.0.0/javascripts/jquery-swfupload-min.js',
    );
  }
  
  // public function getStylesheets()
  // {
  //   return array(
  //     '/tuiWidgetImageLibraryPlugin/css/image_picker.css' => 'screen',
  //     '/tuiWidgetImageLibraryPlugin/css/colours_grey.css' => 'screen',
  //   );
  // }
  
  
  public function configure($options = array(), $attributes = array())
  {
    $this->addRequiredOption('aws_accesskey');
    $this->addRequiredOption('aws_secret');
    $this->addRequiredOption('aws_bucket');
    
    $this->addOption('acl', 'private');
    $this->addOption('key', 'uploads/${filename}');
    $this->addOption('button_image', '/tuiWidgetImageLibraryPlugin/images/XPButtonUploadText_61x22.png');
    $this->addOption('size_limit', '2 GB');
    $this->addOption('file_types', '*.*');
    $this->addOption('file_types_description', 'All files');
    $this->addOption('button_width', 61);
    $this->addOption('button_height', 22);
    $this->addOption('debug', false);
    $this->addOption('upload_limit', 1);
    $this->addOption('queue_limit', 0);

    $this->addOption('policy', array(
      'expiration' => date_create('now +1 month')->format('Y-m-d\T00:00:00\Z'),
      'conditions' => array(
        array('bucket' => $this->getOption('aws_bucket')),
        array('starts-with', '$key', substr($this->getOption('key'), 0, strpos($this->getOption('key'), '/'))),
        array('acl' => $this->getOption('acl')),
        array('success_action_status' => "201"),
        array('starts-with', '$Filename', ''),
        array('content-length-range', 0, $this->convertFileSize($this->getOption('size_limit'))),
      )
    ));
    


    $this->addOption('settings.js', '
      {
          // Backend Settings
          upload_url: {bucket_url},  
          http_success : [ 200, 201, 204 ],

          // File Upload Settings
          file_size_limit : {size_limit}
          file_types : {file_types},
          file_types_description : {file_types_description},
          file_upload_limit : {upload_limit},
          file_queue_limit : {queue_limit},
          file_post_name : "file",

          // Button settings
          button_image_url : {button_image},
          button_placeholder_id : {button_id},
          button_width: {button_width},
          button_height: {button_height},

          // Flash Settings
          flash_url: "/tuiWidgetImageLibraryPlugin/swf/swfupload.swf",  
          debug: {debug},
          post_params: {
            "AWSAccessKeyId": {aws_accesskey},
            "acl": {acl},
            "key": {key},
            "policy": {policy_encoded},
            "signature": {policy_signature},
            "success_action_status" : "201"
          }

      }
    ');


    
    $this->addOption('script.js', '
      <script type="text/javascript">
      $(function(){
          $("{widget_id}").swfupload({settings.js}) 
            .bind("fileQueued", function(event, file){
              $(this).swfupload("startUpload");
            })
            .bind("fileQueueError", function(event, file, errorCode, message){
              alert("File queue error:\n"+message);
            })
            .bind("uploadProgress", function(event, file, bytesLoaded){
              $(".progress", this).text((Math.floor((bytesLoaded / file.size) * 1000) / 10 )+ "%");
            })
            .bind("uploadComplete", function(event, file){
              $(".progress", this).text("Upload complete");

              // Change this callback function to suit your needs
               // $.ajax({
               //   type: "POST",
               //   url: "/upload.php",
               //   data: "name=" + file.name,
               //   async: false,
               // });

              // upload has completed, lets try the next one in the queue
              $(this).swfupload("startUpload");
            });
      });


      </script>
    ');
    
    $this->addOption('template.html', '
      <div id="{widget_id}" class="swfupload-control">
        <div id="{widget_id}_button">Upload</div>
        <div class="progress"></div>
      </div>
      {script.js}
    ');
  }
  
  
  
  public function render($name, $value = null, $attributes = array(), $errors = array())
  {
    
    $id = $this->generateId($name);
    
    $template_vars = array(
      '{widget_id}'              => $id,
      '{script.js}'              => $this->getOption('script.js'),
      '{settings.js}'            => $this->getOption('settings.js'),
      '{policy_signature}'       => $this->generateSignature(),
      '{policy_encoded}'         => base64_encode(json_encode($this->getOption('policy'))),
      '{key}'                    => json_encode($this->getOption('key')),
      '{acl}'                    => json_encode($this->getOption('acl')),
      '{aws_accesskey}'          => json_encode($this->getOption('aws_accesskey')),
      '{debug}'                  => json_encode($this->getOption('debug')),
      '{button_id}'              => json_encode($id.'_button'),
      '{button_image}'           => json_encode($this->getOption('button_image')),
      '{queue_limit}'            => json_encode($this->getOption('queue_limit')),
      '{upload_limit}'           => json_encode($this->getOption('upload_limit')),
      '{file_types_description}' => json_encode($this->getOption('file_types_description')),
      '{file_types}'             => json_encode($this->getOption('file_types')),
      '{size_limit}'             => json_encode($this->getOption('size_limit')),
      '{bucket_url}'             => json_encode($this->getOption('bucket_url')),
    );



    
    // Swap in the template contents and return the resulting HTML
    return strtr($this->getOption('template.html'), $template_vars);
  }
  
  
  public function generateSignature()
  {
    return base64_encode(hash_hmac('sha1', base64_encode(json_encode($this->getOption('policy'))), $this->getOption('aws_secret'), true));
  }
  
  
  public function convertFileSize($size) 
  {
    $multipliers = array(
      'k'  => 1024,
      'kb' => 1024,
      'm'  => 1048576,
      'mb' => 1048576,
      'g'  => 1073741824,
      'gb' => 1073741824,
    );
    
    // get number
    if (!preg_match('/^(\d+)\s*([kbmg]{1,2})?$/i', trim($size), $matches)) {
      throw new Exception("Could not understand file size");
    }
    
    // find multiplier
    if (!$matches[2])
    {
      return $matches[1];
    }
    
    $multiplier = $matches[2] ? $multipliers[ strtolower($matches[2]) ] : false;
    
    // return byte size
    if (!$multiplier)
    {
      throw new Exception("Could not understand file size");
    }
    
    return $matches[1] * $multiplier;
  }


  

}