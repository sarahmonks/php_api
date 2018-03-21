<?php
/*
 * A class for working with a user's facebook public profile data.
 */
class FacebookUser{
    private $DatabaseConnection;
    private $ErrorHandler;
    private $facebookUserID;    
    private $facebookName; 
    private $profilePicURL;    
    private $userPrivilegeID = 1; //default value of 1 which is a regular user. 
    private $userID = null; //this will be the unique userID in our database. 

    public function __construct(){
        $this->DatabaseConnection = DatabaseConnection::getInstance();
        $this->ErrorHandler = new ErrorHandler("FacebookUser");
    }

    public function checkFacebookDetails($facebookUserID, $facebookName, $profilePicURL, $userPrivilegeID = 1){
        /*
         * This method checks to see if a users unique facebook ID already exists in our database.
         * If it doesnt exist then we enter their details to the database and send their profile data back to the client side.
         * If it does exist then we just send their profile data back to the client side.
         */
        $this->facebookUserID = $facebookUserID;
        $this->facebookName = $facebookName;	
        $this->profilePicURL = $profilePicURL;	
        $this->userPrivilegeID = $userPrivilegeID;
        $userID = $this->DatabaseConnection->checkFacebookID($this->facebookUserID);

        if($userID === NULL) {
            //facebookUserID does not exist in database so create a new facebook user.
            $this->userID = $this->DatabaseConnection->createNewFacebookUser($this->facebookUserID, $this->facebookName, $this->profilePicURL, $this->userPrivilegeID);
        }else{
            $this->userID = $userID;
        }
        //return the users facebook details (which are stored in our database).
        return $this->getFacebookUserProfile($this->userID);
    }

    public function getFacebookUserProfile($userID){
        /* 
         * This method takes in a userID as a parameter and gets a users profile data from our database
         */
        $userDetails = array();
        $userDetails = $this->DatabaseConnection->getUserProfile($userID);
        return $userDetails;
    }

    public function getFacebookName(){
        return $this->facebookName;
    }

    public function getUserID(){
        return $this->userID;
    }

}

?>