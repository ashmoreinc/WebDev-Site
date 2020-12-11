<?php

require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/dbconn.php";

class User
{
    private $id, $isCurrentUser; // Key profile data

    // Other profile data
    private $displayImage, $isPrivate, $showFollowing, $showFollowers,
                $bio, $name, $username, $isAdmin;

    function __construct($userID, $isCurrentUser=false){
        $this->id = $userID;
        $this->isCurrentUser = $isCurrentUser;

        $this->retrieveProfileData();
    }

    /**
     * @throws userNotFoundException
     */
    public function retrieveProfileData(){
        // Retrieve the rest of the profile data by querying the database.
        try {
            $conn = getConn();
        } catch (\dbConnNotCreatedException $e) {
            throw new userNotFoundException("Could not connect to the database.", $previous=$e);
        }

        $result = $conn->query("SELECT * FROM users WHERE id=" . $this->id);

        if($result) {
            if($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $this->name = $row["name"];
                $this->username = $row["username"];

                $this->displayImage = $row["displayImageFilename"];
                $this->isPrivate = $row["isPrivate"] > 0; // Sets to true if isPrivate (tinyint) is 1, else 0
                $this->showFollowers = $row["showFollowers"] > 0; // Similar to ^^
                $this->showFollowing = $row["showFollowing"] > 0; // ^^
                $this->isAdmin = $row["isAdmin"];; // ^^
                $this->bio = $row["bio"];
            } else {
                throw new userNotFoundException("No user with id $this->id found.");
            }
        } else {
            throw new userNotFoundException("No results returned from query.");
        }
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return bool
     */
    public function getIsAdmin()
    {
        return $this->isAdmin;
    }

    /**
     * @return bool
     */
    public function getIsCurrentUser()
    {
        return $this->isCurrentUser;
    }

    /**
     * @return string
     */
    public function getDisplayImage()
    {
        return $this->displayImage;
    }

    /**
     * @return bool
     */
    public function getIsPrivate()
    {
        return $this->isPrivate;
    }

    /**
     * @return bool
     */
    public function getShowFollowing()
    {
        return $this->showFollowing;
    }

    /**
     * @return bool
     */
    public function getShowFollowers()
    {
        return $this->showFollowers;
    }

    /**
     * @return string
     */
    public function getBio()
    {
        return $this->bio;
    }
}