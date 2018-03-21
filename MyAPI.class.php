<?php
require_once 'config.php';
require_once 'MapAPIKey.class.php';
require_once 'API.class.php';
require_once 'ErrorHandler.class.php';
require_once 'Validator.class.php';

require_once 'DatabaseConnection.class.php';
require_once 'APIKey.class.php';
require_once 'JWT.class.php';
require_once 'UserToken.class.php';
require_once 'FacebookUser.class.php';
require_once 'Reply.class.php';
require_once 'Review.class.php';
require_once 'PetrolStation.class.php';


class MyAPI extends API{

    protected $UserToken;
    protected $Validator;
    protected $requestOrigin;
    protected $ErrorHandler;
    protected $FacebookUser;
    protected $PetrolStation;
    protected $Review;
    protected $Reply;

    public function __construct($request, $origin){
        //This contructor takes in the super global $_REQUEST array as a parameter which should contain a request and apiKey index
        //and also the $_SERVER['HTTP_ORIGIN']
        parent::__construct($request['request']);
        $this->ErrorHandler = new ErrorHandler("MyAPI");
        //Note: It looks like the $origin value will be file:// when a request is made from a phonegap application on android. 
        //Whereas It will be a domain if the request is coming from a server.
        $this->requestOrigin = $origin; 

        //create an instance of the APIKey class, passing in the company APIkey defined in our config file to the constructor
        $APIKey = new APIKey(COMPANYAPIKEY);

      
        if (!array_key_exists('apiKey', $request)) {
          //  throw new Exception('No API Key provided');
        }elseif(!$APIKey->verifyKey($request['apiKey'])){

         //   throw new Exception('Invalid API Key');
        }

      
    }

    protected function googleMapAPIKey(){
      if ($this->method == 'GET'){
          $data = array(); //an array to hold the data returned to the client
          $MapAPIKey = new MapAPIKey(GOOGLEMAPAPIKEY);
          $data['googleMapAPIKey'] = $MapAPIKey->getMapAPIKey();


          return $data;
      }else{
          throw new Exception("Only accepts GET requests");
      }
    }



    /**
     * facebookAuth Endpoint
     * Format: domain.com/facebookAuth?apiKey=12345678...
     * A HTTP POST request is performed on the client side to this endpoint and a users facebook data is sent with the request.
     * We firstly check if the data is valid (a valid length and valid encoding-methods defined in Validator class). 
     * We also filter the data for security.
     * We then process the data to see if it exists in our database.
     * Once this is done we return the users facebook profile details in JSON format.  
     */
    protected function facebookAuth(){
        if ($this->method == 'POST'){
            //Our POST data will be stored in our $this-request variable defined in the API class.
            $facebookUserID = $this->request->facebookUserID;
            $facebookName =  $this->request->facebookName;
            $profilePicURL = $this->request->profilePicURL;
            $filteredInputs = array(); //an array to hold the filtered inputs
            //create an instance of the Validator class
            $this->Validator = new Validator();
            //Check if the inputs are a valid length and also valid utf8 encoding
            $inputsAreValid = $this->Validator->checkInputsAreValid(
                array(
                      array("input" => $facebookUserID, "minLength" => 1, "maxLength" => 30), 
                      array("input" => $facebookName, "minLength" => 1, "maxLength" => 60),
                      array("input" => $profilePicURL, "minLength" => 10, "maxLength" => 250))); 

            if($inputsAreValid){
                //If the inputs are a valid length and also valid utf8 then filter them.
                $filteredInputs = $this->Validator->filterInputs(
                    array(array("input" => $facebookUserID, "filterMethod" => FILTER_SANITIZE_NUMBER_INT),
                          array("input" => $facebookName, "filterMethod" => FILTER_SANITIZE_STRING),
                          array("input" => $profilePicURL, "filterMethod" => FILTER_SANITIZE_STRING)));
           
                //create an instance of the FacebookUser class
                $this->FacebookUser = new FacebookUser();
                $data = array(); //an array to hold the data returned to the client
                $data = $this->FacebookUser->checkFacebookDetails($filteredInputs[0], $filteredInputs[1], $filteredInputs[2]);

                //On successful authentication we also need to generate a JSON web token here and send it back to the client side.
                $this->UserToken = new UserToken();
                $data['userToken'] = $this->UserToken->createFacebookUserToken($data['userID'], $data['facebookUserID'], $data['userPrivilegeID']);  

                $data['status'] = "success";  
              //  $data['origin'] =  $this->requestOrigin;  //for checking what the origin is on phonegap applications
                return $data;
            }else{
                 throw new Exception("User input values are not valid");
            }
        }else{
            throw new Exception("Only accepts POST requests");
        }
    }
    /**
     * tokenAuth Endpoint
     * Format: domain.com/tokenAuth?apiKey=12345678...
     * The purpose of this method is to validate a userToken sent from the client side through POST method.
     * Once validated we return the users facebook profile details (from the userID in the userToken) to the client side in JSON format
     */
    protected function tokenAuth(){
        if ($this->method == 'POST'){
            $userToken = $this->request->userToken;

            //create an instance of the Validator class
            $this->Validator = new Validator();
        
            //filter the JSON web token.
            $filteredInputs = $this->Validator->filterInputs(
                    array(array("input" => $userToken, "filterMethod" => FILTER_SANITIZE_STRING)));

            //Store the filtered token into our userToken variable
            $userToken = $filteredInputs[0]; 
            //Create an instance of the UserToken class.
            $this->UserToken = new UserToken(); 
            $userID = $this->UserToken->verifyUserToken($userToken);

            $data = array(); //an array to hold the data returned to the client

            if($userID !== NULL){
                //the userID is not null, therefore the userToken is valid.
                //create an instance of the FacebookUser class and use the userID to get this users profile data
                $this->FacebookUser = new FacebookUser();
                $data = $this->FacebookUser->getFacebookUserProfile($userID);
                $data['userToken'] = $userToken;  
            }else{

                throw new Exception('UserID not valid');
            }

            return $data;
        }else{
             throw new Exception("Only accepts POST requests");

        }

        
    }

