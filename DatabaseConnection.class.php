<?php
/*
 * A class which contains all methods for retrieving and inserting data to our application database.
 * 
 */
class DatabaseConnection{
    private $query;
    private $statement;
    private static $instance = null;	
    private $pdoConnection;
    private $ErrorHandler;
    private $Validator;

    public function __construct(){
        $this->ErrorHandler = new ErrorHandler("DatabaseConnection");
        try{
            //construct a new PDO object.
            $this->pdoConnection = new PDO(HOSTDBNAME, USER, PASSWORD);
            $this->pdoConnection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $this->pdoConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdoConnection->exec("SET CHARACTER SET utf8mb4");
        }catch(PDOException $e){ 
        	$this->ErrorHandler->createLogEntry("construct", $e->getMessage());
            throw new Exception("Error connecting to database ");
        }
        $this->Validator = new Validator();
    }

    public static function getInstance(){
        if(!isset(self::$instance)){ // Check if the instance is not set.
            //create a new DatabaseConnection instance which will execute the code in the above constructor.			
            self::$instance = new DatabaseConnection();	
        }
        return self::$instance;	
	}

    public function createNewFacebookUser($facebookUserID, $facebookName, $profilePicURL, $userPrivilegeID){
        /*
         * This method takes in a users facebook credentials as parameters and inserts them to our facebook_users table.
         * If successfully inserted then we return an array containing the userID value.
         * If unsuccessful return an exception
         * 
         */
        $userID = null;
        try{
            $this->query  = "INSERT INTO facebook_users (facebookUserID, facebookName, profilePicURL, userPrivilegeID) VALUES (:facebookUserID, :facebookName, :profilePicURL, :userPrivilegeID)";
            $this->statement = $this->pdoConnection->prepare($this->query);
            $this->statement->bindValue(':facebookUserID', $facebookUserID, PDO::PARAM_STR);
            $this->statement->bindValue(':facebookName', $facebookName, PDO::PARAM_STR);
            $this->statement->bindValue(':profilePicURL', $profilePicURL, PDO::PARAM_STR);
            $this->statement->bindValue(':userPrivilegeID', $userPrivilegeID, PDO::PARAM_INT);
            $this->statement->execute();
            $userID = $this->pdoConnection->lastInsertId();	
            return $userID;
        }catch(PDOException $e){
            //create a log entry to record the error message
            $this->ErrorHandler->createLogEntry("createNewFacebookUser", $e->getMessage());
            throw new Exception("Error logging in facebook user");
        }
    }

    public function checkFacebookID($facebookUserID){
        /*
         * This method checks to see if a users facebook id exists in our facebook_users table.
         * If it does exist then we get the userID value from our database for this record and return it.
         * If it doesn't exist then the userID returned from this method will be null.
         */
        $userID = null;
        try{
            $this->query = "SELECT userID FROM facebook_users WHERE facebookUserID = :facebookUserID LIMIT 1";
            $this->statement = $this->pdoConnection->prepare($this->query);
            $this->statement->bindValue(':facebookUserID', $facebookUserID, PDO::PARAM_STR); 
            $this->statement->execute();
            $this->statement->setFetchMode(PDO::FETCH_ASSOC);		
            while($row = $this->statement->fetch()){
                $userID = $row['userID'];
            }
            return $userID;
        }catch(PDOException $e){
            //create a log entry to record the error message
            $this->ErrorHandler->createLogEntry("checkFacebookID", $e->getMessage());
            throw new Exception("Error checking facebook user ID");
        }     	
    }

    public function getUserProfile($userID){
        /* 
         * This method takes in a userID as a parameter and gets that user's data from the profiles table in the database
         */
        $userDetails = array();
        try{
            $this->query ="SELECT * FROM facebook_users WHERE userID = :userID LIMIT 1";
            $this->statement = $this->pdoConnection->prepare($this->query);
            $this->statement->bindValue(':userID', $userID, PDO::PARAM_INT); 
            $this->statement->execute();
            $this->statement->setFetchMode(PDO::FETCH_ASSOC);
            $count = 0;
            while($row = $this->statement->fetch()){
                //store the data from the result of the query into an associative array. 
                $userDetails = $row;
                $count++;
            }
            if($count > 0){
                return $userDetails;
            }else{
                //create a log entry to record the error message
                $this->ErrorHandler->createLogEntry("getUserProfile", "userID is not valid");
                //throw new Exception("Error getting user profile");
            }	
        }catch(PDOException $e){
            //create a log entry to record the error message
            $this->ErrorHandler->createLogEntry("getUserProfile", $e->getMessage());
            // throw new Exception("Error getting user profile");
        }	
    }


