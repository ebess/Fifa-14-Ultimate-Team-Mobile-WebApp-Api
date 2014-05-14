<?php

/**
 * Class EAHashor
 */
class EAHashor
{
    public static $R1Shifts = array( 7, 12, 17, 22, 7, 12, 17, 22, 7, 12, 17, 22, 7, 12, 17, 22 );
    public static $R2Shifts = array( 5, 9, 14, 20, 5, 9, 14, 20, 5, 9, 14, 20, 5, 9, 14, 20 );
    public static $R3Shifts = array( 4, 11, 16, 23, 4, 11, 16, 23, 4, 11, 16, 23, 4, 11, 16, 23 );
    public static $R4Shifts = array( 6, 10, 15, 21, 6, 10, 15, 21, 6, 10, 15, 21, 6, 10, 15, 21 );
	static public $HexCharacters = "0123456789abcdef";

	static public function int32($val)
	{
		return $val & 0xFFFFFFFF;
	}

	static public function ff($a, $b, $c, $d, $x, $s, $t)
    {
    	$a = static::int32($a);
    	$b = static::int32($b);
    	$c = static::int32($c);
    	$d = static::int32($d);
    	$x = static::int32($x);
    	$s = static::int32($s);
    	$t = static::int32($t);

        return static::cmn(($b & $c) | ((~$b) & $d), $a, $b, $x, $s, $t);
    }

    static public function gg($a, $b, $c, $d, $x, $s, $t)
    {
    	$a = static::int32($a);
    	$b = static::int32($b);
    	$c = static::int32($c);
    	$d = static::int32($d);
    	$x = static::int32($x);
    	$s = static::int32($s);
    	$t = static::int32($t);

        return static::cmn(($b & $d) | ($c & (~$d)), $a, $b, $x, $s, $t);
    }
    static public function hh($a, $b, $c, $d, $x, $s, $t)
    {
    	$a = static::int32($a);
    	$b = static::int32($b);
    	$c = static::int32($c);
    	$d = static::int32($d);
    	$x = static::int32($x);
    	$s = static::int32($s);
    	$t = static::int32($t);

        return static::cmn($b ^ $c ^ $d, $a, $b, $x, $s, $t);
    }

    static public function ii($a, $b, $c, $d, $x, $s, $t)
    {
    	$a = static::int32($a);
    	$b = static::int32($b);
    	$c = static::int32($c);
    	$d = static::int32($d);
    	$x = static::int32($x);
    	$s = static::int32($s);
    	$t = static::int32($t);

        return static::cmn($c ^ ($b | (~$d)), $a, $b, $x, $s, $t);
    }

    static public function cmn($q, $a, $b, $x, $s, $t)
    {
    	$q = static::int32($q);
    	$b = static::int32($b);
    	$x = static::int32($x);
    	$s = static::int32($s);
    	$t = static::int32($t);

        return static::add(static::bitwiseRotate(static::add(static::add($a, $q), static::add($x, $t)), $s), $b);
    }

    static public function add($x, $y)
    {
    	$x = static::int32($x);
    	$y = static::int32($y);

        $lsw = ($x & 0xFFFF) + ($y & 0xFFFF);
        $msw = ($x >> 16) + ($y >> 16) + ($lsw >> 16);
        return ($msw << 16) | ($lsw & 0xFFFF);
    }

    static public function bitwiseRotate($x, $c)
    {
    	$x = static::int32($x);
        return (int)(($x << $c) | (static::uRShift($x,32-$c)));
    }

    static public function uRShift($number, $shiftBits) 
    { 
    	$number = static::int32($number);

        $z = hexdec(80000000); 
        if ($z & $number) 
        { 
            $number = ($number >> 1); 
            $number &= (~$z); 
            $number |= 0x40000000; 
            $number = ($number >> ($shiftBits - 1)); 
        } else { 
            $number = ($number >> $shiftBits); 
        } 
        return $number; 
    } 

    static public function numberToHex($number)
    {
        $result = "";
        for ($j = 0; $j <= 3; $j++)
        {
            $result .= static::$HexCharacters[($number >> ($j * 8 + 4)) & 0x0F]
                    . (string) static::$HexCharacters[($number >> ($j * 8)) & 0x0F];
        }

        return $result;
    }

	static public function chunkInput($input)
    {
    	$inputLength = strlen($input);
        $numberOfBlocks = (($inputLength + 8) >> 6) + 1;
        $blocks = array();
        for ($i = 0; $i < $numberOfBlocks * 16; $i++)
       	{
       		$blocks[$i] = 0;
       	}

        for ($i = 0; $i < $inputLength; $i++)
        {
            $blocks[$i >> 2] =  $blocks[$i >> 2] | ord($input[$i]) << (($i % 4) * 8);
        }


        $blocks[$inputLength >> 2] = $blocks[$inputLength >> 2] | 0x80 << (($inputLength % 4) * 8);
        $blocks[$numberOfBlocks * 16 - 2] = $inputLength * 8;

        return $blocks;
    }

