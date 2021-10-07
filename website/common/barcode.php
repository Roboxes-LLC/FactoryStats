<?php
class Barcode
{
   const UNKNOWN_ORDER = "";
   const UNKNOWN_SCHEDULE = 0;
   const UNKNOWN_SEQUENCE = 0;
   
   public $order;
   public $schedule;
   public $sequence;
   
   public function __construct(
      $order = Barcode::UNKNOWN_ORDER, 
      $schedule = Barcode::UNKNOWN_SCHEDULE,
      $sequence = Barcode::UNKNOWN_SEQUENCE)
   {
      $this->order = $order;
      $this->schedule = $schedule;
      $this->sequence = $sequence;
   }
   
   public static function parse($encodedBarcode)
   {
      $barcode = new Barcode();
      
      $tokens = explode('.', $encodedBarcode);
      
      if (count($tokens) == 3)
      {
         $barcode->order = $tokens[0];
         $barcode->schedule = intval($tokens[1]);
         $barcode->sequence = intval($tokens[2]);
      }
      
      return ($barcode);
   }
   
   public function encode()
   {
      // Flexscreen barcode format:
      // order.schedule.sequence
      
      $encoded = "";
      
      if ($this->isValid())
      {
         $encoded = "$this->order.$this->schedule.$this->sequence";
      }
      
      return ($encoded);
   }
   
   public function isValid()
   {
      return (($this->order != Barcode::UNKNOWN_ORDER) &&
              ($this->schedule != Barcode::UNKNOWN_SCHEDULE) &&
              ($this->sequence != Barcode::UNKNOWN_SEQUENCE));
   }
}

/*
$barcode = Barcode::parse("P123.456.1");

echo "Order: $barcode->order <br>";
echo "Schedule: $barcode->schedule <br>";
echo "Sequence: $barcode->sequence <br>";
echo "isValid: " . ($barcode->isValid() ? "true" : "false") . "<br>";
echo "Encoded: " . $barcode->encode() . "<br><br>";

$barcode = Barcode::parse("P123.456");

echo "Order: $barcode->order <br>";
echo "Schedule: $barcode->schedule <br>";
echo "Sequence: $barcode->sequence <br>";
echo "isValid: " . ($barcode->isValid() ? "true" : "false") . "<br>";
echo "Encoded: " . $barcode->encode() . "<br><br>";
*/ 