    /**
     * allStationsMapData Endpoint
     * Format: domain.com/allStationsMapData?apiKey=12345678...
     * Get the data for all petrol stations(needed for pinning the stations on a google map).
     * We don't need to send the userToken as a param with this request because 
     * the user doesnt have to be logged in to see the stations on the map
     */

    protected function allStationsMapData(){
        if($this->method == 'GET'){
            $this->PetrolStation = new PetrolStation();
            $allStationsPositionData = array(); //an array to hold the data returned to the client
            $allStationsPositionData = $this->PetrolStation->getAllStationsMapData();
            return $allStationsPositionData;
        }else{
            throw new Exception("Only accepts GET requests");
        }
    }

    /**
     * station Endpoint
     * Format: domain.com/station/stationID?apiKey=12345678...
     * Get the details of a particular petrol station by the stationID
     * We don't need to send the userToken as a param with this request because the user doesnt have to be logged in to see the stations
     */
    protected function station(){
        if($this->method == 'GET'){
            //Firstly check if there are any arguments in the request.
            if(count($this->args) > 0){
                //The stationID will be the first argument in the request.
                $stationID = $this->args[0];
                $this->PetrolStation = new PetrolStation();
                $data = array(); //an array to hold the data returned to the client
                $data = $this->PetrolStation->getStationDetails($stationID);
                return $data;
            }else{
                $errorMessage = array("error" => "StationID is missing from request");
                return $errorMessage;
            }
        }else{
            throw new Exception("Only accepts GET requests");
        }
    }

    /**
     * userReviews Endpoint
     * Format: domain.com/userReviews/stationID?apiKey=12345678...
     * Get the reviews for a particular petrol station by the stationID
     * We don't need to send the userToken as a param with this request because the user doesnt have to be logged in to see the reviews
     */
    protected function userReviews(){
        if($this->method == 'GET'){
            //Firstly check if there are any arguments in the request.
            if(count($this->args) > 0){
                //The stationID will be the first argument in the request.
                $stationID = $this->args[0];

                $this->Review = new Review();
                $data = array(); //an array to hold the data returned to the client

                $data = $this->Review->getReviews($stationID);
          
                return $data;
            }else{
                $errorMessage = array("error" => "StationID is missing from the request");
                return $errorMessage;
            }
        }else{
            throw new Exception("Only accepts GET requests");

        }
    }