    static public function hash($input)
    {

		$chunks = static::chunkInput($input);

        $a = 1732584193;
        $b = -271733879;
        $c = -1732584194;
        $d = 271733878;

        for ($i = 0; $i < count($chunks); $i += 16)
        {
            $tempA = $a;
            $tempB = $b;
            $tempC = $c;
            $tempD = $d;

            $a = static::ff($a, $b, $c, $d, $chunks[$i + 0], static::$R1Shifts[0], -680876936);
            $d = static::ff($d, $a, $b, $c, $chunks[$i + 1], static::$R1Shifts[1], -389564586);
            $c = static::ff($c, $d, $a, $b, $chunks[$i + 2], static::$R1Shifts[2], 606105819);
            $b = static::ff($b, $c, $d, $a, $chunks[$i + 3], static::$R1Shifts[3], -1044525330);
            $a = static::ff($a, $b, $c, $d, $chunks[$i + 4], static::$R1Shifts[4], -176418897);
            $d = static::ff($d, $a, $b, $c, $chunks[$i + 5], static::$R1Shifts[5], 1200080426);
            $c = static::ff($c, $d, $a, $b, $chunks[$i + 6], static::$R1Shifts[6], -1473231341);
            $b = static::ff($b, $c, $d, $a, $chunks[$i + 7], static::$R1Shifts[7], -45705983);
            $a = static::ff($a, $b, $c, $d, $chunks[$i + 8], static::$R1Shifts[8], 1770035416);
            $d = static::ff($d, $a, $b, $c, $chunks[$i + 9], static::$R1Shifts[9], -1958414417);
            $c = static::ff($c, $d, $a, $b, $chunks[$i + 10], static::$R1Shifts[10], -42063);
            $b = static::ff($b, $c, $d, $a, $chunks[$i + 11], static::$R1Shifts[11], -1990404162);
            $a = static::ff($a, $b, $c, $d, $chunks[$i + 12], static::$R1Shifts[12], 1804603682);
            $d = static::ff($d, $a, $b, $c, $chunks[$i + 13], static::$R1Shifts[13], -40341101);
            $c = static::ff($c, $d, $a, $b, $chunks[$i + 14], static::$R1Shifts[14], -1502002290);
            $b = static::ff($b, $c, $d, $a, $chunks[$i + 15], static::$R1Shifts[15], 1236535329);
            $a = static::gg($a, $b, $c, $d, $chunks[$i + 1], static::$R2Shifts[0], -165796510);
            $d = static::gg($d, $a, $b, $c, $chunks[$i + 6], static::$R2Shifts[1], -1069501632);
            $c = static::gg($c, $d, $a, $b, $chunks[$i + 11], static::$R2Shifts[2], 643717713);
            $b = static::gg($b, $c, $d, $a, $chunks[$i + 0], static::$R2Shifts[3], -373897302);
            $a = static::gg($a, $b, $c, $d, $chunks[$i + 5], static::$R2Shifts[4], -701558691);
            $d = static::gg($d, $a, $b, $c, $chunks[$i + 10], static::$R2Shifts[5], 38016083);
            $c = static::gg($c, $d, $a, $b, $chunks[$i + 15], static::$R2Shifts[6], -660478335);
            $b = static::gg($b, $c, $d, $a, $chunks[$i + 4], static::$R2Shifts[7], -405537848);
            $a = static::gg($a, $b, $c, $d, $chunks[$i + 9], static::$R2Shifts[8], 568446438);
            $d = static::gg($d, $a, $b, $c, $chunks[$i + 14], static::$R2Shifts[9], -1019803690);
            $c = static::gg($c, $d, $a, $b, $chunks[$i + 3], static::$R2Shifts[10], -187363961);
            $b = static::gg($b, $c, $d, $a, $chunks[$i + 8], static::$R2Shifts[11], 1163531501);
            $a = static::gg($a, $b, $c, $d, $chunks[$i + 13], static::$R2Shifts[12], -1444681467);
            $d = static::gg($d, $a, $b, $c, $chunks[$i + 2], static::$R2Shifts[13], -51403784);
            $c = static::gg($c, $d, $a, $b, $chunks[$i + 7], static::$R2Shifts[14], 1735328473);
            $b = static::gg($b, $c, $d, $a, $chunks[$i + 12], static::$R2Shifts[15], -1926607734);
            $a = static::hh($a, $b, $c, $d, $chunks[$i + 5], static::$R3Shifts[0], -378558);
            $d = static::hh($d, $a, $b, $c, $chunks[$i + 8], static::$R3Shifts[1], -2022574463);
            //line below uses _r2Shifts[2] where as MD5 would use _r3Shifts[2] 
            $c = static::hh($c, $d, $a, $b, $chunks[$i + 11], static::$R2Shifts[2], 1839030562);
            $b = static::hh($b, $c, $d, $a, $chunks[$i + 14], static::$R3Shifts[3], -35309556);
            $a = static::hh($a, $b, $c, $d, $chunks[$i + 1], static::$R3Shifts[4], -1530992060);
            $d = static::hh($d, $a, $b, $c, $chunks[$i + 4], static::$R3Shifts[5], 1272893353);
            $c = static::hh($c, $d, $a, $b, $chunks[$i + 7], static::$R3Shifts[6], -155497632);
            $b = static::hh($b, $c, $d, $a, $chunks[$i + 10], static::$R3Shifts[7], -1094730640);
            $a = static::hh($a, $b, $c, $d, $chunks[$i + 13], static::$R3Shifts[8], 681279174);
            $d = static::hh($d, $a, $b, $c, $chunks[$i + 0], static::$R3Shifts[9], -358537222);
            $c = static::hh($c, $d, $a, $b, $chunks[$i + 3], static::$R3Shifts[10], -722521979);
            $b = static::hh($b, $c, $d, $a, $chunks[$i + 6], static::$R3Shifts[11], 76029189);
            $a = static::hh($a, $b, $c, $d, $chunks[$i + 9], static::$R3Shifts[12], -640364487);
            $d = static::hh($d, $a, $b, $c, $chunks[$i + 12], static::$R3Shifts[13], -421815835);
            $c = static::hh($c, $d, $a, $b, $chunks[$i + 15], static::$R3Shifts[14], 530742520);
            $b = static::hh($b, $c, $d, $a, $chunks[$i + 2], static::$R3Shifts[15], -995338651);
            $a = static::ii($a, $b, $c, $d, $chunks[$i + 0], static::$R4Shifts[0], -198630844);
            $d = static::ii($d, $a, $b, $c, $chunks[$i + 7], static::$R4Shifts[1], 1126891415);
            $c = static::ii($c, $d, $a, $b, $chunks[$i + 14], static::$R4Shifts[2], -1416354905);
            $b = static::ii($b, $c, $d, $a, $chunks[$i + 5], static::$R4Shifts[3], -57434055);
            $a = static::ii($a, $b, $c, $d, $chunks[$i + 12], static::$R4Shifts[4], 1700485571);
            $d = static::ii($d, $a, $b, $c, $chunks[$i + 3], static::$R4Shifts[5], -1894986606);
            $c = static::ii($c, $d, $a, $b, $chunks[$i + 10], static::$R4Shifts[6], -1051523);
            $b = static::ii($b, $c, $d, $a, $chunks[$i + 1], static::$R4Shifts[7], -2054922799);
            $a = static::ii($a, $b, $c, $d, $chunks[$i + 8], static::$R4Shifts[8], 1873313359);
            $d = static::ii($d, $a, $b, $c, $chunks[$i + 15], static::$R4Shifts[9], -30611744);
            $c = static::ii($c, $d, $a, $b, $chunks[$i + 6], static::$R4Shifts[10], -1560198380);
            $b = static::ii($b, $c, $d, $a, $chunks[$i + 13], static::$R4Shifts[11], 1309151649);
            $a = static::ii($a, $b, $c, $d, $chunks[$i + 4], static::$R4Shifts[12], -145523070);
            $d = static::ii($d, $a, $b, $c, $chunks[$i + 11], static::$R4Shifts[13], -1120210379);
            $c = static::ii($c, $d, $a, $b, $chunks[$i + 2], static::$R4Shifts[14], 718787259);
            $b = static::ii($b, $c, $d, $a, $chunks[$i + 9], static::$R4Shifts[15], -343485551);
            //This line is doubled for some reason, line below is not in the MD5 version
            $b = static::ii($b, $c, $d, $a, $chunks[$i + 9], static::$R4Shifts[15], -343485551);
            $a = static::add($a, $tempA);
            $b = static::add($b, $tempB);
            $c = static::add($c, $tempC);
            $d = static::add($d, $tempD);

        }

        return static::numberToHex($a) . static::numberToHex($b) . static::numberToHex($c) . static::numberToHex($d);
    }


}