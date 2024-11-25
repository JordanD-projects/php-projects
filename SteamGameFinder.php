<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class SteamGameFinder extends CI_Controller {

	public $steamAPIKey = 'api_key_here';
	public $thirdParty_steamAPIKey = 'api_steam_key_here';
	
	// TODO: Remove TODOS, add genres, add select all friends, categories, stop ajax request if another one starts, fix unclickable category text, fix categories not being filtered due to rate limiting, if remote play together, only one user needs it.....
    
	// TODO: Fix this: http://dev.jdusick.com/index.php/SteamGameFinder

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */
	public function index() {

		$fakeFormInput = array('username_here');
		$fakeFriends = 'steam_friend_id_here';
		$fakeCategories = array(
			'3', // Online co-op
		);

		$junkData = ''; //$this->getSteamGames($fakeFriends, $fakeCategories);
		
		$data = array(
			// 'navHTML'=>$navHTML,
			// 'steamGames'=>$steamGames,
			// 'steamFriends'=>$steamFriends,
			'junkData'=>$junkData, // Show this if the user infor that was entered couldn't be found.
		);

		// $this->load->view('header', $data);
		$this->load->view('steamgamefinder', $data);

	}



	/**
	 * This function will search for a user, first based off of a user's vanity URL, then based on Steam ID. Once it verifies the Steam ID it will return the basic information of the Steam user.
	 * This function only returns valid steam users and their info and should strip out any grabagge the end user may have put in. 
	 * 
	 * Returns an Obj
	 */
	private function getSteamUserInfo($steamIDs=array()) {

		$returnObjs = array(
			'error' => '',
			'data' => array()
		);
		$vanityURLInfo = array();

		// See if we can get the SteamInfo from the VanityURL first. 
		if (!empty($steamIDs) && isset($steamIDs)) {
			foreach ($steamIDs as $i=>$steamID) {

				// Try to get the user's steamID using a vanity URL
				$vanityURLData = $this->getSteamIDFromVanityURL($steamID);

				// If we got a valid response and not an error, add the steam ID to the array. Also unset the entry in the orginal SteamIDs array since we have an actual steam ID now. 
				if (!empty($vanityURLData['steamID'])) {

					$steamIDs[] = $vanityURLData['steamID'];
					unset($steamIDs[$i]); // Since it was a vanity URL, unset it. 

				} else {
					$returnObjs['error'] = $vanityURLData['errorMsg'];
				}

			} // end for steamIDs
			


			// Loop through each steamID (there will be no more vanityURLs are we converted them all to Steam IDs) and get their user info
			foreach ($steamIDs as $steamID) {

				$userSteamInfo = $this->getSteamInforFromSteamID($steamID);

				if (!empty($userSteamInfo->response->players[0]->steamid)) {

					$returnObjs['data'][] = $userSteamInfo;
					
				}

			} // end for steamID info

		}

		return $returnObjs;

	}


	/**
	 * If we get a Steam ID or if we don't have a Steam ID we can use this function to return data on the steam user.
	 * 
	 * return: basic steam info for a user. 
	 */
	public function getSteamInforFromSteamID($steamID='') {

		// API call we will run.
		$apifr = "http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=".$this->steamAPIKey."&steamids=".$steamID."&format=json";

		$returnAry = array(
			'errorCode' => 0,
			'errorMsg' => '',
			'steamID' => 0,
		);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		curl_setopt($ch, CURLOPT_URL,$apifr);

		$result = curl_exec($ch);

		$result = json_decode($result);

		return $result;

		curl_close($ch);

	}


	/**
	 * We will need to user this function to get the Steam ID we need to API calls and game searching 
	 * 
	 * return: steamID of user
	 */
	public function getSteamIDFromVanityURL($vanityName='') {

		// API call we will run.
		$apifr = "http://api.steampowered.com/ISteamUser/ResolveVanityURL/v0001/?key=".$this->steamAPIKey."&vanityurl=".$vanityName."&format=json";

		$returnAry = array(
			'errorCode' => 0,
			'errorMsg' => '',
			'steamID' => 0,
		);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		curl_setopt($ch, CURLOPT_URL,$apifr);

		$result = curl_exec($ch);

		$result = json_decode($result);

		// If the response is empty throw generic erorr
		if (empty($result->response)) {
			$returnAry['errorMsg'] = 'Something went wrong. Verify that your profile on Steam is listed as public and try again.';

		} elseif ($result->response->success == 42) { // No user was found with that Vanity URL.

			$returnAry['errorMsg'] = 'No users could be found using that Vanity URL. Verify that your profile on Steam is listed as public.';
			$returnAry['errorCode'] = $result->response->success;

		} elseif ($result->response->success == 1) { // Else we found the Steam ID for the user. 
			$returnAry['errorMsg'] = '';
			$returnAry['errorCode'] = $result->response->success;
			$returnAry['steamID'] = $result->response->steamid;
		}

		return $returnAry;

		curl_close($ch);

	}


	/**
	 * Get a list of Steam friends a user has. 
	 * Returns an array of steamids of your friends.
	 * 
	 * 
	 */
	public function getSteamFriends($steamID) {

		// API call we will run.
		$apifr = "http://api.steampowered.com/ISteamUser/GetFriendList/v0001/?key=".$this->steamAPIKey."&steamid=".$steamID."&relationship=friend&format=json";

		$returnAry = array();
		
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		curl_setopt($ch, CURLOPT_URL,$apifr);

		$result = curl_exec($ch);

		$result = json_decode($result);

		// echo '<pre>';
		// print_r($result->friendslist->friends);
		// echo '</pre>';
		
		// add each friend's steam ID to 
		if (!empty($result->friendslist->friends)) {

			// Loop through every game
			foreach($result->friendslist->friends as $r){

				$returnAry[] = $r->steamid;

			}
		}

		return $returnAry;

		curl_close($ch);
	}

	/**
	 * Get the steam games of a user by passing their Steam User ID as a parameter.
	 * The function will return the name and image for each game the user owns. 
	 * 
	 * Filters allows you to pass an array of tags from the form to narrow down the results send back based on things like "coop", "local multiplayer", "etc."
	 * 
	 */
	public function getSteamGames($steamUserID=0, $categories=array()) {

		// API call we will run.
		$apifr = "http://api.steampowered.com/IPlayerService/GetOwnedGames/v0001/?key=".$this->steamAPIKey."&steamid=".$steamUserID."&include_appinfo=true&include_played_free_games=true&gameid&format=json";

		// The formatted and returned array of games.
		$gamesArray = array();

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		curl_setopt($ch, CURLOPT_URL,$apifr);

		$result = curl_exec($ch);

		$result = json_decode($result, true);

		curl_close($ch);

		if (!empty($result['response']['games'])) {

			// Loop through every game and add it to an array we can return
			foreach($result['response']['games'] as $r){

			//	var_dump($r);

				if (!empty($r['appid'])) {

					// Sadly, we need to even return detailed info for games we don't need as that is the only way to get the filters and filter by category. 
					$gameDetails = ''; //$this->getGameDetails($r['appid'], $categories);

					// echo '<pre>';
					// echo $r['appid'];
					// print_r($gameDetails);
					// echo '</pre>';

					// This will be empty if the game did not meet our categories, in which case we can just skip it and not return it to the list. 
					// if (!empty($gameDetails)) {

						$gamesArray[$r['appid']] = array(
							'appid' => $r['appid'],
							'name' => $r['name'],
							'img_icon_url' => $r['img_icon_url'],
							'img_logo_url' => $r['img_logo_url'],
							'gameDetails' => $gameDetails,
						);

		// echo '<pre>';
		// print_r($gameDetails);
		// echo '</pre>';


				//	}
				}
			}
		}

		// echo '<pre>';
		// print_r($gamesArray);
		// echo '</pre>';
		
		return $gamesArray;
	}


	// Get the store details of a particular game.
	public function getGameDetails($appID=0, $categories=array()) {

		// API call we will run.
		$apifr = "http://store.steampowered.com/api/appdetails?appids=".$appID."&categories=3&format=json";
		//$apifr = "http://api.steampowered.com/ISteamUserStats/GetSchemaForGame/v2/?key=".$this->steamAPIKey."&appid=".$appID."&categories=36&format=json";
		//$apifr = "https://api.steamapis.com/market/app/".$appID."?api_key=".$this->thirdParty_steamAPIKey."&format=json";

		// The formatted and returned array of games.
		$gameDetailArray = array();

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		curl_setopt($ch, CURLOPT_URL,$apifr);

		$result = curl_exec($ch);

		$result = json_decode($result, true);

		curl_close($ch);
	
		//print_r($result);

		// if (!empty($result)) {	

		// // 	// Categories are empty so the user selected no filters. 
		// 	if (empty($categories)) {
		// 		$gameDetailArray = $result[$appID]['data'];
		// 	} else {
		// 		// Loop through the selected categories to narrow down the games and remove any games that do not have the category selected. 

		// 		foreach ($categories as $cat) {

		// 			echo $cat;
						
		// 			// echo '<pre>';
		// 			// print_r($result[$appID]['data']['categories']['id']);
		// 			// echo '</pre>';

		// 			// If this value is set, it means it matches one or more of our category filters. 
		// 			if (isset($result[$appID]['data']['categories']['id'][$cat])) {
		// 				$gameDetailArray = $result[$appID]['data'];
		// 			}
		// 		}
		// 	}
		// }

		return $gameDetailArray;

	}

	/**
	 * Take in an array of friend data from Steam API and return an HTML formatted list.
	 *
	 * @param [type] $friendDataArray
	 * @return void
	 */
	public function formatFriendsList($friendDataArray) {

		$HTMLStr = '<ul required>';

		if (!empty($friendDataArray)) {
			foreach ($friendDataArray as $friend) {

				$HTMLStr .= '
					<li>
						<input type="checkbox" id="'.$friend->steamid.'" name="friendsSteamID[]" value="'.$friend->steamid.'" />
						<a href="'.$friend->avatar.'" title="Home"></a>
						<label for="'.$friend->steamid.'">'.$friend->personaname.'</label>
					</li>
					<br>
				';

			}
		}

		$HTMLStr .= '</ul>';

		return $HTMLStr;

	}


	/**
	 * Take the raw data from all friend's steam games, filter by cateegory, and format the list
	 * 
	 * 
	 */
	public function formatGames($friendDataArray) {



	}

	/**
	 * Ajax Functions below this line.
	 */


	 // Step 1 of the user experience is to grab the friends of the user
	 public function getFriendsAndTags() {

		// POST data from the form
		$postData = $this->input->post();
		$friendDataArray = array();
		$returnData = array(
			'verifiedSteamID' => '',
			'userinfo' => '',
			'friends' => array(),
			'friendsHTML' => '',
			'error' => ''
		);

		// We have a value
		if (!empty($postData['steamID'])) {

			// First, we need to verify that the username entered was valid. 
			$steamIDUserInfo = $this->getSteamUserInfo(array($postData['steamID']));

		//	print_r($steamIDUserInfo['data'][0]->response->players[0]);

			// Make sure we got a valid user back and add their info as well as their SteamID by itself for easy reference
			if (!empty(($steamIDUserInfo['data'][0]->response->players[0]))) {
				$returnData['verifiedSteamID'] = $steamIDUserInfo['data'][0]->response->players[0]->steamid;
				$returnData['userinfo'] = $steamIDUserInfo['data'][0]->response->players[0];

				// Since we have a valid Steam ID we can grab this user's friends.
				$steamFriends = $this->getSteamFriends($returnData['verifiedSteamID']);

				if (!empty($steamFriends)) {
					
					// Now grab info for all these users and pass it back to the front end:
					$friendsUserInfo = $this->getSteamUserInfo($steamFriends);
		
					// echo '<pre>';
					// print_r($friendsUserInfo);
					// echo '</pre>';

					// Add each friend to our array of friends so we can grab their data
					if (!empty($friendsUserInfo['data'])) {
						foreach($friendsUserInfo['data'] as $r){
							$returnData['friends'][] = $r->response->players[0];
							$friendDataArray[] = $r->response->players[0];

							// Format the list of friends nicely to send back to the form
							$formattedFriends = $this->formatFriendsList($friendDataArray);

							if (!empty($formattedFriends)) {
								$returnData['friendsHTML'] = $formattedFriends;
							}
						}

					}

				} // end if we have friends
			} // end if we have a valid user

			if (!empty($steamIDUserInfo['error'])) {
				$returnData['error'] = $steamIDUserInfo['error'];
			}

		}
	
		echo json_encode($returnData);
		
	 }

	 // Step 2 Now that the user has selected the friends they want to compare games with, show them filtering options (multiplayer, local, remote play, etc.)
	 public function getGamesInCommon() {


		// POST data from the form
		$postData = $this->input->post();
		$returnData = array(
			'error',
			'games' => array(),
		); // Data we are returning to the form to display as results. 
		$categories = array(); // Categories we want to filter by.
		$friends = array(); // Friends we want to get games in common for. 
		$allGamesArray = array(); // Includes a list of every friend's games until we can remove ones not everyone has.
		$verifiedUserSteamID = 0;
		$verifiedUserGames = array();
		$errorMsg = '';
		$finalGamesListAry = array();

		if (!empty($postData['categories'])) {
			$categories = $postData['categories'];
		}

		if (!empty($postData['friendsSteamID'])) {
			$friends = $postData['friendsSteamID'];
		}

		if (!empty($postData['verifiedUserSteamID'])) {
			$verifiedUserSteamID = $postData['verifiedUserSteamID'];
		}

		// get the verified user's games too or we won't have anything to compare against. 
		if (!empty($verifiedUserSteamID)) {
			array_push($friends, $verifiedUserSteamID);
		} else {
			$errorMsg = 'No games were found for your user. Your profile may be private.';
		}

		// TODO: Include the person creating the list....
		// Loop through the friends we selected and compile our list while applying the categories
		foreach ($friends as $friend) {
			array_push($allGamesArray, $this->getSteamGames($friend, $categories));
		}

		// var_dump($allGamesArray);
		// count($allGamesArray);

		// Now loop through each array of games that was returned and return only games each 
		if (!empty($allGamesArray)) {
			//$finalGamesListAry = call_user_func_array('array_intersect', $allGamesArray);

			$length = sizeof($allGamesArray);
			for ($i = 0; $i < $length; $i++){
				${"listOfGames".($i+1)} = $allGamesArray[$i];
			}
			
			//var_dump($listOfGames1, $test);


		} else {
			$errorMsg = 'No games were found in common between your friends, please try again with different filters.';
		}

		if (!empty($errorMsg)) {
			$returnData['error'] = $errorMsg;
		}

		if (!empty($finalGamesListAry)) {
			$returnData['games'] = $finalGamesListAry;
		}

		echo json_encode($returnData);

	 }
	
}
