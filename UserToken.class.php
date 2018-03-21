<?php
/*
 * A class for working with a JWT (i.e a userToken for our application) 
 */
class UserToken{

    private $ErrorHandler;
    private $tokenArray = array();
    private $userToken;
    private $userID = null;
    private $facebookUserID;
    private $userPrivilegeID;


    public function __construct(){

    }

    public function createFacebookUserToken($userID, $facebookUserID, $userPrivilegeID){
        /*
         * This method creates a JSON Web Token with a userID, facebookUserID and userPrivilegeID of a user.
         * We first store our userID, facebookUserID and userPrivilegeID in the token array which we then encrypt with the JWT class.
         */
        $this->userID = $userID;
        $this->facebookUserID = $facebookUserID;
        $this->userPrivilegeID = $userPrivilegeID;
        $this->tokenArray['userID'] = $this->userID;
        $this->tokenArray['facebookUserID'] = $this->facebookUserID;
        $this->tokenArray['userPrivilegeID'] = $this->userPrivilegeID;
        $this->userToken = JWT::encode($this->tokenArray, 'secret_server_key');
        return $this->userToken;
    }

    public function verifyUserToken($userToken){
        /*
         * This method takes in a userToken and checks to see if it is a valid token.
         * We return the userID that was stored in the userToken.
         * If the userToken is not valid then the userID returned will be null.
         */
        $this->userToken = $userToken;
        $this->tokenArray = JWT::decode($this->userToken, 'secret_server_key');
	
        if($this->tokenArray->userID){
            //userID will not exist if the userToken has been tampered so therefore userToken is valid
            $this->userID = $this->tokenArray->userID;
        }
        return $this->userID;
    }

    public function getUserPrivilegeIDFromToken($userToken){
        /*
         * This method takes in a userToken and gets the userPrivilegeID from the token.
         */
        $this->userToken = $userToken;
        $this->tokenArray = JWT::decode($this->userToken, 'secret_server_key');
	
        if($this->tokenArray->userPrivilegeID){
            $this->userPrivilegeID = $this->tokenArray->userPrivilegeID;
        }
        return $this->userPrivilegeID;
    }
}

?>