<?php

// Remove if PHP 8.4.0+ is supported.
// https://www.php.net/manual/en/function.array-any.php
if (! function_exists('array_any')) {
   function array_any(array $array, callable $callable) {
      foreach ($array as $key => $value) {
         if ($callable($value, $key))
            return true;
      }
      return false;
   }
}

class CsvFile
{
   const MAX_LINE_LENGTH = 0;
   
   const SEPERATOR = ",";
   
   const ENCLOSURE = '"';
   
   const ESCAPE = "\\";
   
   const EOL = "\n";

   public function __construct($numRows = 0, $numCols = 0)
   {
      $this->filename = null;
      $this->headings = array();
      $this->data = array();
      $this->annotation = null;
      
      for ($row = 0; $row < $numRows; $row++)
      {
         $newRow = $this->data[] = array();
         
         for ($col = 0; $col < $numRows; $col++)
         {
            $newRow[] = null;
         }
      }
      
      $this->format = (object)["separator" => ",", "enclosure" => '"', "escape" => "\\", "eol" => "\n"];
   }
   
   public static function load($filename, $expectHeader)
   {
      $csvFile = null;
      
      try
      {
         if (($file = fopen($filename, "r")) !== false)
         {
            $csvFile = new CsvFile();
            
            $csvFile->filename = basename($filename);
             
            if ($expectHeader)
            {
               $csvFile->headings = fgetcsv($file, CsvFile::MAX_LINE_LENGTH, CsvFile::SEPERATOR);
            }
            
            while (($row = fgetcsv($file, CsvFile::MAX_LINE_LENGTH, CsvFile::SEPERATOR)) !== false)
            {
               $csvFile->data[] = $row;
            }
   
            fclose($file);
         }
      }
      catch (Exception $e)
      {
        // File does not exist, or open failed.  
      }
      
      return ($csvFile);
   }
   
   public static function save($csvFile, $filename)
   {
      $returnStatus = false;
         
      $file = fopen($filename, 'w');
      
      if ($file)
      {
         if ($csvFile->hasHeadings())
         {
            if (PHP_VERSION_ID >= 80100)
            {
               fputcsv($file, $csvFile->getHeadings(), $csvFile->format->separator, $csvFile->format->enclosure, $csvFile->format->escape, $csvFile->format->eol);
            }
            else
            {
               CsvFile::fputcsv_custom($file, $csvFile->getHeadings(), $csvFile->format->separator, $csvFile->format->enclosure, $csvFile->format->escape, $csvFile->format->eol);
            }
         }
         
         foreach ($csvFile->data as $row)
         {
            if (PHP_VERSION_ID >= 80100)
            {
               fputcsv($file, $row, $csvFile->format->separator, $csvFile->format->enclosure, $csvFile->format->escape, $csvFile->format->eol);
            }
            else
            {
               CsvFile::fputcsv_custom($file, $row, $csvFile->format->separator, $csvFile->format->enclosure, $csvFile->format->escape, $csvFile->format->eol);
            }
         }
      
         fclose($file);
         
         $returnStatus = true;
      }
      
      return ($returnStatus);
   }
   
   public function getFilename()
   {
      return ($this->filename);
   }
   
   public function toString()
   {
      $file = fopen('php://memory', 'r+');
      
      if ($this->hasHeadings())
      {
         if (PHP_VERSION_ID >= 80100)
         {
            fputcsv($file, $this->headings, $this->format->separator, $this->format->enclosure, $this->format->escape, $this->format->eol);
         }
         else
         {
            CsvFile::fputcsv_custom($file, $this->headings, $this->format->separator, $this->format->enclosure, $this->format->escape, $this->format->eol);
         }
      }
      
      foreach($this->data as $row)
      {
         if (PHP_VERSION_ID >= 80100)
         {
            fputcsv($file, $row, $this->format->separator, $this->format->enclosure, $this->format->escape, $this->format->eol);
         }
         else
         {
            CsvFile::fputcsv_custom($file, $row, $this->format->separator, $this->format->enclosure, $this->format->escape, $this->format->eol);
         }
      }
      
      rewind($file);
      $csvString = stream_get_contents($file);
      
      fclose($file);
      
      return ($csvString);
   }
   
   public function toHtml($tableId = "", $tableClassList = "")
   {
      $html = "<table id=\"$tableId\" class=\"$tableClassList\">";

      if ($this->hasHeadings())
      {
         $html .= "<thead><tr>";
         foreach ($this->headings as $value)
         {
            $html .= "<th>$value</th>";
         }
         $html .= "</tr>";
      }
      
      $html .= "</thead><tbody>";
      
      foreach ($this->data as $rowIndex => $row)
      {
         $html .= "<tr>";

         foreach ($row as $colIndex => $value)
         {
            $annotated = "";
            $annotation = "";
            $annotationValue = $this->getAnnotationAt($rowIndex, $colIndex);
            if ($annotationValue)
            {
               $annotated = "class=\"annotated\"";
               $annotation = "data-annotation=\"$annotationValue\"";
            }
            
            $html .= "<td $annotated $annotation>$value</td>";
         }
         
         $html .= "</tr>";
      }
      
      $html .= "</tbody>";
      
      $html .= "</table>";
      
      return ($html);
   }
   
   public function get($row, $col)
   {
      $data = null;
      
      if ($this->validCell($row, $col))
      {
         $data = $this->data[$row][$col];
      }
      
      return ($data);
   }
   