    /**
     * createReview Endpoint
     * Format: domain.com/createReview?apiKey=12345678...
     * Create a review (which was made by a user) for a particular petrol station.
     * We do need to send the userToken as a param with this request because the user has to be logged in to create a review
     * Parameters include: userToken, reviewRating, reviewText, stationID
     */
    protected function createReview(){
        if($this->method == 'POST'){
            //get the data sent in the request.
            $userToken = $this->request->userToken;
            $reviewText = $this->request->reviewText;
            $reviewRating = $this->request->reviewRating;
            $stationID = $this->request->stationID;

            //Create an instance of the userToken class.
            $this->UserToken = new UserToken(); 
            //Firstly check if the userToken is valid
            $userIDFromToken = $this->UserToken->verifyUserToken($userToken);

            if($userIDFromToken !== NULL){
                //userToken is valid so we can proceed with the processing.
    
                $filteredInputs = array(); //an array to hold the filtered inputs

                //create an instance of the Validator class
                $this->Validator = new Validator();
                //Check if the inputs are a valid length and also valid utf8 encoding 
                $inputsAreValid = $this->Validator->checkInputsAreValid(
                    array(
                        array("input" => $reviewText, "minLength" => 10, "maxLength" => 2000), 
                        array("input" => $reviewRating, "minLength" => 1, "maxLength" => 1),
                        array("input" => $stationID , "minLength" => 1, "maxLength" => 5))); 

                if($inputsAreValid){
                    //If the inputs are a valid length and also valid utf8 then filter them.
                    //We wont filter the reviewText here as we will filter it when retrieving it from the database.
                    //I know there was a good reason for doing this (something to do with utf8mb4 and emojis).
                    $filteredInputs = $this->Validator->filterInputs(
                    array(
                        array("input" => $reviewRating, "filterMethod" => FILTER_SANITIZE_NUMBER_INT),
                        array("input" => $stationID, "filterMethod" => FILTER_SANITIZE_NUMBER_INT)));
                    $filteredReviewRating = $filteredInputs[0];
                    $filteredStationID = $filteredInputs[1];
                    

                    $this->Review = new Review();
                    $this->Review->createReview($userIDFromToken, $reviewText, $filteredReviewRating, $filteredStationID);
                    $reviews = $this->Review->getReviews($filteredStationID);
                    return $reviews;
                }else{
                    //Either the lengths of the inputs or the encoding is not valid.
                    $errorMessage = array("error" => "Invalid review form data");
                    return $errorMessage;
                }
            }else{
                //Log this error
                $this->ErrorHandler->createLogEntry("createReview", "UserToken is not valid.");
                throw new Exception("UserToken is not valid.");
            }

        }else{
            throw new Exception("Only accepts POST requests");

        }
    }

    /**
     * editReview Endpoint
     * Format: domain.com/editReview/reviewID?apiKey=12345678...
     * Edit a review with an ID of reviewID for a particular petrol station.
     * We do need to send the userToken as a param with this request because the user has to be logged in to edit a review
     * We also need to check if the userID stored in the userToken matches the userID of the creator of the review before allowing the edit
     * Data sent with request: userToken, stationID, editedReviewText and newRating
     */

    protected function editReview(){
        if ($this->method == 'PUT') {
            //reviewID will be the first argument of the endpoint URI.
            $reviewID = $this->args[0];
            //get the data sent in the request.
            $userToken = $this->request->userToken;
            $stationID = $this->request->stationID;       
            $editedReviewText = $this->request->editedReviewText;
            $newRating = $this->request->newRating;

            //Create instances of our classes.
            $this->UserToken = new UserToken(); 
            $this->Review = new Review();

            //get the userID stored in the userToken.
            $userIDFromToken = $this->UserToken->verifyUserToken($userToken);
            //Get the userID of the review with reviewID that we've passed in as an argument.
            $reviewUserID = $this->Review->getReviewUserID($reviewID);

            //check if userToken is valid
            if(($userIDFromToken !== NULL) && ($reviewUserID === $userIDFromToken)){
                //userToken is valid and also the user is the owner of the review so we can proceed to edit the review.

                $this->Review->updateReview($reviewID, $editedReviewText, $newRating);
                $reviews = $this->Review->getReviews($stationID);
                return $reviews;
            }else{
                //Log this error
                $this->ErrorHandler->createLogEntry("editReview", "UserToken is not valid.");
                throw new Exception("UserToken is not valid.");
            }

        }else{
            throw new Exception("Only accepts PUT requests");

        }
    }

