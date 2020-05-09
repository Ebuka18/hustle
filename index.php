<?php

// Order Priority
// "availability":true,
// "successRate":"70",
// "rating":"4",
// "avgRespTime":"3",


class Hustle extends Match
{
   function main() {
      // sign app
      echo "------------- Hustle -------------"."<br><br>";

      // declare variables
      $sellers = array(
         $this->readJsonFile("sellerA.json"),
         $this->readJsonFile("sellerB.json"),
         $this->readJsonFile("sellerC.json")
      );

      $buyers = array(
         $this->readJsonFile("buyerA.json"),
         $this->readJsonFile("buyerB.json"),
         $this->readJsonFile("buyerC.json")
      );

      // get preferences
      $buyersPref = $this::getBuyersPreference($sellers, $buyers);

      echo "<br><br> ------------------- Buyers Preference ------------------------- <br><br>";
      foreach ($buyersPref as $key => $value) {
         echo "{$key} = " . $value[0] ." - ". $value[1] ." - ". $value[2];
         echo "<br><br>";
      }

      $sellersPref = $this::getSellersPreference($sellers, $buyers);

      echo "<br><br> ------------------- Sellers Preference ------------------------- <br><br>";
      foreach ($sellersPref as $key => $value) {
         echo "{$key} = " . $value[0] ." - ". $value[1] ." - ". $value[2];
         echo "<br><br>";
      }

      // Assign Buyers to Sellers
      $match = $this::matchSellersToBuyers($buyersPref, $sellersPref);

      var_export($match);


   }

   function readJsonFile($filename) : array {

      // open file
      $reader = fopen($filename,"r",true) or die("Unable to open file");
      
      // get json data
      $data = fgets($reader);

      // close file 
      fclose($reader);

      $respdata = json_decode($data, true);

      return $respdata;
   }
}

class Match
{

   function getSellersPreference($sellers, $buyers){
      $sellersPref = array();

      for ($i=0; $i < count($sellers); $i++) { 
         $pref = array();
         for ($j=0; $j < count($buyers); $j++) { 
            $id = $j + 1;
            $pref["buyer{$id}"] = $this->howMuchDistance($sellers[$i],$buyers[$j]);
         }
         
         $id = $i + 1;
         $sellersPref["seller{$id}"] = $this->bSort($pref,"buyer");
      }

      return $sellersPref;

   }

   function getBuyersPreference($sellers, $buyers){
      $buyersPref = array();

      for ($i=0; $i < count($buyers); $i++) { 
         $pref = array();
         for ($j=0; $j < count($sellers); $j++) { 
            $id = $j + 1;
            $pref["seller{$id}"] = $this->howMuchDistance($sellers[$j],$buyers[$i]);
         }

         $id = $i + 1;
         $buyersPref["buyer{$id}"] = $this->bSort($pref, "seller");
      }

      return $buyersPref;
   }

   function howMuchDistance($seller,$buyer){
      $distance = 0;
      
      $distance += ($seller["region"] == $buyer["preferenceOrder"]["region"]) ? 0 : 1 ;
      $distance += ($seller["avgImplentationTime"] == $buyer["preferenceOrder"]["avgImplentationTime"]) ? 0 : 1 ;
      $distance += ($seller["experience"] == $buyer["preferenceOrder"]["experience"]) ? 0 : 1 ;
      $distance += ($seller["price"] == $buyer["preferenceOrder"]["price"]) ? 0 : 1 ;

      return $distance;
   }

   function bSort($list, $name){
      $isSorted = false;
      $lastUnsorted = count($list) - 1;

      // create an array of the keys
      $prefs = array();
      foreach ($list as $key => $value) {
         $prefs[] = $key;
      }

      // keep checking if the list is not sorted
      while (!$isSorted) {
         $isSorted = true;
         for ($i = 0; $i < $lastUnsorted; $i++) { $id = $i+1;$idn = $i+2;
            if ($list["$name{$id}"] > $list["$name{$idn}"]) {
               // the list is not sorted
               $isSorted = false;
               // swap elements in the list
               $temp = $list["$name{$id}"];
               $list["$name{$id}"] = $list["$name{$idn}"];
               $list["$name{$idn}"] = $temp;

               // for the names
               $temp = $prefs[$i];
               $prefs[$i] = $prefs[$i+1];
               $prefs[$i+1] = $temp;
            }
         }
         // shrink the length since the last element would always be in place
         $lastUnsorted--;
      }

      return $prefs;
   }
   