    public function getAllStationsMapData(){
        /*
         * This method gets all station data (necessary for pinpointing the stations on the google map) from the database.
         */
        $allStationsData = array();
        try{
            $this->query ="SELECT * FROM petrol_stations";
            $this->statement = $this->pdoConnection->prepare($this->query);
            $this->statement->execute();
            $this->statement->setFetchMode(PDO::FETCH_ASSOC);
            while($row = $this->statement->fetch()){
                //store the data from the result of the query into an associative array. 
                array_push($allStationsData, $row);
            }
            return $allStationsData;
        }catch(PDOException $e){
            //create a log entry to record the error message
            $this->ErrorHandler->createLogEntry("getAllStationsData", $e->getMessage());
            throw new Exception("Error getting all station data");
        }	
    }


    public function getStationData($stationID){
        /*
         * This method takes in the stationID of a petrol station and gets all the data related to that station from the database. 
         */
        $stationData = array();
        try{
            $this->query ="SELECT * FROM petrol_stations WHERE stationID = :stationID LIMIT 1";
            $this->statement = $this->pdoConnection->prepare($this->query);
            $this->statement->bindValue(':stationID', $stationID, PDO::PARAM_INT); 
            $this->statement->execute();
            $this->statement->setFetchMode(PDO::FETCH_ASSOC);
            $count = 0;
            while($row = $this->statement->fetch()){
                //store the data from the result of the query into an associative array. 
                $stationData['stationID'] = $row['stationID'];
                $stationData['stationName'] = $row['stationName'];
                $stationData['stationAddressLine1'] = $row['stationAddressLine1'];
                $stationData['stationAddressLine2'] = $row['stationAddressLine2'];
                $stationData['stationAddressLine3'] = $row['stationAddressLine3'];
                $stationData['stationPhoneNumber'] = $row['stationPhoneNumber'];
                $stationData['stationLatLng'] = array("lat" => $row['stationLatitude'], "lng" => $row['stationLongitude']);
                $count++;
            }
            if($count > 0){
                return $stationData;
            }else{
                //If the stationID does not exist in the database then communicate this through an exception
                throw new Exception("stationID is not valid");
                $this->ErrorHandler->createLogEntry("getStationData", "stationID is not valid");
            }	
        }catch(PDOException $e){
            //create a log entry to record the error message
            $this->ErrorHandler->createLogEntry("getStationData", $e->getMessage());
            throw new Exception("Error getting station details");
        }	
    }

    public function getStationServicesData($stationID){
        /*
         * This method takes in the stationID of a petrol station and gets all the services data related to that station from the database. 
         */
        $servicesData = array();
        try{
            $this->query = "SELECT DISTINCT services.serviceID, services.serviceName FROM services, station_services WHERE station_services.stationID=:stationID";
            $this->statement = $this->pdoConnection->prepare($this->query);
            $this->statement->bindValue(':stationID', $stationID, PDO::PARAM_INT); 
            $this->statement->execute();
            $this->statement->setFetchMode(PDO::FETCH_ASSOC);
            while($row = $this->statement->fetch()){
                //store the data from the result of the query into an associative array. 
                array_push($servicesData, $row); 
            }	
            return $servicesData;
        }catch(PDOException $e){
            //create a log entry to record the error message
            $this->ErrorHandler->createLogEntry("getStationServicesData", $e->getMessage());
        }	
    }

    public function getReviewData($stationID){
        /*
         * This method takes in the stationID of a petrol station and gets all the review data related to that station from the database. 
         * We order the data by reviewID Descending so that the latest review will be at the start of the array and so forth.
         */

        $reviewData = array();
        try{
            $this->query ="SELECT * FROM facebook_user_reviews WHERE stationID = :stationID  ORDER BY reviewID DESC";
            $this->statement = $this->pdoConnection->prepare($this->query);
            $this->statement->bindValue(':stationID', $stationID, PDO::PARAM_INT); 
            $this->statement->execute();
            $this->statement->setFetchMode(PDO::FETCH_ASSOC);
            while($row = $this->statement->fetch()){
                //store the data from the result of the query into an associative array. 
                array_push($reviewData, $row); 
            }	
            //Return the array of reviews. If there are no reviews for this stationID then the array will be empty.
            return $reviewData;
        }catch(PDOException $e){
            throw new Exception("Error getting review data");
        }	
    }

