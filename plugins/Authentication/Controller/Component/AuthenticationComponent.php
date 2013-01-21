<?php

//App::import('Component', '.BaseModel');

class AuthenticationComponent extends Component {
    
    private $emailField = 'email';
    private $userModel = null;
    
    public $components = array('Auth');

    public function initialize(Controller $controller) {
        
    }

    public function sendSelfUserCreationNotification($userId, $password) {
        App::uses('CakeEmail', 'Network/Email');        
        
        $user = $this->userModel()->findById($userId);
        $user = $user[$this->userModel()->alias];

        $email = new CakeEmail('default');                
        $email->to($user[$this->emailField]);
        $email->subject(__('Your account was created.',true));        
        $email->send("Username: {$user['email']}\nPassword: $password");
    }
    
    private function userModel() {
        if (empty($this->userModel)) {
            $this->userModel = ClassRegistry::init($this->Auth->userModel);
        }
        return $this->userModel;
    }

    /**
     * Adaptado de http://www.laughing-buddha.net/php/lib/password
     * @return string
     */
    public static function generateRandomPassword($length = 8) {

        // start with a blank password
        $password = "";

        // define possible characters - any character in this string can be
        // picked for use in the password, so if you want to put vowels back in
        // or add special characters such as exclamation marks, this is where
        // you should do it
        $possible = "2346789bcdfghjkmnpqrtvwxyzBCDFGHJKLMNPQRTVWXYZ";

        // we refer to the length of $possible a few times, so let's grab it now
        $maxlength = strlen($possible);

        // check for length overflow and truncate if necessary
        if ($length > $maxlength) {
            $length = $maxlength;
        }

        // set up a counter for how many characters are in the password so far
        $i = 0;

        // add random characters to $password until $length is reached
        while ($i < $length) {

            // pick a random character from the possible ones
            $char = substr($possible, mt_rand(0, $maxlength - 1), 1);

            // have we already used this character in $password?
            if (!strstr($password, $char)) {
                // no, so it's OK to add it onto the end of whatever we've already got...
                $password .= $char;
                // ... and increase the counter by one
                $i++;
            }
        }

        // done!
        return $password;
    }

}

?>
