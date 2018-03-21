<?php
/*
 * A class for working with a google map API Key. 
 */
class MapAPIKey{

	private $googleMapAPIKey = null; //google map API key which will be stored privately on the server
	private $ErrorHandler;

 
	public function __construct($googleMapAPIKey){
		/*
		 * This constructor takes in a google map API key. 
		 * This google map API key will be defined in our config file which will be stored privately on the server.
		 */
		$this->googleMapAPIKey = $googleMapAPIKey;
		//Create an instance of the ErrorHandler class in order to log errors
		$this->ErrorHandler = new ErrorHandler("APIKey");
	}

	public function getMapAPIKey(){
		/*
		 */
        try{

            return $this->googleMapAPIKey;
            
        }catch(Exception $e){
		    //create a log entry to record the error message
			$this->ErrorHandler->createLogEntry("getMapAPIKey", $e->getMessage());
		    return $this->googleMapAPIKey;
		}
	
	}

}

?>