    public function getReviewReplyData($reviewID){
        /*
         * This method takes in the reviewID of a station review and gets all the replies for that review from the database. 
         * We order the data by replyID Ascending so that the first reply will be at the start of the array and so forth.
         */

        $replyData = array();
        try{
            $this->query ="SELECT * FROM review_replies WHERE reviewID = :reviewID  ORDER BY replyID ASC";
            $this->statement = $this->pdoConnection->prepare($this->query);
            $this->statement->bindValue(':reviewID', $reviewID, PDO::PARAM_INT); 
            $this->statement->execute();
            $this->statement->setFetchMode(PDO::FETCH_ASSOC);
            while($row = $this->statement->fetch()){
                array_push($replyData, $row); 
            }	
            return $replyData;
        }catch(PDOException $e){
            throw new Exception("Error getting reply data");
        }	
    }

    public function getReviewUserID($reviewID){
        /*
         * This method takes in a reviewID and gets the userID of that review from the database. 
         * We will use this method before updating and deleting reviews to check the user performing the action is the creator of the review. 
         */

        $reviewUserID = null;
        try{
            $this->query ="SELECT userID FROM facebook_user_reviews WHERE reviewID = :reviewID LIMIT 1";
            $this->statement = $this->pdoConnection->prepare($this->query);
            $this->statement->bindValue(':reviewID', $reviewID, PDO::PARAM_INT); 
            $this->statement->execute();
            $this->statement->setFetchMode(PDO::FETCH_ASSOC);
            while($row = $this->statement->fetch()){
                //store the userID from the result of the query 
                $reviewUserID = $row['userID']; 
            }	
            return $reviewUserID;
        }catch(PDOException $e){
            $this->ErrorHandler->createLogEntry("getReviewUserID", $e->getMessage());
            throw new Exception("Error getting review userID");
        }	
    }

    public function createReview($userID, $reviewText, $reviewRating, $stationID){
        /*
         * This method takes in a details associated with a user review as parameters and inserts them to our facebook_user_reviews table.
         * If successfully inserted then we return the new reviewID which is the primary key of the table. 
         * If theres an error throw and exception
         */
        $reviewTime = time();
        try{
            $this->query  = "INSERT INTO facebook_user_reviews (userID, reviewText, reviewRating, stationID, reviewTime) VALUES (:userID, :reviewText, :reviewRating, :stationID, :reviewTime)";
            $this->statement = $this->pdoConnection->prepare($this->query);
            $this->statement->bindValue(':userID', $userID, PDO::PARAM_INT);
            $this->statement->bindValue(':reviewText', $reviewText, PDO::PARAM_STR);
            $this->statement->bindValue(':reviewRating', $reviewRating, PDO::PARAM_INT);
            $this->statement->bindValue(':stationID', $stationID, PDO::PARAM_INT);
            $this->statement->bindValue(':reviewTime', $reviewTime, PDO::PARAM_STR);
            $this->statement->execute();
            $reviewID = $this->pdoConnection->lastInsertId();	
            return $reviewID;
        }catch(PDOException $e){
            //create a log entry to record the error message
            $this->ErrorHandler->createLogEntry("createReview", $e->getMessage());
            throw new Exception("Error creating review");
        }
    }

    public function updateReview($reviewID, $editedReviewText, $newRating){
        /*
         * This method takes in a reviewID, editedReviewText and a newRating and updates an exising row of the facebook_user_reviews table.
         * We won't alter the original time of the review so no need to update the reviewTime field
         * If theres an error throw and exception
         */

        try{
            $this->query  = "UPDATE facebook_user_reviews SET reviewText=:reviewText, reviewRating=:reviewRating WHERE reviewID=:reviewID";
            $this->statement = $this->pdoConnection->prepare($this->query);
            $this->statement->bindValue(':reviewID', $reviewID, PDO::PARAM_INT);
            $this->statement->bindValue(':reviewText', $editedReviewText, PDO::PARAM_STR);
            $this->statement->bindValue(':reviewRating', $newRating, PDO::PARAM_INT);		
            $this->statement->execute();
        }catch(PDOException $e){
            //create a log entry to record the error message
            $this->ErrorHandler->createLogEntry("updateReview", $e->getMessage());
            throw new Exception("Error updating review");
        }
    }