   // Assign Buyers to Sellers
   function matchSellersToBuyers($buyersPref, $sellersPref) {
      $buyersMatched = array();
      $sellersMatched = array();

      // initiate them to free
      for ($i=1; $i < count($buyersPref)+1; $i++) { 
         $buyersMatched["buyer{$i}"] = false;
      }

      for ($i=1; $i < count($sellersPref)+1; $i++) { 
         $sellersMatched["seller{$i}"] = false;
      }
      
      // while seller is not matched yet
      while ($this->isAnyFree($sellersMatched, "seller") == true && $this->isAnyFree($buyersMatched, "buyer") == true) {

         // if only one remain on both side,
         if($this->countFree($buyersMatched,"buyer") == 1 && $this->countFree($sellersMatched,"seller") == 1){
            // get their IDs
            $b = $this->isFreeId($buyersMatched,"buyer");
            $s = $this->isFreeId($sellersMatched,"seller");

            echo "<br>$s and $b are the only ones availble<br>";

            // match both of them
            $buyersMatched[$b] = $s;
            $sellersMatched[$s] = $b;

            echo "<br>matching of $s and $b should be all for now<br>";
         } else {
               
            // get the un-assigned seller
            $s = $this->isFreeId($sellersMatched,"seller");
            echo "<br>$s has not been matched<br>";

            // get the seller's most preferred buyer
            $b = $sellersPref[$s][0];
            echo "<br>$s prefers $b most<br>";

            // if the buyer has not been matched
            if($buyersMatched[$b] == false){
               echo "<br>$b has not been matched<br>";

               // match the buyer to seller
               $buyersMatched[$b] = $s;
               $sellersMatched[$s] = $b;

               echo "<br>$s is matched with $b<br>";
            }

            // else if buyer has been matched
            else {
               // get the buyers engaged seller
               $ss = $buyersMatched[$b];

               echo "<br>$b has been matched to $ss<br>";

               // who do the buyer prefer more
               // if buyer prefers seller $s to seller $ss
               if( $this->getIndexInPreference($buyersPref[$b],$s) < $this->getIndexInPreference($buyersPref[$b],$ss) ){
                  echo "<br>$b prefers $s<br>";

                  // free $ss
                  $sellersMatched[$ss] = false;
                  echo "<br>$ss is un-matched with $b<br>";

                  // match the buyer to seller
                  $buyersMatched[$b] = $s;
                  $sellersMatched[$s] = $b;

                  echo "<br>$s is matched with $b<br>";

               }

               else {

                  echo "<br>$b prefers $ss<br>";
                  echo "<br>therefore $ss remains matched with $b<br>";
                  // both remain matched
                  // $buyersMatched[$b] = $ss;
                  // $sellersMatched[$ss] = $b;
               }

            }

         }

         echo "<br>------------------------------------------------------------------<br>";

      }

      return array_merge($sellersMatched);

   }

   function countFree($list,$name){
      $count = 0;
      for ($i=1; $i < count($list)+1; $i++) {
         if ($list["$name{$i}"] == false){
            $count++;
         }
      }

      return $count;
   }

   function getIndexInPreference($list,$value){
      // var_export($list);
      for ($i=0; $i < count($list); $i++) { 
         if ($list[$i] == $value) {
            return $i;
         }
      }
      return -1;
   }

   function isAnyFree($value,$name){

      for ($i=1; $i < count($value)+1; $i++) {
         if ($value["$name{$i}"] == false){
            // echo "false";
            return true;
         } else { 
            // echo "true";
            continue;
         }
      }

      return false;
   }

   function isFreeId($value,$name){
      for ($i=1; $i < count($value)+1; $i++) { 
         if ($value["$name{$i}"] == false){
            return "$name{$i}";
         } else {
            continue;
         }
      }

      return -1;
   }

}


$hs = (new Hustle())->main();
// Done !!!, Kiss my Piss !!!

?>