   public function set($row, $col, $data)
   {
      if ($this->validCell($row, $col))
      {
         $this->data[$row][$col] = $data;
      }
   }
   
   public function getRow($row)
   {
      $data = null;
      
      if ($this->validRow($row))
      {
         $data = $this->data[$row];
      }
      
      return ($data);
   }
   
   public function addRow($row)
   {
      $this->data[] = $row;
   }
   
   public function removeColumn($colIndex)
   {
      if ($colIndex < $this->numCols())
      {
         unset($this->headings[$colIndex]);
         $this->headings = array_values($this->headings);
         
         for ($rowIndex = 0; $rowIndex < $this->numRows(); $rowIndex++)
         {
            $row = $this->getRow($rowIndex);
            
            unset($row[$colIndex]);
            $this->data[$rowIndex] = array_values($row);
         }
      }
   }
   
   public function setHeadings($headings)
   {
      $this->headings = $headings;
   }
   
   public function getHeadings()
   {
      return ($this->headings);
   }
   
   public function hasHeadings()
   {
      return (!empty($this->headings));
   }
   
   public function createHeadings()
   {
      if ($this->numCols() > 0)
      {
         $headings = array();
         
         for ($colIndex = 0; $colIndex < $this->numCols(); $colIndex++)
         {
            $headings[$colIndex] = CsvFile::getColumn($colIndex);
         }
         
         $this->setHeadings($headings);
      }
   }
   
   public function numRows()
   {
      return (count($this->data));
   }
   
   public function numCols()
   {
      $numColumns = 0;
      
      if ($this->hasHeadings())
      {
         $numColumns = count($this->getHeadings());
      }
      else if (!empty($this->data))
      {
         $numColumns = count($this->data[0]);
      }

      return ($numColumns);
   }
   
   public static function getIndex($column)
   {
      $index = false;
      
      $base26 = 0;
      
      if (is_string($column) && (strlen($column) > 0) && ctype_alpha($column))
      {
         for ($strPos = 0; $strPos < strlen($column); $strPos++)
         {
            $ordinalValue = (ord(strtoupper($column[$strPos])) - ord("A") + 1);
            
            $multiplier = pow(26, (strlen($column) - $strPos - 1));
            
            $base26 += ($ordinalValue * $multiplier);
         }
         
         $index = ($base26 > 0) ? ($base26 - 1) : 0;
      }
      
      return ($index);
   }
   
   public static function getColumn($index)
   {
      // https://stackoverflow.com/questions/3302857/algorithm-to-get-the-excel-like-column-name-of-a-number
      
      for ($column = ""; $index >= 0; $index = (intval($index / CsvFile::BASE_26) - 1))
      {
         $column = chr(($index % 26) + 0x41) . $column;
      }
      
      return ($column);
   }
   
   public function setAnnotation($annotation)
   {
      $this->annotation = $annotation;
   }
   
   public function getAnnotation()
   {
      return ($this->annotation);
   }
   
   public function getAnnotationAt($rowIndex, $colIndex)
   {
      $value = "";
      
      if ($this->annotation && isset($this->annotation[$rowIndex]) && isset($this->annotation[$rowIndex][$colIndex]))
      {
         $value = $this->annotation[$rowIndex][$colIndex];
      }
      
      return ($value);
   }
   
   public static function isEmptyRow($row)
   {
      return (!(is_array($row) && (count($row) > 0) && array_any($row, function (string $value) {return (!empty($value));})));
   }
   
   public function setFormat($separator, $enclosure, $escape, $eol)
   {
      $this->format->separator = $separator;
      $this->format->enclosure = $enclosure;
      $this->format->escape = $escape;
      $this->format->eol = $eol;
   }
   
   // **************************************************************************
   
   private function validCell($row, $col)
   {
      return (($row < $this->numRows()) && ($col < $this->numCols()));
   }
   
   private function validRow($row)
   {
      return ($row < $this->numRows());
   }
   
   // Note: ChatGPT generated file to support fputcsv() with a EOL parameter, not available in prior to PHP 8.
   private static function fputcsv_custom($handle, array $fields, $delimiter = ',', $enclosure = '"', $escape_char = '\\', $eol = "\n")
   {
      $line = '';
    
      $i = 0;
      foreach ($fields as $field)
      {
         // Escape the enclosure character by doubling it (as fputcsv does)
         $escaped = ($enclosure !== null) ? str_replace($enclosure, $enclosure . $enclosure, $field) : $field;
    
         // Enclose the field if it contains delimiter, enclosure, newline, or any whitespace.
         if (($enclosure !== null) &&
             ((strpos($escaped, $delimiter) !== false) ||
              (strpos($escaped, $enclosure) !== false) ||
              (strpos($escaped, "\n") !== false) ||
              (strpos($escaped, "\r") !== false) ||
              preg_match("/\s/", $escaped)))
         {
            $escaped = $enclosure . $escaped . $enclosure;
         }
         
         $line .= $escaped;
         
         if ($i++ < (count($fields) - 1))
         {
            $line .= $delimiter;
         }
      }
    
      // Add custom EOL
      $line = $line . $eol;
    
      return (fwrite($handle, $line));
   }
   
   private const BASE_26 = 26;
   
   private $headings;
   
   private $data;
   
   private $annotation;
   
   private $format;  // {seperator, enclosure, escape, eol}
}

?>