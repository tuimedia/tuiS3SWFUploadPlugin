<?php
sfApplicationConfiguration::getActive()->loadHelpers('Url');

class tuiWidgetS3SWFUpload extends sfWidgetFormInputSWFUpload
{
  
  public function configure($options = array(), $attributes = array())
  {
    $result = parent::configure($options, $attributes);

    $this->addRequiredOption('aws_accesskey');
    $this->addRequiredOption('aws_secret');
    $this->addRequiredOption('aws_bucket');
    
    $this->addOption('acl', 'private');
    $this->addOption('key', 'uploads/${filename}');
    $this->addOption('rrs', false);
    
    $this->setOption('swfupload_flash_url', public_path('/tuiS3SWFUploadPlugin/swf/swfupload.swf'));
    $this->setOption('swfupload_js_path', public_path('/tuiS3SWFUploadPlugin/js/swfupload.js'));
    $this->setOption('send_serialized_values', false);
    $this->setOption('swfupload_post_name', 'file');
    
    return $result;
  }
  

  public function render($name, $value = null, $attributes = array(), $errors = array())
  {
    
    $s3_policy = array(
      'expiration' => date_create('now +1 month')->format('Y-m-d\T00:00:00\Z'),
      'conditions' => array(
        array('bucket' => $this->getOption('aws_bucket')),
        array('starts-with', '$key', substr($this->getOption('key'), 0, strpos($this->getOption('key'), '/'))),
        array('acl' => $this->getOption('acl')),
        array('success_action_status' => "200"),
        array('starts-with', '$Filename', ''),
        array('content-length-range', 0, 2 * 1024 * 1024 * 1024), // 2 Gig
        array('x-amz-storage-class' => $this->getOption('rrs') ? 'REDUCED_REDUNDANCY' : 'STANDARD'),
      )
    );
    
    $template_vars = array(
      '{aws_accesskey}'  => $this->getOption('aws_accesskey'),
      '{aws_secret}'     => $this->getOption('aws_secret'),
      '{aws_bucket}'     => $this->getOption('aws_bucket'),
      '{rrs}'            => $this->getOption('rrs') ? 'REDUCED_REDUNDANCY' : 'STANDARD',
      '{key}'            => $this->getOption('key'),
      '{acl}'            => $this->getOption('acl'),
      '{policy_encoded}' => base64_encode(json_encode($s3_policy)),
      '{signature}'      => base64_encode(hash_hmac('sha1', base64_encode(json_encode($s3_policy)), $this->getOption('aws_secret'), true)),
    );
    $template_vars = array_map('json_encode', $template_vars);


    $param_template = '
      "AWSAccessKeyId": {aws_accesskey},
      "acl": {acl},
      "key": {key},
      "policy": {policy_encoded},
      "signature": {signature},
      "success_action_status" : "200",
      "x-amz-storage-class" : {rrs}
    ';
    
    
    $this->setOption('swfupload_post_params', strtr($param_template, $template_vars));
    $this->setOption('swfupload_upload_url', 'http://'.$this->getOption('aws_bucket').'.s3.amazonaws.com/');
    
    
    return parent::render($name, $value, $attributes, $errors);
    
  }
  
  
  public function getJavaScripts()
  {
    $js = parent::getJavaScripts();
    
    // Remove the cookie script from the list
    return preg_grep('/swfupload\.cookies\.js$/', $js, PREG_GREP_INVERT);
  }
  
  
}