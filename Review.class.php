<?php
/*
 * A class for working with user review data of a petrol station
 */
class Review{

    private $DatabaseConnection;
    private $ErrorHandler;
    private $Validator;
    private $Reply; //to hold an instance of the Reply class.
    private $reviewID = null;    //initialize the stationID to null
    private $userID;  
    private $reviewText;    
    private $reviewRating; 
    private $stationID;
    private $reviewTime;    

    public function __construct(){
        $this->DatabaseConnection = DatabaseConnection::getInstance();
        $this->ErrorHandler = new ErrorHandler("Review");
        $this->Validator = new Validator();
        $this->Reply = new Reply();
    }
    public function getReviewUserID($reviewID){
        /*
         * This method takes in a reviewID and gets the userID for that review
         * We will use this method before updating and deleting reviews (in our endpoint methods) 
         * to check that the user performing the action is the creator of the review. 
         */
        $this->reviewID = $reviewID;
        $reviewUserID = $this->DatabaseConnection->getReviewUserID($this->reviewID);
        return $reviewUserID;
    }

    public function getReviews($stationID){
        /*
         * This method takes in a stationID and gets all reviews related to that stationID.
         */
        date_default_timezone_set('Europe/London');
        $this->stationID = $stationID;
        $reviewData = array(); //initialize the $reviewData array which will store the data we return from this method.

        //get the review data from the database. 
        foreach($this->DatabaseConnection->getReviewData($this->stationID) as $key => $value){	
            array_push(
                $reviewData, array(
                    'reviewID' => $value['reviewID'],
                    'stationID' => $value['stationID'],
                    'reviewText' => $this->Validator->cleanUserInput($value['reviewText']),
                    'reviewTime' => date("jS F Y H:i", $value['reviewTime']), 
                    'reviewRating' => $value['reviewRating'],
                    'reviewUserData' => $this->DatabaseConnection->getUserProfile($value['userID']),
                    'replies' => $this->Reply->getReviewReplies($value['reviewID'])
                    )
                ); 
        }
        return $reviewData;
    }

    public function createReview($userID, $reviewText, $reviewRating, $stationID){
        /*
         * This method takes in a userID, reviewText, reviewRating, stationID and calls the createReview method from the DatabaseConnection class.
         * We call this method in the createReview endpoint method.
         */
        $this->userID = $userID;
        $this->reviewText = $reviewText;
        $this->reviewRating = $reviewRating;
        $this->stationID = $stationID;
        $this->reviewID = $this->DatabaseConnection->createReview($this->userID, $this->reviewText, $this->reviewRating, $this->stationID);
        return $this->reviewID;
    }

    public function updateReview($reviewID, $editedReviewText, $newRating){
        /*
         * This method takes in a reviewID, editedReviewText, newRating and calls the updateReview method from the DatabaseConnection class.
         * We call this method in the editReview endpoint method.
         */
        $this->reviewID = $reviewID;
        $this->reviewText = $editedReviewText;
        $this->reviewRating = $newRating;
        $this->DatabaseConnection->updateReview($this->reviewID, $this->reviewText, $this->reviewRating);
    }

    public function deleteReview($reviewID){
        /*
         * This method takes in a reviewID and calls the deleteReview method from the DatabaseConnection class.
         * We call this method in the deleteReview endpoint method.
         */
        $this->reviewID = $reviewID;
        $this->DatabaseConnection->deleteReview($this->reviewID);
    }

}

?>