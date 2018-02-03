<?php

namespace PHP;

class TArray implements \ArrayAccess, \Iterator{
  private $position = 0;
  private $container;
  private $events = [];

  public function __construct($initial = []){
    $this->container = $initial;
  }

  public function offsetSet($offset, $value) {
    if (is_null($offset)) {
      $this->container[] = $value;
      $this->shootEvent('add', $this->length - 1, $value, $null);
    } else {
      $old_value =  $this->container[$offset];
      $this->container[$offset] = $value;
      $this->shootEvent('update', $offset, $value, $old_value);
    }
  }

  public function offsetExists($offset) {
    return isset($this->container[$offset]);
  }

  public function offsetUnset($offset) {
    unset($this->container[$offset]);
  }

  public function offsetGet($offset) {
    $value = isset($this->container[$offset]) ? $this->container[$offset] : null;
    $this->shootEvent('get', $offset, $value, $value);
    return $value;
  }

  public function rewind() {
    $this->position = 0;
  }

  public function current() {
    return $this->container[$this->position];
  }

  public function key() {
    return $this->position;
  }

  public function next() {
    ++$this->position;
  }

  public function valid() {
    return isset($this->container[$this->position]);
  }

  public function __get($name){
    switch($name){
      case 'length':{
        return count($this->container);
      }
    }
  }

  public function merge(...$arrays){
    foreach($arrays as $arr){
      $this->container = array_merge($this->container, $arr);
    }
  }

  public function map($callback){
    $new_array = new TArray();

    $function_reference = new \ReflectionFunction($callback);
    $argc = count($function_reference->getParameters());

    foreach($this->container as $key => $item){
      if($argc == 1)
        $new_array[$key] = $callback($item);
      else if($argc == 2)
        $new_array[$key] = $callback($item, $key);
      else if($argc == 3)
        $new_array[$key] = $callback($item, $key, $this->container);
    }

    return $new_array;
  }

  public function keys(){
    return array_keys($this->container);
  }

  public function values(){
    return array_values($this->container);
  }

  public function join($separator = ','){
    return implode($separator, $this->container);
  }

  public static function fromString($source, $separator = ','){
    return new self(explode($separator, $source));
  }

  public function indexOf($value, $fromIndex = 0){
    $result = -1;

    if($fromIndex >= $this->length)
      return $result;

    $keys = array_flip($this->keys());

    for($i = $fromIndex; $i < $this->length; $i++){
      if(isset($this->container[$keys[$i]]) && $this->container[$keys[$i]] == $value)
        return $i;
    }

    return $result;
  }

  public function observe($name, $callback){
    $this->events[$name] = $callback;
  }

  private function shootEvent($type, $index, $new_value, $old_value){
    foreach($this->events as $key => $callback){
      $this->trigger($key, $type, $index, $new_value, $old_value);
    }
  }

  public function trigger($name, $type, $index, $new_value, $old_value){
    $this->events[$name]([
      'type' => $type,
      'index' => $index,
      'new_value' => $new_value,
      'old_value' => $old_value
    ]);
  }

  public function unobserve($name){
    unset($this->container[$name]);
  }

  public function getObserveList(){
    return array_keys($this->events);
  }
}

?>