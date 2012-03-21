<?php

/*
 * 
 * *************   Before you start with OAuth_Twitter.php   ***************
 * 
 * This library acts as a helperClass for Twitter APIs which requires Zend_Oauth_Consumer
 * 
 * I suppose that your code contains Zend/Oauth Library.
 * This library includes Consumer.php
 * 
 * All the functions in this library uses an object of type Consumer
 * 
 * This Library is developed and tested in a Zend Project
 * 
 */

/*
 * *******************   Change Log  **************
 * 
 * Modified on: 24 Jan 2010 21:50    GMT+05:30 Version 0.2Alpha
 *    
 *	1.	0.1Alpha published on 23 Jan 2010 12:50    GMT+05:30
 *
 * 	2.	0.2Alpha published on 24 Jan 2010 21:50    GMT+05:30	
 *		Changes:
 *			added function  1. getPublicUpdatesByUserid($user_id)
 *							2. getPublicUpdatesByHandle($screen_name)
 *
 * 	3.	0.3 Alpha published on 21 Feb 2010 21:50    GMT+05:30 	
 *		Changes:
 			1. Added function to access list updates, followers and members.
 			2. Added basic search functionality.
 * 
 * 
 */

	/*
	 * File Name	: oauth_twitter.php
	 * File Size	: 1 KB
	 * Author 		: Basil Brassily Thoppil < basilbthoppil@gmail.com >
	 * Version 		: 0.3 Alpha
	 * Created on   : 17 Jan 2009 00:00 GMT+5:30 
	 * Last modified on: 21 Feb 2010 21:50    GMT+05:30  
	 */
require_once 'Zend/Oauth/Consumer.php';

class OAuth_Twitter {
	
	private $config; //App configuration Array
	private $access_token;
	private $save_token_in_session;
	function __construct($config) {
		$this->config = $config;
		$save_token_in_session = isset($config['save_token_in_session']) ? true : false;
		
		if(isset($config['access_token']))
			$this->access_token = $config['access_token'];
		else 
			$this->access_token = null;
			
		//If access_token is present in config - no other twitter app conf are required
		if( $save_token_in_session && isset($this->access_token) && $this->access_token != '' )
			$_SESSION['TWITTER_ACCESS_TOKEN'] = serialize($token);
		
		else if (!$this->access_token && $save_token_in_session && isset($_SESSION['TWITTER_ACCESS_TOKEN']))
			$this->access_token = unserialize($_SESSION['TWITTER_ACCESS_TOKEN']);
		
		else
			requestAuth();
	}
	
	
	/*
	 * Redirects app to the twitter App authentication page
	 * 
	 * Once authenticated successfully, Redirects back to the callback URL registerd with Twitter
	 * 
	 * If Already authenticated, Returns true to the calling function
	 */
	function requestAuth(){
		$consumer = new Zend_Oauth_Consumer($this->config);
		session_start();
			/*
			 * Check for already authenticated and
			 * app has TWITTER ACCESS TOKEN
			 */
		if (!$_SESSION['TWITTER_ACCESS_TOKEN'] || !isset($this->access_token)) {
			/*
			 * Redirect to twitter API with REQUEST TOKEN
			 */
		    $token = $consumer->getRequestToken();
		    $_SESSION['TWITTER_REQUEST_TOKEN'] = serialize($token);
		    $consumer->redirect();
		}else{
			return true;
		}		
	}
	
	/*
	 * To handle callback from Twitter API
	 * 
	 * @param Array $this->config   --- Contains configuration of Twitter client
	 */
	function handleCallback(){
		$consumer = new Zend_Oauth_Consumer($this->config);
		
		if (!empty($_GET) && isset($_SESSION['TWITTER_REQUEST_TOKEN'])) {	
		    $token = $consumer->getAccessToken($_GET, unserialize($_SESSION['TWITTER_REQUEST_TOKEN']));
		    $_SESSION['TWITTER_ACCESS_TOKEN'] = serialize($token);
		    $this->access_token = $token;
		    unset($_SESSION['TWITTER_REQUEST_TOKEN']);
		    $_SESSION['logged_in'] = 1;
		}
	}
	
