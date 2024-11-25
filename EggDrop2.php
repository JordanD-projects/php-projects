<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class EggDrop2 extends CI_Controller {

        // Index function to actually run the test. 
        function index () {

            // Run the code
            $n = 3;
            $k = 5;
            echo "The minimum number of trials with " .$n. " eggs and " .$k. " floors is " .$this->eggDropTest($n, $k);
                                            
        }

        // $n = number of eggs
        // $k = number of floors
    	function eggDropTest($n, $k) {

            $results = array(array());

            for ($i = 1; $i <= $n; $i++) {
                $results[$i][1] = 1;
                $results[$i][0] = 0;
            }

            // Hard code for one egg as with one egg the number of floors are always going to be the same.
            for ($i = 1; $i <= $k; $i++) {
                $results[1][$i] = $i;
            }

            for ($in = 2; $in <= $n; $in++) {

                for ($ik = 2; $ik <= $k; $ik++) {

                    $results[$in][$ik] = 9999999;
                
                    for ($ik2 = 2; $ik2 <= $ik; $ik2++) {

                        $res = 1 + max($results[$in - 1][$ik2 - 1], $results[$in][$ik - $ik2]);

                        if ($res < $results[$in][$ik]) {
                            $results[$in][$ik] = $res;
                        }
                    }

                }

            }

            // Output the results
            /* echo '<pre>';
            var_dump($results);
            echo '</pre>'; */
            //var_dump($results[$n][$k]);

            // return the results
            return $results[$n][$k];

        } // end func eggDrop


} // end egg drop class
