<?php

/**
 * Apply this extension to allow configuration from the SiteConfig
 *
 * @author Kalyptus SPRL <thomas@kalyptus.be>
 */
class Auth0SiteConfigExtension extends DataExtension
{

    private static $db = array(
        'Auth0Domain' => "Varchar(191)",
        'Auth0ClientId' => "Varchar(191)",
        'Auth0ClientSecret' => "Varchar(191)",
        'Auth0Connections' => "Varchar(191)",
        'Auth0Audience' => "Varchar(191)",
    );

    public function updateCMSFields(\FieldList $fields)
    {
        $fields->addFieldToTab('Root.Auth0', new TextField('Auth0Domain', 'Domain'));
        $fields->addFieldToTab('Root.Auth0', new TextField('Auth0ClientId', 'Client Id'));
        $fields->addFieldToTab('Root.Auth0', new TextField('Auth0ClientSecret', 'Client Secret'));
        $fields->addFieldToTab('Root.Auth0', $Auth0Audience = new TextField('Auth0Audience', 'Audience'));
        $Auth0Audience - setAttribute('placeholder', 'https://domain/userinfo');
        $fields->addFieldToTab('Root.Auth0', $Auth0Connections = new TextField('Auth0Connections', 'Connections'));
        $Auth0Connections->setAttribute('placeholder', 'google-oauth2,facebook,...');
        $Auth0Connections->setDescription("Comma separated list of available connections");
    }

    /**
     * Retrieve an array with all required variables for Auth0 setup
     *
     * Data comes from constants or the SiteConfig
     * 
     * @return array
     */
    public static function getAuth0Data()
    {
        $sc = SiteConfig::current_site_config();

        if (defined('AUTH0_DOMAIN')) {
            $domain = AUTH0_DOMAIN;
        } else {
            $domain = $sc->Auth0Domain;
        }
        if (defined('AUTH0_CLIENT_ID')) {
            $client_id = AUTH0_CLIENT_ID;
        } else {
            $client_id = $sc->Auth0ClientId;
        }
        if (defined('AUTH0_CLIENT_SECRET')) {
            $client_secret = AUTH0_CLIENT_SECRET;
        } else {
            $client_secret = $sc->Auth0ClientSecret;
        }
        if (defined('AUTH0_CONNECTIONS')) {
            $connections = AUTH0_CONNECTIONS;
        } else {
            $connections = $sc->Auth0Connections;
        }
        if (defined('AUTH0_AUDIENCE')) {
            $audience = AUTH0_AUDIENCE;
        } else {
            $audience = $sc->Auth0Audience;
        }

        return [
            'domain' => $domain,
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'audience' => $audience,
            'redirect_uri' => Auth0SecurityExtension::Auth0CallbackUrl(),
            'connections' => $connections,
            'debug' => Director::isDev() ? true : false,
        ];
    }
}
