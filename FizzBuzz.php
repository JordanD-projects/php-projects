<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class FizzBuzz extends CI_Controller {

    	function index() {

            for($i = 1; $i <= 100; $i++) {

                if ($i % 3 == 0 && $i % 5 == 0) {
                    echo "FizzBuzz";
                    echo "<br>";
                } else if ($i % 3 == 0) {
                    echo "Fizz";
                    echo "<br>";
                } else if ($i % 5 == 0) {
                    echo "Buzz";
                    echo "<br>";
                } else {
                    echo $i;
                    echo "<br>";
                }

            }

        } // end func eggDrop


} // end egg drop class