    /**
     * deleteReview Endpoint
     * Format: domain.com/deleteReview/reviewID?apiKey=12345678...
     * Delete a review with an ID of reviewID for a particular petrol station.
     * We do need to send the userToken as a param with this request because the user has to be logged in to delete a review
     * We also need to check if the userID stored in the userToken matches the userID of the creator of the review 
     * OR if the user has an administrator userPrivilegeID of 2.
     * Data sent with request: userToken, stationID
     */
    protected function deleteReview(){
        if ($this->method == 'DELETE') {
            //reviewID will be the first argument of the endpoint URI.
            $reviewID = $this->args[0];
            //get the data sent in the request.
            $userToken = $this->request->userToken;
            $stationID = $this->request->stationID;        

            //Create instances of our classes.
            $this->UserToken = new UserToken(); 
            $this->Review = new Review();

            //get the userID and the userPrivilegeID stored in the userToken.
            $userIDFromToken = $this->UserToken->verifyUserToken($userToken);
            $userPrivilegeIDFromToken = $this->UserToken->getUserPrivilegeIDFromToken($userToken);
            //Get the userID of the review with reviewID that we've passed in as an argument.
            $reviewUserID = $this->Review->getReviewUserID($reviewID);

            //check if userToken is valid
            if(($userIDFromToken !== NULL) && ($reviewUserID === $userIDFromToken ||  $userPrivilegeIDFromToken === 2)){
                //userToken is valid and also the user is either the owner of the review or is an administrator 
                //so we can proceed to delete the review.
                $this->Review->deleteReview($reviewID);
                //Get all the reviews for this station. We need to do this because the average rating will need to be calculated again
                //with the reviews array.
                $reviews = $this->Review->getReviews($stationID);
                return $reviews;
            }else{
                //Log this error
                $this->ErrorHandler->createLogEntry("deleteReview", "UserToken is not valid.");
                throw new Exception("UserToken is not valid.");
            }

        }else{
            throw new Exception("Only accepts DELETE requests");

        }
    }


    /**
     * createReply Endpoint
     * Format: domain.com/createReply?apiKey=12345678...
     * Create a reply (which was made by a user) for a particular review of a petrol station.
     * We do need to send the userToken as a param with this request because the user has to be logged in to create a reply
     * Parameters include: userToken, replyText, reviewID
     */
    protected function createReply(){
        if ($this->method == 'POST'){
            //get the data sent in the request.
            $userToken = $this->request->userToken;
            $replyText = $this->request->replyText;
            $reviewID = $this->request->reviewID;

            //Create instances of our classes.
            $this->UserToken = new UserToken(); 
            $this->Reply = new Reply();
            $this->Review = new Review();
       
            //get the userID and the userPrivilegeID stored in the userToken.
            $userIDFromToken = $this->UserToken->verifyUserToken($userToken);
            $userPrivilegeIDFromToken = $this->UserToken->getUserPrivilegeIDFromToken($userToken);
            //Get the userID of the review with reviewID that we've passed in as an argument so we can check if the user who wants to reply 
            //to the review is the same user who created the review. (Note: administrators are also allowed to reply to reviews).
            $reviewUserID = $this->Review->getReviewUserID($reviewID);

            //check if userToken is valid
            if(($userIDFromToken !== NULL) && ($reviewUserID === $userIDFromToken ||  $userPrivilegeIDFromToken === 2)){
                //userToken is valid and also the user is either the owner of the review or is an administrator 
                //so we can proceed to create the reply.
                $replyID = $this->Reply->createReply($userIDFromToken, $replyText, $reviewID);
                //get the replies array of the review that this reply belonged to
                $replies = $this->Reply->getReviewReplies($reviewID);
                return $replies;
            }else{
                //Log this error
                $this->ErrorHandler->createLogEntry("createReply", "You do not have the right permissions.");
                throw new Exception("You do not have the right permissions.");

            }

        }else{
            throw new Exception("Only accepts POST requests");

        }
    }

    /**
     * editReply Endpoint
     * Format: domain.com/editReply?replyID?apiKey=12345678...
     * Update a reply with an ID of replyID for a particular review of a petrol station.
     * We do need to send the userToken as a param with this request because the user has to be logged in to edit a reply
     * Parameters include: userToken, editedReplyText, reviewID
     */
    protected function editReply(){
        if ($this->method == 'PUT') {
            //replyID will be the first argument of the endpoint URI.
            $replyID = $this->args[0];
            //get the data sent in the request.
            $userToken = $this->request->userToken;
            $reviewID = $this->request->reviewID;      //need reviewID to generate the replies array again after editing the reply. 
            $editedReplyText = $this->request->editedReplyText;

            //Create instances of our classes.
            $this->UserToken = new UserToken(); 
            $this->Reply = new Reply();
            $this->Review = new Review();

            //get the userID stored in the userToken.
            $userIDFromToken = $this->UserToken->verifyUserToken($userToken);
            //we need to check that the user editing the reply is the same user that created it.
            //We dont need to know the userPrivilegeID

            //Get the userID of the reply with replyID that we've passed in as an argument.
            $replyUserID = $this->Reply->getReplyUserID($replyID);


            //check if userToken is valid
            if(($userIDFromToken !== NULL) && ($replyUserID === $userIDFromToken)){
                //userToken is valid and also the user is the owner of the reply so we can proceed to edit the reply.

                $this->Reply->updateReply($replyID, $editedReplyText);
                //get the replies array of the review that this reply belonged to
                $replies = $this->Reply->getReviewReplies($reviewID);
                return $replies;
            }else{
                //Log this error
                $this->ErrorHandler->createLogEntry("editReply", "UserToken is not valid.");
                throw new Exception("UserToken is not valid.");
            }

        }else{
            throw new Exception("Only accepts PUT requests");

        }
    }


