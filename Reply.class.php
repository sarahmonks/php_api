<?php
/*
 * A class for working with reply data of reviews of a petrol station
 */
class Reply{

	private $DatabaseConnection;
	private $ErrorHandler;
	private $Validator;

    private $replyID = null; 
    private $reviewID;   
    private $userID;  
    private $replyText;    
    private $replyTime;    

	public function __construct(){
		$this->DatabaseConnection = DatabaseConnection::getInstance();
		$this->ErrorHandler = new ErrorHandler("Reply");
	    $this->Validator = new Validator();
	}

	public function getReviewReplies($reviewID){
		/*
		 * This method takes in a reviewID and gets all replies related to that reviewID.
		 */

        $this->reviewID = $reviewID;
        $replyData = array(); //initialize the $replyData array which will store the data we return from this method.
        //get the reply data from the database. 
        foreach($this->DatabaseConnection->getReviewReplyData($this->reviewID) as $key => $value){	
            array_push(
            	$replyData, array(
            		'replyID' => $value['replyID'],
                    'reviewID' => $value['reviewID'],
					'replyText' => $this->Validator->cleanUserInput($value['replyText']),
		            'replyTime' => date("jS F Y H:i", $value['replyTime']), 
		            'replyUserData' => $this->DatabaseConnection->getUserProfile($value['userID'])
		            )
            	); 
        }
	    return $replyData;
	}

    public function getReplyUserID($replyID){
        /*
         * This method takes in a replyID and gets the userID for that reply (of a review)
         * We will use this method before updating and deleting replies (in our endpoint methods) 
         * to check that the user performing the action is the creator of the reply. 
         */
        $this->replyID = $replyID;
        $this->userID = $this->DatabaseConnection->getReplyUserID($this->replyID);
	    return $this->userID;
    }


    public function createReply($userID, $replyText, $reviewID){
        /*
		 * This method takes in a userID, replyText and a reviewID and calls the createReply method from the DatabaseConnection class.
		 */
        $this->userID = $userID;
        $this->replyText = $replyText;
        $this->reviewID = $reviewID;
        $this->replyID = $this->DatabaseConnection->createReply($this->userID, $this->replyText, $this->reviewID);
        return $this->replyID;
    }

    public function updateReply($replyID, $editedReplyText){
        /*
         * This method takes in a replyID and editedReplyText and calls the updateReply method from the DatabaseConnection class.
         * We call this method in the editReply endpoint method.
         */
        $this->replyID = $replyID;
        $this->replyText = $editedReplyText;
        $this->DatabaseConnection->updateReply($this->replyID, $this->replyText);
    }

	public function deleteReply($replyID){
        /*
		 * This method takes in a replyID and calls the deleteReply method from the DatabaseConnection class.
		 */
        $this->replyID = $replyID;
        $this->DatabaseConnection->deleteReply($this->replyID);
	}

}

?>