<?php
sfApplicationConfiguration::getActive()->loadHelpers('Url');

class tuiWidgetS3SWFUpload extends sfWidgetFormInputFile
{
  protected static $INSTANCE_COUNT = 0;
  
  
  protected function iniSize2Bytes($ini_size)
  {
    if (preg_match('#^([0-9]+?)([gmk])$#i', $ini_size, $tokens))
    {
      $unit=null; $size_val=null;
      isset($tokens[1])&&$size_val  = $tokens[1];
      isset($tokens[2])&&$unit      = $tokens[2];
      if($size_val && $unit)
      {
        switch(strtolower($unit))
        {
          case 'k':
            return $size_val * 1024 . 'B';
          case 'm':
            return $size_val * 1024 * 1024 . 'B';
          case 'g':
            return $size_val * 1024 * 1024 * 1024 . 'B';
        }
      }
    }
    else
    {
      return $ini_size . 'B';
    }
  }
  
  public function configure($options = array(), $attributes = array())
  {
    parent::configure($options, $attributes);

    $this->addOption('reset_on_dialog', true);

    $this->addOption('custom_javascripts', array());

    $this->addOption('prevent_form_submit', true);

    $this->addOption('collapse_queue_on_init', true);

    $this->addOption('send_serialized_values', true);

    $this->addOption('swfupload_upload_url', $_SERVER['REQUEST_URI']);
    $this->addOption('swfupload_post_name', null);
    $this->addOption('swfupload_post_params', '');

    $this->addOption('swfupload_file_types', '*.jpg;*.jpeg;*.gif;*.png');
    $this->addOption('swfupload_file_types_description', 'Web images');

    $this->addOption('swfupload_file_size_limit', ini_get('upload_max_filesize'));
    $this->addOption('swfupload_file_upload_limit', 0);
    $this->addOption('swfupload_file_queue_limit', 0);

    $this->addOption('swfupload_css_path',      public_path('/tuiS3SWFUploadPlugin/css/tuiS3SWFUpload.css'));
    $this->addOption('swfupload_js_path',       public_path('/tuiS3SWFUploadPlugin/js/swfupload.js'));
    $this->addOption('swfupload_handler_path',  public_path('/tuiS3SWFUploadPlugin/js/handlers.js'));
    $this->addOption('swfupload_plugins_dir',   public_path('/tuiS3SWFUploadPlugin/js'));
    $this->addOption('swfupload_button_image_url', public_path('/tuiS3SWFUploadPlugin/images/swfupload-select-button.png'));
    
    $this->addOption('swfupload_button_width', 60);
    $this->addOption('swfupload_button_height', 23);
    $this->addOption('swfupload_button_text', "");
    $this->addOption('swfupload_button_text_style', '');
    $this->addOption('swfupload_button_text_left_padding', 0);
    $this->addOption('swfupload_button_text_top_padding', 0);
    $this->addOption('swfupload_button_disabled', 'false');
    $this->addOption('swfupload_button_cursor', 'SWFUpload.CURSOR.ARROW');
    $this->addOption('swfupload_button_window_mode', 'SWFUpload.WINDOW_MODE.TRANSPARENT');
    $this->addOption('swfupload_button_action', 'SWFUpload.BUTTON_ACTION.SELECT_FILES');

    $this->addOption('swfupload_file_queued_handler', 'fileQueued');
    $this->addOption('swfupload_file_queue_error_handler', 'fileQueueError');
    $this->addOption('swfupload_file_dialog_complete_handler', 'fileDialogComplete');
    $this->addOption('swfupload_upload_start_handler', 'uploadStart');
    $this->addOption('swfupload_upload_progress_handler', 'uploadProgress');
    $this->addOption('swfupload_upload_error_handler', 'uploadError');
    $this->addOption('swfupload_upload_success_handler', 'uploadSuccess');
    $this->addOption('swfupload_upload_complete_handler', 'uploadComplete');
    $this->addOption('swfupload_queue_complete_handler', 'queueComplete');
    $this->addOption('swfupload_swfupload_pre_load_handler', 'preLoad');
    $this->addOption('swfupload_swfupload_load_failed_handler', 'loadFailed');
    $this->addOption('swfupload_minimum_flash_version', '10.0.0');
    

    $this->addRequiredOption('aws_accesskey');
    $this->addRequiredOption('aws_secret');
    $this->addRequiredOption('aws_bucket');
    
    $this->addOption('acl', 'private');
    $this->addOption('key', 'uploads/${filename}');
    $this->addOption('rrs', false);
    
    $this->addOption('swfupload_flash_url', public_path('/tuiS3SWFUploadPlugin/swf/swfupload.swf'));
    $this->addOption('swfupload_js_path', public_path('/tuiS3SWFUploadPlugin/js/swfupload.js'));
    $this->addOption('send_serialized_values', false);
    $this->addOption('swfupload_post_name', 'file');

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
    $this->setOption('swfupload_upload_url', 'https://'.$this->getOption('aws_bucket').'.s3.amazonaws.com/');
    
    
    self::$INSTANCE_COUNT++;
    //*.jpg;*.gif
    $extensions = is_array($this->getOption('swfupload_file_types')) ?
      implode(';', $this->getOption('swfupload_file_types')):
      $this->getOption('swfupload_file_types');
    $extensions_description = $this->getOption('swfupload_file_types_description');

    // $output = parent::render($name, $value, $attributes, $errors);
    $output = '';

    $widget_id  = $this->getAttribute('id') ? $this->getAttribute('id') : $this->generateId($name);
    $button_id  = $widget_id . "_swfupload_target";

    $swfupload_button_image_url = $this->getOption('swfupload_button_image_url') === null ? '' : public_path($this->getOption('swfupload_button_image_url'));

    $max_size = $this->iniSize2Bytes($this->getOption('swfupload_file_size_limit'));

    $swfupload_post_name = $this->getOption('swfupload_post_name') === null ? $name : $this->getOption('swfupload_post_name');

    $send_serialized_values = $this->getOption('send_serialized_values') ? 'true' : 'false';

    $collapse_queue_on_init = $this->getOption('collapse_queue_on_init') ? 'true' : 'false';

    $prevent_form_submit = $this->getOption('prevent_form_submit') ? 'true' : 'false';

    $reset_on_dialog = $this->getOption('reset_on_dialog') ? 'true' : 'false';

    $output .= <<<EOF
      <div class="fieldset flash" id="fsUploadProgress">
        <span class="legend">Upload Queue</span>
      </div>
      <div id="divStatus">0 Files Uploaded</div>
      <div>
          <span id="{$button_id}"></span>
          <input id="btnCancel" type="button" value="Cancel All Uploads" onclick="swfu.cancelQueue();" disabled="disabled" style="margin-left: 2px; font-size: 8pt; height: 29px;" />
      </div>
      <script type="text/javascript">
        //<![CDATA[
        var swfu;
        
        SWFUpload.onload = function()
        {
          swfu = new SWFUpload
          ({
            upload_url : "{$this->getOption('swfupload_upload_url')}",
            flash_url : "{$this->getOption('swfupload_flash_url')}",
            button_placeholder_id : "{$button_id}",
            file_post_name : "{$swfupload_post_name}",
            post_params :
            {
              {$this->getOption('swfupload_post_params')}
            },
            custom_settings :
            {
              widget_id: "{$widget_id}",
              send_serialized_values: $send_serialized_values,
              collapse_queue_on_init: $collapse_queue_on_init,
              prevent_form_submit: $prevent_form_submit,
              reset_on_dialog: $reset_on_dialog,
              progressTarget : "fsUploadProgress",
              cancelButtonId : "btnCancel"
            },
            use_query_string : false,
            requeue_on_error : false,
            assume_success_timeout : 0,
            file_types : "{$extensions}",
            file_types_description: "{$extensions_description}",
            file_size_limit : "{$max_size}",
            file_upload_limit : {$this->getOption('swfupload_file_upload_limit')},
            file_queue_limit : {$this->getOption('swfupload_file_queue_limit')},
            debug : false,
            prevent_swf_caching : true,
            preserve_relative_urls : false,

            button_image_url : "{$swfupload_button_image_url}",
            button_width : {$this->getOption('swfupload_button_width')},
            button_height : {$this->getOption('swfupload_button_height')},
            button_text : '{$this->getOption('swfupload_button_text')}',
            button_text_style : '{$this->getOption('swfupload_button_style')}',
            button_text_left_padding : {$this->getOption('swfupload_button_text_left_padding')},
            button_text_top_padding : {$this->getOption('swfupload_button_text_top_padding')},
            button_disabled : {$this->getOption('swfupload_button_disabled')},
            button_cursor : {$this->getOption('swfupload_button_cursor')},
            button_window_mode : {$this->getOption('swfupload_button_window_mode')},
            button_action : {$this->getOption('swfupload_button_action')},

            file_queued_handler : {$this->getOption('swfupload_file_queued_handler')},
            file_queue_error_handler : {$this->getOption('swfupload_file_queue_error_handler')},
            file_dialog_complete_handler : {$this->getOption('swfupload_file_dialog_complete_handler')},
            upload_start_handler : {$this->getOption('swfupload_upload_start_handler')},
            upload_progress_handler : {$this->getOption('swfupload_upload_progress_handler')},
            upload_error_handler : {$this->getOption('swfupload_upload_error_handler')},
            upload_success_handler : {$this->getOption('swfupload_upload_success_handler')},
            upload_complete_handler : {$this->getOption('swfupload_upload_complete_handler')},
            queue_complete_handler : {$this->getOption('swfupload_queue_complete_handler')},

            // swf object
            swfupload_pre_load_handler : {$this->getOption('swfupload_swfupload_pre_load_handler')},
            swfupload_load_failed_handler : {$this->getOption('swfupload_swfupload_load_failed_handler')},

            minimum_flash_version : "{$this->getOption('swfupload_minimum_flash_version')}"
          });
        }
        //]]>
      </script>
EOF;
    return $output;

  }
  
  
  public function getJavaScripts()
  {
    $js = array(
      $this->getOption('swfupload_js_path'),
      $this->getOption('swfupload_handler_path'),
      $this->getOption('swfupload_plugins_dir') . '/swfupload.queue.js',
      $this->getOption('swfupload_plugins_dir') . '/fileprogress.js',
    );

    return array_merge($js, $this->getOption('custom_javascripts'));
  }
  
  public function getStylesheets()
  {
    return array(
      $this->getOption('swfupload_css_path') => 'all'
    );
  }
  
}
