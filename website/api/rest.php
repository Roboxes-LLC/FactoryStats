<?php

class Params extends ArrayObject
{
   public function get($key)
   {
      return (isset($this[$key]) ? $this[$key] : "");
   }
}

class Router
{
   public function add($command, $handler)
   {
      $this->handlers[$command] = $handler;
   }
   
   public function route()
   {
      $command = $this->parseCommand($_SERVER["REQUEST_URI"]);
      $params = $this->parseParams();
      
      // Log API requests.
      if ($this->loggingEnabled)
      {
         $now = Time::now("d-m-Y h:i:s a");
         
         $url = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
         
         $logEntry = $now . " " . $url  . "\r\n";
         
         file_put_contents(Router::LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX);
      }

      if (($command) && isset($this->handlers[$command]))
      {
         $this->handlers[$command]($params);
      }
      else
      {
         echo("No handlers specified for \"$command\" command.");
      }
   }
   
   public function setLogging($loggingEnabled)
   {
      $this->loggingEnabled = $loggingEnabled;
   }
   
   private function tokenize($string, $delimiter)
   {
      $tokens = array();
      
      $token = strtok($string, $delimiter);
      
      while ($token !== false)
      {
         $tokens[] = $token;
         
         $token = strtok($delimiter);
      }
      
      return ($tokens);
   }
   
   private function parseCommand($requestUri)
   {
      $baseTokens = $this->tokenize($_SERVER['PHP_SELF'], "/");
      $requestTokens = $this->tokenize($requestUri, "/");
      
      return $requestTokens[count($baseTokens) - 1];
   }
   
   private function parseParams()
   {
      $params = new Params(array());
      
      if ($_SERVER["REQUEST_METHOD"] === "GET")
      {
         foreach ($_GET as $key => $value)
         {
            $params[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
         }
      }
      else if ($_SERVER["REQUEST_METHOD"] === "POST")
      {
         foreach ($_POST as $key => $value)
         {
            $params[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
         }
      }
      
      return $params;
   }
   
   private $handlers = array();
   
   private $loggingEnabled = false;
   
   const LOG_FILE = "rest.log";
}

?>