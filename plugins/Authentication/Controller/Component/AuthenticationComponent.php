<?php

App::uses('AuthenticationUser', 'Authentication.Model');

class AuthenticationComponent extends Component {

    public function __construct(\ComponentCollection $collection, $settings = array()) {
        parent::__construct($collection, $settings + array(
            'emailField' => 'email',
            'usernameField' => 'username',
            'passwordField' => 'password',
            'activeField' => 'active',
            'userModel' => null,
            'loginRedirect' => null,
        ));
        AuthenticationUser::configure($this->settings);
    }

    public $components = array(
        'Auth' => array(
            'loginAction' => array(
                'controller' => 'authentication',
                'action' => 'login',
                'plugin' => 'authentication'
            )
        )
    );

    public function initialize(\Controller $controller) {
        parent::initialize($controller);
        if (!$controller->Components->loaded('Auth')) {
            $controller->Auth = $controller->Components->load('Auth');
            $controller->Auth->initialize($controller);
        }
        $controller->Auth->authenticate = array(
            'all' => array(
                'userModel' => $this->settings['userModel'],
                'fields' => array(
                    'username' => $this->settings['usernameField']
                    , 'password' => $this->settings['passwordField']
                ),
            ),
            'Form'
        );
        $controller->Auth->authorize = array('Controller');
        $controller->Auth->loginAction = array(
            'controller' => 'authentication',
            'action' => 'login',
            'plugin' => 'authentication'
        );
        $controller->Auth->loginRedirect = $this->settings['loginRedirect'];
        $controller->Auth->allow();
        $controller->Auth->allow('index', 'add', 'edit', 'delete', 'view');
    }

    public function sendSelfUserCreationNotification($userId, $password) {
        App::uses('CakeEmail', 'Network/Email');

        $model = ClassRegistry::init('Authentication.AuthenticationUser');
        $user = $model->findById($userId);
        $user = $user[$model->alias];

        $email = new CakeEmail('default');
        $email->to($user['email']);
        $email->subject(__('Your account was created.', true));
        $email->send(
                __('Usernamme') . ': ' . $user['username']
                . "\n" . __('Password') . ': ' . $password);
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