	/*
	 * You may extend override this function to save access_token to db : Idea!!  :-)
	 */
	private function saveAccessToken($access_token) {
		if( $save_token_in_session ) 
			$_SESSION['TWITTER_ACCESS_TOKEN'] = serialize($access_token);
	}
	
	public function getAccessToken() {
		return $this->access_token;
	}
	
	/*
	 * Get All followers of a specified user, using twitter user id
	 * 
	 * The function fetches all the details about the followers including their latest update(tweet)
	 * 
	 * @param Array Configuration data of Twitter App
	 * @param string $status  --- Tweet :).
	 * 
	 *  Return Flags
	 *  
	 *  -1  --> Fails   Status string empty
	 *  -2  --> Fails   Status string length greater than 140 characters --> Breaks the twitter phylosophy
	 *  1   --> Success Status updated successfully
	 *  0   --> Fails	Update Error!!
	 */
		
	function updateStatus($status){
		if(!isset($status)){
			return -1;
		}else if(strlen($status) > 140){
			return -2;
		}else{
	    		$response =  makeAPIRequest('http://twitter.com/statuses/update.json' , 
	    				array('status' => $status ) ,
	    				Zend_Http_Client::POST );
			if(isset($response)){
				return 1;
			}else{
				return 0;
			}
		}
	}
	
	
	
	
	/*
	 * Get All followers of a specified user, using twitter user id
	 * 
	 * The function fetches all the details about the followers including their latest update(tweet)
	 * 
	 * @param Array Configuration data of Twitter App
	 * @param int | string $user_id  --- Twitter User Id of the user. 
	 */
	function getFollowersByUserId($user_id){
	    return makeAPIRequest('http://twitter.com/statuses/followers.json' , 
	    				array('user_id' => $user_id));
	}	

	
	/*
	 * Get All followers of a specified user using user's screen_name
	 *
	 * The function fetches all the details about the followers including their latest update(tweet)
	 * @param Array 	$this->config				Configuration data of Twitter App
	 * @param  string 	$screen_name  --- 	Twitter Screen Name of the user. 
	 */	
	function getFollowersByHandle($screen_name){
	    return makeAPIRequest('http://twitter.com/statuses/followers.json' , 
	    				array('screen_name' => $screen_name));
	}


	
	/*
	 * Get All friends(following) of a specified user, using twitter user id
	 * 
	 * The function fetches all the details about the followers including their latest update(tweet)
	 * 
	 * @param Array Configuration data of Twitter App
	 * @param int | string $user_id  --- Twitter User Id of the user. 
	 */
	function getFriendsByUserId($user_id){
	    return makeAPIRequest('http://twitter.com/statuses/friends.json' , 
	    			array('user_id' => $user_id));
	}	
	
	
	/*
	 * Get All friends (following) of a specified user using user's screen_name
	 *
	 * The function fetches all the details about the followers including their latest update(tweet)
	 
	 * @param  string 	$screen_name  --- 	Twitter Screen Name of the user. 
	 */	
	function getFriendsByHandle($screen_name){
		$consumer = new Zend_Oauth_Consumer($this->config);
	    	return makeAPIRequest('http://twitter.com/statuses/friends.json' , 
	    			array('screen_name' => $screen_name));
	}	

	
	
	/*
	 * Get Updates of a user using user's screen_name, 
	 * All the updates published in a users public page can be retrieved using this function
	 * This includes not only the tweets, but user image, user screen name, timestamp of update, application etc
	 * 
	 * The function fetches all the details about the followers including their latest update(tweet)
	 
	 * @param  string 	$screen_name  --- 	Twitter Screen Name of the user. 
	 */	
	function getPublicUpdatesByHandle($screen_name){
	    $sxml = file_get_contents('http://twitter.com/statuses/user_timeline.json?screen_name='.$screen_name);
	    return json_decode($sxml);
	}	
	
	
	
