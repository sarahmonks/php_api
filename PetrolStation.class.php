<?php
/*
 * A class for working with petrol station data
 */
class PetrolStation{

    private $DatabaseConnection;
    private $ErrorHandler;
    private $Review; //to store an instance of the Review class
    private $stationID = null;    //initialize the stationID to null
    private $stationName; 
    private $stationAddressLine1;    
    private $stationAddressLine2;
    private $stationAddressLine3;
    private $stationPhoneNumber;    
    private $stationLatitude;    
    private $stationLongitude;  
    private $stationServices = array();
    private $reviews = array();  


    public function __construct(){
        $this->DatabaseConnection = DatabaseConnection::getInstance();
        $this->ErrorHandler = new ErrorHandler("PetrolStation");
        $this->Review = new Review();
    }

    public function getAllStationsMapData(){
        /*
         */
        $allStationsPositionData = array();
        foreach($this->DatabaseConnection->getAllStationsMapData() as $key => $value){	
            array_push(
                $allStationsPositionData, array(
                    'stationID' => $value['stationID'],
                    'stationName' => $value['stationName'],
                    'stationAddressLine2' => $value['stationAddressLine2'],
                    'stationLatLng' => array("lat" => $value['stationLatitude'], "lng" => $value['stationLongitude'])
                    )
                ); 
        }
        return $allStationsPositionData;
    }


    public function getStationDetails($stationID){
        /*
         * This method takes in a stationID and gets all station data related to that stationID.
         */
        $this->stationID = $stationID;
        $stationData = array(); //initialize the $stationData array which will store the data we return from this method.
		
        //get the station data from the database. 
        //An exception will be thrown in the DatabaseConnection class if the stationID does not exist in the database	
        $stationData = $this->DatabaseConnection->getStationData($this->stationID);	 

        //get the review data for this stationID and store it in a key called 'reviews' in the stationData array
        $stationData['reviews'] = $this->Review->getReviews($this->stationID);
        //get the stationServices data for this stationID and store it in a key called 'stationServices' in the stationData array
        $stationData['stationServices'] = $this->DatabaseConnection->getStationServicesData($this->stationID);
        return $stationData;	
    }

}

?>