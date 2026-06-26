<?php

namespace App\Helpers;

class NumberHelper
{
    /**
     * Get the ordinal representation of a number (e.g. 1st, 2nd, 3rd).
     *
     * @param int|float $value
     * @return string
     */
    public static function ordinal($value)
    {
        $number = (int) $value;
        if ($number <= 0) {
            return (string) $number;
        }
        
        $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
        
        if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
            return $number . 'th';
        }
        
        return $number . $ends[$number % 10];
    }
}