	/*
	 * Get 20 most recent Updates of a LIST using user_id and list_id,
	 * This is equivalent to the home page of a list :: like www.twitter.com/basilbthoppil/ente-collab
	 * 
	 * This includes not only the tweets, but user image, user screen name, timestamp of update, application etc
	 
	 *
	 * 
	 
	 * @param  string | int 	$user_id  --- 	Twitter user_id of the user. 
	 * @param  string | int 	$list_id  --- 	Twitter users list id of the user.
	 */	
	function getListUpdatesByUseridAndListId($user_id,$list_id){
	    $sxml = file_get_contents('http://api.twitter.com/1/'.$user_id.'/lists/'.$list_id.'/statuses.json');
	    return json_decode($sxml);
	}	

	
	/*
	 * Get 20 most recent Updates of a LIST using user_name and list_name,
	 * This is equivalent to the home page of any user :: like www.twitter.com/basilbthoppil/ente-collab
	 * 
	 * This includes not only the tweets, but user image, user screen name, timestamp of update, application etc
	 
	 *
	 * 
	 
	 * @param  string | int 	$user_id  --- 	Twitter user_id of the user. 
	 * @param  string | int 	$list_id  --- 	Twitter users list id of the user.
	 */	
	function getListUpdatesByUsernameAndListname($user_name,$list_name){
	    $sxml = file_get_contents('http://api.twitter.com/1/'.$user_id.'/lists/'.$list_name.'/statuses.json');
	    return json_decode($sxml);
	}	

	
	/*
	 * Get all list followers,
	 * 
	 * This includes not only the tweets, but user image, user screen name, timestamp of update, application etc
	 *
	 * 
	 
	 * @param  string | int 	$user_id  --- 	Twitter user_id of the user. 
	 * @param  string | int 	$list_id  --- 	Twitter users list id of the user.
	 */	
	function getListMemebersByListname($user_name,$list_name){
	    
		$consumer = new Zend_Oauth_Consumer($this->config);			
		return makeAPIRequest('http://api.twitter.com/1/'.$user_name.'/'.$list_name.'/members.json');	    
	}	
	
	/*
	 * Get all list followers, 
	 * 
	 * This includes not only the tweets, but user image, user screen name, timestamp of update, application etc
	 *
	 * 
	 
	 * @param  string | int 	$user_id  --- 	Twitter user_id of the user. 
	 * @param  string | int 	$list_id  --- 	Twitter users list id of the user.
	 */	
	function getListFollowersByListname($user_name,$list_name){
		
		$consumer = new Zend_Oauth_Consumer($this->config);			
	    	return makeAPIRequest('http://api.twitter.com/1/'.$user_name.'/'.$list_name.'/subscribers.json' );
	}	
	/*
	 * Get most recent tweets contais the string provided,
	 * This is equivalent to the home page of any user :: like www.twitter.com/basilbthoppil/ente-collab
	 * 
	 * 
	 
	 * @param  string | int 	$tweet  --- 	Search String. 
	 */	
	function searchTweetsDetails($tweet){
	    $sxml = file_get_contents('http://search.twitter.com/search.json?lang=en&q='.$tweet);
	    return json_decode($sxml);
	}	

	function followATweety($screen_name){	
		$consumer = new Zend_Oauth_Consumer($this->config);			
          	return makeAPIRequest('http://api.twitter.com/1/friendships/create.json' ,
	    			array('screen_name' => $screen_name)
	    		     );
	}
	
	private function makeAPIRequest($uri , $params = null, $method = Zend_Http_Client::GET) {
	    $token = unserialize($_SESSION['TWITTER_ACCESS_TOKEN']);
	    $token = (object)$token;
	    
	    $client = $token->getHttpClient($this->config);

	    $client->setUri($uri);
	    
	    if(isset($params) && !empty($params)) {
	    	foreach($params as $key => $val ) {
	    		$client->setParameterGet($key , $val);	
	    	}
	    }
	    
	    $client->setMethod($method);
	    
	    $response = $client->request();
	    return json_decode($response->getBody());	
	}
	
}

?>