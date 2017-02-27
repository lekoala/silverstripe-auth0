<?php

/**
 * Auth0LoginForm
 *
 * @author Kalyptus SPRL <thomas@kalyptus.be>
 */
class Auth0LoginForm extends MemberLoginForm
{

    public function __construct($controller, $name, $fields = null, $actions = null, $checkCurrentUser = true)
    {
        parent::__construct($controller, $name, $fields, $actions, $checkCurrentUser);

        $data = Auth0SiteConfigExtension::getAuth0Data();

        $connections = explode(',', $data['connections']);
        if (!empty($connections)) {
            $result = $this->Auth0JsRequirements();

            $fields = $this->Fields();

            $holder = new CompositeField();
            $holder->addExtraClass('auth0-holder auth0-' . count($connections));
            $fields->insertBefore('Email', $holder);

            $i = 0;
            foreach ($connections as $connection) {
                $i++;
                $service = str_replace('-oauth2', '', $connection);
                $label = ucwords($service);
                $holder->push(new LiteralField('Auth0' . $i, '<button class="auth0-popup ' . $connection . '" data-connection="' . $connection . '">' . $label . '</button>'));
            }

            $holder->push(new LiteralField('Auth0Clearfix', '<div class="auth0-clear"></div>'));
        }
    }

    protected function Auth0JsRequirements()
    {
        $data = Auth0SiteConfigExtension::getAuth0Data();

        if (empty($data['domain'])) {
            return false;
        }

        $auth0_domain = $data['domain'];
        $auth0_client_id = $data['client_id'];
        $auth0_callback = $data['redirect_uri'];

        // Please include jQuery in the init method of your controller
        Requirements::javascript("https://cdn.auth0.com/w2/auth0-7.1.min.js");
        Requirements::customScript(<<<JS
var auth0 = new Auth0({
  domain:         '$auth0_domain',
  clientID:       '$auth0_client_id',
  callbackURL:    '$auth0_callback'
});
jQuery(function() {
  jQuery('.auth0-popup').on('click',function(e) {
       e.preventDefault();
       auth0.signin({popup: true, connection: jQuery(this).data('connection')}, function(err, result) {
            if (err) {
              console.log("something went wrong: " + err.message);
              return;
            }
          });
  });
});
JS
        );

        return true;
    }
}