    public function deleteReview($reviewID){
        /*
         * This method takes in a reviewID and deletes that review from the facebook_user_reviews table.
         * If theres an error throw and exception
         */
        try{
            $this->query  = "DELETE FROM facebook_user_reviews WHERE reviewID = :reviewID";
            $this->statement = $this->pdoConnection->prepare($this->query);
            $this->statement->bindValue(':reviewID', $reviewID, PDO::PARAM_INT);		
            $this->statement->execute();
        }catch(PDOException $e){
            //create a log entry to record the error message
            $this->ErrorHandler->createLogEntry("deleteReview", $e->getMessage());
            throw new Exception("Error deleting review");
        }
    }

    public function getReplyUserID($replyID){
        /*
         * This method takes in a replyID and gets the userID of that reply from the database. 
         * We will use this method before updating and deleting replies to check the user performing the action is the creator of the reply. 
         */

        $replyUserID = null;
        try{
            $this->query ="SELECT userID FROM review_replies WHERE replyID = :replyID LIMIT 1";
            $this->statement = $this->pdoConnection->prepare($this->query);
            $this->statement->bindValue(':replyID', $replyID, PDO::PARAM_INT); 
            $this->statement->execute();
            $this->statement->setFetchMode(PDO::FETCH_ASSOC);
            while($row = $this->statement->fetch()){
                //store the userID from the result of the query 
                $replyUserID = $row['userID']; 
            }	
            return $replyUserID;
        }catch(PDOException $e){
            $this->ErrorHandler->createLogEntry("getReplyUserID", $e->getMessage());
            throw new Exception("Error getting reply userID");
        }	
    }

    public function createReply($userID, $replyText, $reviewID){
        /*
         * This method takes in a details associated with a reply of a review as parameters and inserts them to our review_replies table.
         * If successfully inserted then we return the new replyID which is the primary key of the table. 
         * If theres an error throw and exception
         */
        $replyTime = time();
        try{
            $this->query  = "INSERT INTO review_replies (userID, replyText, reviewID, replyTime) VALUES (:userID, :replyText, :reviewID, :replyTime)";
            $this->statement = $this->pdoConnection->prepare($this->query);
            $this->statement->bindValue(':userID', $userID, PDO::PARAM_INT);
            $this->statement->bindValue(':replyText', $replyText, PDO::PARAM_STR);
            $this->statement->bindValue(':reviewID', $reviewID, PDO::PARAM_INT);
            $this->statement->bindValue(':replyTime', $replyTime, PDO::PARAM_STR);
            $this->statement->execute();
            $replyID = $this->pdoConnection->lastInsertId();	
            return $replyID;
        }catch(PDOException $e){
            //create a log entry to record the error message
            $this->ErrorHandler->createLogEntry("createReply", $e->getMessage());
            throw new Exception("Error creating reply");
        }
    }

    public function updateReply($replyID, $editedReplyText){
        /*
         * This method takes in a replyID, editedReplyText and updates an exising row of the review_replies table.
         * We won't alter the original time of the reply so no need to update the replyTime field
         * If theres an error throw and exception
         */
        try{
            $this->query  = "UPDATE review_replies SET replyText=:replyText WHERE replyID=:replyID";
            $this->statement = $this->pdoConnection->prepare($this->query);
            $this->statement->bindValue(':replyID', $replyID, PDO::PARAM_INT);
            $this->statement->bindValue(':replyText', $editedReplyText, PDO::PARAM_STR);
            $this->statement->execute();
        }catch(PDOException $e){
            //create a log entry to record the error message
            $this->ErrorHandler->createLogEntry("updateReply", $e->getMessage());
            throw new Exception("Error updating reply");
        }
    }

    public function deleteReply($replyID){
        /*
         * This method takes in a replyID (of a review reply comment)and deletes that reply from the review_replies table.
         * If theres an error throw and exception
         */
        try{
            $this->query  = "DELETE FROM review_replies WHERE replyID = :replyID";
            $this->statement = $this->pdoConnection->prepare($this->query);
            $this->statement->bindValue(':replyID', $replyID, PDO::PARAM_INT);		
            $this->statement->execute();
        }catch(PDOException $e){
            //create a log entry to record the error message
            $this->ErrorHandler->createLogEntry("deleteReply", $e->getMessage());
            throw new Exception("Error deleting reply");
        }
    }
}
?>