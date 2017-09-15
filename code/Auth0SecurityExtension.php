<?php

use Auth0\SDK\Auth0;

/**
 * Auth0SecurityExtension
 *
 * @author Kalyptus SPRL <thomas@kalyptus.be>
 */
class Auth0SecurityExtension extends Extension
{

    private static $allowed_actions = array(
        'auth0'
    );

    public function auth0()
    {
        if (!Controller::has_curr()) {
            return $this->closePopupScript();
        }
        $ctrl = Controller::curr();

        $client = $this->getClient();

        $user = $client->getUser();
        if (!$user) {
            $this->owner->extend('onAuth0Failed', $user);
            return $this->closePopupScript();
        }

        d($user);

        //@link https://auth0.com/docs/user-profile
        $email = isset($user['email']) ? $user['email'] : null;
        $email_verified = isset($user['email_verified']) ? $user['email_verified'] : null;
        $name = isset($user['name']) ? $user['name'] : null;
        $given_name = isset($user['given_name']) ? $user['given_name'] : null;
        $family_name = isset($user['family_name']) ? $user['family_name'] : null;
        $gender = isset($user['gender']) ? $user['gender'] : null;
        $locale = isset($user['locale']) ? $user['locale'] : null;
        $nickname = isset($user['nickname']) ? $user['nickname'] : null;
        $avatar = isset($user['picture_large']) ? $user['picture_large'] : null;
        if (!$avatar) {
            $avatar = isset($user['picture']) ? $user['picture'] : null;
        }
        $socialId = isset($user['third_party_id']) ? $user['third_party_id'] : null;

        $filters = [];
        if ($email) {
            $filters['Email'] = $email;
        }
        if ($socialId) {
            $filters['SocialId'] = $socialId;
        }


        if (empty($filters)) {
            SS_Log::log("No filters for user " . json_encode($user), SS_Log::DEBUG);
            $this->owner->extend('onAuth0Failed', $user);
            return $this->closePopupScript();
        }

        /* @var $member Member */
        $member = Member::get()->filterAny($filters)->first();

        // Email may not be shared and we need it to create a member. Store data for a prefilled register form
        if (!$email && !$member) {
            Session::set('RegisterForm.Data', [
                'FromAuth0' => true,
                'SocialId' => $socialId,
                'FirstName' => $given_name,
                'Surname' => $family_name,
                'UserData' => $user,
            ]);
            return $this->closePopupScript();
        }

        if ($member) {
            // If the member exist, do not overwrite their data unless specified
            if ($member->hasMethod('updateAuth0')) {
                $member->updateAuth0($user);
            }
            $member->logIn();
            return $this->closePopupScript();
        }

        $member = Member::create();
        $member->Email = $email;
        $member->EmailVerified = $email_verified;
        $member->FirstName = $given_name;
        $member->Surname = $family_name;
        if ($nickname != $email) {
            $member->Nickname = $nickname;
        }

        switch ($gender) {
            case 'male':
                $member->Gender = 'Male';
                break;
            case 'female':
                $member->Gender = 'Female';
                break;
        }

        $member->Locale = i18n::get_locale_from_lang($locale);
        if ($member->hasMethod('fillAuth0')) {
            $member->fillAuth0($user);
        }
        $member->write();

        // Store image
        if ($member->hasField('AvatarID')) {
            $image = self::storeRemoteImage(@file_get_contents($avatar), 'Avatar' . $member->ID, 'Avatars');
            if ($image) {
                $member->AvatarID = $image->ID;
                $member->write();
            }
        }

        $member->logIn();

        return $this->closePopupScript();
    }

    /**
     * Store an image
     *
     * @param string $data
     * @param string $name
     * @param string $folder
     * @return Image
     */
    public static function storeRemoteImage($data, $name, $folder)
    {
        if (!$data) {
            return;
        }

        $filter = new FileNameFilter;
        $name = $filter->filter($name);

        $folderName = self::$folder . '/' . $folder;
        $folderPath = BASE_PATH . '/assets/' . $folderName;
        $filename = $folderPath . '/' . $name;
        $folderInst = Folder::find_or_make($folderName);
        file_put_contents($filename, $data);
        $folderInst->syncChildren();

        return Image::find($folderName . '/' . $name);
    }

    protected function closePopupScript()
    {
        return '<script>
    window.onunload = refreshParent;
    function refreshParent() {
        window.opener.location.reload();
    }
    self.close();
</script>
</html>';
    }

    /**
     * This is the url that will be called upon signup or login
     * Please make sure it has been whitelisted (this precise url)
     * 
     * @return string
     */
    public static function Auth0CallbackUrl()
    {
        return Director::absoluteURL('/Security/auth0');
    }

    /**
     * Get auth0 client
     *
     * @return \Auth0\SDK\API\Oauth2Client
     */
    public function getClient()
    {
        $data = Auth0SiteConfigExtension::getAuth0Data();

        if (empty($data['domain'])) {
            return;
        }

        // @link http://docs.guzzlephp.org/en/stable/request-options.html
        $guzzleOpts = [];

        if (strlen(ini_get('curl.cainfo')) === 0) {
            // Set to a string to specify the path to a file containing a PEM formatted client side certificate. 
//            $guzzleOpts['cert'] = \Composer\CaBundle\CaBundle::getBundledCaBundlePath();
            $guzzleOpts['verify'] = false;
        }

        $params = [
            'domain' => $data['domain'],
            'client_id' => $data['client_id'],
            'client_secret' => $data['client_secret'],
            'redirect_uri' => $data['redirect_uri'],
            'debug' => $data['debug'],
            'store' => false,
//            'persist_id_token' => true,
//            'persist_access_token' => true,
//            'persist_refresh_token' => true,
            'guzzle_options' => $guzzleOpts,
        ];

        if (isset($data['audience'])) {
            $params['audience'] = $data['audience'];
        }

        $auth0 = new Auth0($params);

        if (!empty($data['debug'])) {
            $auth0->setDebugger(function($message) {
                SS_Log::log($message, SS_Log::DEBUG);
            });
        }

        return $auth0;
    }
}
