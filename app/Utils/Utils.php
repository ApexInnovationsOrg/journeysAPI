<?php namespace app\Utils;

class Utils
{

    // Class-specific methods may be added below
    static public function romanNumeral($level) {
        
        $table = array('M'=>1000, 'CM'=>900, 'D'=>500, 'CD'=>400, 'C'=>100, 'XC'=>90, 'L'=>50, 'XL'=>40, 'X'=>10, 'IX'=>9, 'V'=>5, 'IV'=>4, 'I'=>1); 
            $numeral = ''; 
            while($level > 0) 
            { 
                foreach($table as $rom=>$arb) 
                { 
                    if($level >= $arb) 
                    { 
                        $level -= $arb; 
                        $numeral .= $rom; 
                        break; 
                    } 
                } 
            } 
        

        return $numeral;
    }

}