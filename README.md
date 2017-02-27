SilverStripe Auth0 module
==================

Integrates [Auth0](https://auth0.com/) into your login form. Currently, it only
supports social connections. Login is done through a popup.

Database/passwordless authentication is not supported as of now.

Configuration using Constants
==================

Define the following constants in your _ss_environment.php:

    define('AUTH0_DOMAIN','');
    define('AUTH0_CLIENT_ID','');
    define('AUTH0_CLIENT_SECRET','');
    define('AUTH0_DOMAIN','');

Configuration using SiteConfig
==================

Apply the following extension:

    SiteConfig:
      extensions:
        - Auth0SiteConfigExtension

Compatibility
==================
Tested with 3.5+

Maintainer
==================
LeKoala - thomas@lekoala.be