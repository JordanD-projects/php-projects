<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class EggDrop extends CI_Controller {

        // Index function to actually run the test. 
        function index () {

            // Run the code
            $n = 3;
            $k = 5;
            echo "The fewest possible trials using " .$n. " eggs and " .$k. " floors is " .$this->eggDropTest($n, $k);
                                            
        }

        // func eggDropTest: Our main function for determing the fewest tests needed for x eggs and x floors. 
        // $n = number of eggs
        // $k = number of floors
    	function eggDropTest($n, $k) {

            // Create a 2D array of floors to hold our results.
            // This array is going to store our results and we can access those results by inputting $n and $k like so: $resutls[$n][$k]
            $results = array(array());

            // Set our numbers for zero eggs
            // If we have one floor, we need 1 trial. 
            // If we have zero floors, we need 0 trials. 
            for ($i = 1; $i <= $n; $i++) {
                $results[$i][1] = 1;
                $results[$i][0] = 0;
            }

            // If we have one egg, we need a number of trials equal to the egg 
            // This part is easy since trials will always be equal to the number of floors. 
            for ($i = 1; $i <= $k; $i++) {
                $results[1][$i] = $i;
            }

            // Loop through each egg
            // We start at two as we have already covered one. 
            // Completely skip 1 as we have already covered it.
            for ($in = 2; $in <= $n; $in++) {

                // Loop through each floor
                for ($ik = 2; $ik <= $k; $ik++) {

                    // set a max number of trials to compare against. 
                    $results[$in][$ik] = 999999;
                    // 2 (eggs), 2 (trials) = 999999

                    // Loop through each iteration of floors
                    // ik2 = $ik (floors) -1 so we can get an offset.
                    for ($ik2 = 1; $ik2 <= $ik; $ik2++) {

                        // To get our result we use the PHP max function and the other variables we have used througout. 
                        $res = 1 + max($results[$in - 1][$ik2 - 1], $results[$in][$ik - $ik2]);

                        // Make sure our result Is less than 999999
                        if ($res < $results[$in][$ik]) {

                            // Assign our result to our array. 
                            $results[$in][$ik] = $res;
                        }

                    }

                }
                
            }

            // Output the results
            // echo '<pre>';
            // var_dump($results);
            // echo '</pre>';
            //var_dump($results[$n][$k]);


            // return the results
            return $results[$n][$k];

        } // end func eggDrop


} // end egg drop class
