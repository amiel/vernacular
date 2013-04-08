<?php

class VernacularImage{
  private $width, $height, $crop, $file, $editor;
  public $directory = 'images';

  public function __construct($file){
    $this->setup();
    $this->file = $this->sanitize_file_path($file);
    $this->editor = wp_get_image_editor($this->source_file());

    return $this;
  }

  public function crop($width, $height, $crop = true){
    $this->width = (int)$width;
    $this->height = (int)$height;
    $this->crop = $crop;

    if(!$this->is_cached()){
      $this->editor->resize($this->width, $this->height, $this->crop);
      $this->editor->save( $this->output_file_with_path() );
    }

    return $this->output_url() . $this->filename();
  }

  private function source_file(){
    return ABSPATH . $this->file;
  }

  private function filename(){
    list($basename, $extension) = explode(".", basename($this->source_file()));

    $filename = $basename;
    $filename .= "-";
    $filename .= $this->cache_key();

    $filename .= ".";
    $filename .= $extension;

    return $filename;
  }

  private function cache_key(){
    $cache_key = $this->width;
    $cache_key .= "x";
    $cache_key .= $this->height;

    if($this->crop){ $cache_key .= 'c'; }

    $cache_key .= "-";
    $cache_key .= filemtime($this->source_file());

    return $cache_key;
  }

  private function output_url(){
    return WP_CONTENT_URL . '/' . $this->directory . '/';
  }

  private function output_path(){
    return WP_CONTENT_DIR . '/' . $this->directory . '/';
  }

  private function sanitize_file_path($file){
    return str_replace( get_site_url(), '', $file);
  }

  private function output_file_with_path(){
    return $this->output_path() . $this->filename();
  }


  private function is_cached(){
    return file_exists($this->output_file_with_path());
  }

  private function setup(){
    if(!is_dir($this->output_path())){
      mkdir($this->output_path());
    }

    if(!is_writable($this->output_path())){
      chmod($this->output_path(), 0777);
    }
  }

  public function clear_cache(){
    array_map('unlink', glob($this->output_path()."*"));
  }
}

class_alias('VernacularImage', '_image');

function the_resized_post_thumbnail($width = 500, $height = 300, $crop = true){
  $upload_dir = wp_upload_dir();

  $attachment = wp_get_attachment_metadata(get_post_thumbnail_id(), 'original');
  $image_path = $upload_dir['baseurl'] . '/' . $attachment['file'];


  $image = new VernacularImage($image_path);
  $cropped_image = $image->crop($width, $height, $crop);

  echo '<img src="' . $cropped_image . '" alt="">';
}

function VernacularImage($image){
  return new VernacularImage($image);
}

function _image($image){
  return new VernacularImage($image);
}