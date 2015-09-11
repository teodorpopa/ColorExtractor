<?php

namespace TeodorPopa\ColorExtractor\Algorithms;

/**
 * Color Distance CIEDE2000
 *
 * @link http://www.ece.rochester.edu/~gsharma/ciede2000/ciede2000noteCRNA.pdf
 */
class CIEDE2000
{

    /**
     * @param $color1
     * @param $color2
     *
     * @return float
     */
    public static function diff($color1, $color2)
    {
        list($L1, $a1, $b1) = $color1;
        list($L2, $a2, $b2) = $color2;

        $C1 = sqrt(pow($a1, 2) + pow($b1, 2));
        $C2 = sqrt(pow($a2, 2) + pow($b2, 2));
        $Cb = ($C1 + $C2) / 2;

        $G = .5 * (1 - sqrt(pow($Cb, 7) / (pow($Cb, 7) + pow(25, 7))));

        $a1p = (1 + $G) * $a1;
        $a2p = (1 + $G) * $a2;

        $C1p = sqrt(pow($a1p, 2) + pow($b1, 2));
        $C2p = sqrt(pow($a2p, 2) + pow($b2, 2));

        $h1p = $a1p == 0 && $b1 == 0 ? 0 : atan2($b1, $a1p);
        $h2p = $a2p == 0 && $b2 == 0 ? 0 : atan2($b2, $a2p);

        $LpDelta = $L2 - $L1;
        $CpDelta = $C2p - $C1p;

        if ($C1p * $C2p == 0) {
            $hpDelta = 0;
        } elseif (abs($h2p - $h1p) <= 180) {
            $hpDelta = $h2p - $h1p;
        } elseif ($h2p - $h1p > 180) {
            $hpDelta = $h2p - $h1p - 360;
        } else {
            $hpDelta = $h2p - $h1p + 360;
        }

        $HpDelta = 2 * sqrt($C1p * $C2p) * sin($hpDelta / 2);

        $Lbp = ($L1 + $L2) / 2;
        $Cbp = ($C1p + $C2p) / 2;

        if ($C1p * $C2p == 0) {
            $hbp = $h1p + $h2p;
        } elseif (abs($h1p - $h2p) <= 180) {
            $hbp = ($h1p + $h2p) / 2;
        } elseif ($h1p + $h2p < 360) {
            $hbp = ($h1p + $h2p + 360) / 2;
        } else {
            $hbp = ($h1p + $h2p - 360) / 2;
        }

        $T = 1 - .17 * cos($hbp - 30) + .24 * cos(2 * $hbp) + .32 * cos(3 * $hbp + 6) - .2 * cos(4 * $hbp - 63);

        $sigmaDelta = 30 * exp(-pow(($hbp - 275) / 25, 2));

        $Rc = 2 * sqrt(pow($Cbp, 7) / (pow($Cbp, 7) + pow(25, 7)));

        $Sl = 1 + ((.015 * pow($Lbp - 50, 2)) / sqrt(20 + pow($Lbp - 50, 2)));
        $Sc = 1 + .045 * $Cbp;
        $Sh = 1 + .015 * $Cbp * $T;

        $Rt = -sin(2 * $sigmaDelta) * $Rc;

        return sqrt(
            pow($LpDelta / $Sl, 2) +
            pow($CpDelta / $Sc, 2) +
            pow($HpDelta / $Sh, 2) +
            $Rt * ($CpDelta / $Sc) * ($HpDelta / $Sh)
        );
    }

}