    /**
     * deleteReply Endpoint
     * Format: domain.com/deleteReply/replyID?apiKey=12345678...
     * Delete a reply with an ID of replyID for a particular review of a petrol station.
     * We do need to send the userToken as a param with this request because the user has to be logged in to delete a reply
     * Parameters include: userToken, reviewID
     */
    protected function deleteReply(){
        if ($this->method == 'DELETE') {
            //replyID will be the first argument of the endpoint URI.
            $replyID = $this->args[0];
             //get the data sent in the request.
            $userToken = $this->request->userToken;
            $reviewID = $this->request->reviewID;

            //Create instances of our classes.
            $this->UserToken = new UserToken(); 
            $this->Reply = new Reply();
            $this->Review = new Review();
       
            //get the userID and the userPrivilegeID stored in the userToken.
            $userIDFromToken = $this->UserToken->verifyUserToken($userToken);
            $userPrivilegeIDFromToken = $this->UserToken->getUserPrivilegeIDFromToken($userToken);
            //Get the userID of the reply with replyID that we've passed in as an argument.
            $replyUserID = $this->Reply->getReplyUserID($replyID);

            if(($userIDFromToken !== NULL) && ($replyUserID === $userIDFromToken ||  $userPrivilegeIDFromToken === 2)){
                //userToken is valid and also the user is either the creator/owner of the reply or is an administrator 
                //so we can proceed to delete the reply.

                $this->Reply->deleteReply($replyID);
                //get the replies array of the review that this reply belonged to
                $replies = $this->Reply->getReviewReplies($reviewID);
                return $replies;
            }else{
                //Log this error
                $this->ErrorHandler->createLogEntry("deleteReply", "UserToken is not valid.");
                throw new Exception("UserToken is not valid.");
            }

        }else{
            throw new Exception("Only accepts DELETE requests");
        }
    }



    /**
     * facebookProfile Endpoint Not using currently 
     * Get the facebook profile of a user
     */
    protected function facebookProfile(){
        if ($this->method == 'GET') {
            $facebookUserID = '10213718552614326';
            $facebookName = 'La';
            $profilePicURL = 'https://scontent.xx.fbcdn.net/v/t1.0-1/p50x50/21617751_10213716829451248_7798041643913998634_n.jpg?oh=7242e13b731a211fa7ac77ed443ec96f&oe=5A483F35';
            //  $userID = $parameters[0];

            //Use the Validator class to validate our inputs.
            $this->Validator = new Validator();
            $inputsAreValid = $this->Validator->checkInputsAreValid(array(array("input" => $facebookUserID, "minLength" => 10, "maxLength" => 30), 
                                                                          array("input" => $facebookName, "minLength" => 2, "maxLength" => 40),
                                                                          array("input" => $profilePicURL, "minLength" => 10, "maxLength" => 250))); 
            if($inputsAreValid){
                //If the inputs are valid then filter them.
                $filteredInputs = $this->Validator->filterInputs(array(array("input" => $facebookUserID, "filterMethod" => FILTER_SANITIZE_NUMBER_INT),
                                                                      array("input" => $facebookName, "filterMethod" => FILTER_SANITIZE_STRING),
                                                                      array("input" => $profilePicURL, "filterMethod" => FILTER_SANITIZE_STRING)));
            }
       
           // return "Your name is " . $this->args[0] . json_encode($filteredInputs);
            $this->FacebookUser = new FacebookUser();
            $facebookProfileData = $this->FacebookUser->checkFacebookDetails($filteredInputs[0], $filteredInputs[1], $filteredInputs[2]); 

  
            return $facebookProfileData;
        } else {
            $errorMessage = array("error" => "Only accepts GET requests");
            return $errorMessage;
        }
    }

}
?>
