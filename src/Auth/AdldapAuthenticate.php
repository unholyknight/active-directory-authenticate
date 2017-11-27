<?php
namespace ActiveDirectoryAuthenticate\Auth;

use Adldap\Adldap;
use Cake\Auth\FormAuthenticate;
use Cake\Controller\ComponentRegistry;
use Cake\Network\Request;
use Cake\Network\Response;

class AdldapAuthenticate extends FormAuthenticate
{
    /**
     * Constructor
     *
     * AdldapAuthenticate uses a configuration array which matches the configuration
     * values from the Adldap2 library. For more specific information on these settings
     * see the Adldap2 documentation: https://github.com/Adldap2/Adldap2
     *
     * @param \Cake\Controller\ComponentRegistry $registry The Component registry
     *   used on this request.
     * @param array $config Array of config to use.
     */
    public function __construct(ComponentRegistry $registry, $config)
    {
        $this->registry = $registry;

        $this->config([
            'config' => [],
            'ignored' => [
                'distinguishedname',
                'dn',
                'objectcategory',
                'objectclass'
            ],
            'select' => null
        ]);
        $this->config($config, null, false);

        $this->ad = new Adldap();
        $this->provider = new \Adldap\Connections\Provider($this->_config['config']);
        $this->ad->addProvider('default', $this->provider);
    }

    /**
     * Clean an array of user attributes
     *
     * @param array $attributes Array of attributes to clean up against the ignored setting.
     * @return array An array of attributes stripped of ignored keys.
     */
    protected function _cleanAttributes($attributes)
    {
        foreach ($attributes as $key => $value) {
            if (is_int($key) || in_array($key, $this->_config['ignored'])) {
                unset($attributes[$key]);
            } else if ($key != 'memberof' && is_array($value) && count($value) == 1) {
                $attributes[$key] = $value[0];
            }
        }

        return $attributes;
    }

    /**
     * Create a friendly, formatted groups array
     *
     * @param array $memberships Array of memberships to create a friendly array for.
     * @return array An array of friendly group names.
     */
    protected function _cleanGroups($memberships)
    {
        $groups = [];
        foreach ($memberships as $group) {
            $parts = explode(',', $group);

            foreach ($parts as $part) {
                if (substr($part, 0, 3) == 'CN=') {
                    $groups[] = substr($part, 3);
                    break;
                }
            }
        }

        return $groups;
    }

    /**
     * Authenticate user
     *
     * @param \Cake\Network\Request $request The request that contains login information.
     * @param \Cake\Network\Response $response Unused response object.
     * @return mixed False on login failure. An array of User data on success.
     */
    public function authenticate(Request $request, Response $response)
    {
        $fields = $this->_config['fields'];
        if (!$this->_checkFields($request, $fields)) {
            return false;
        }
        return $this->findAdUser($request->data[$fields['username']], $request->data[$fields['password']]);
    }

    /**
     * Connect to Active Directory on behalf of a user and return that user's data.
     *
     * @param string $username The username (samaccountname).
     * @param string $password The password.
     * @return mixed False on failure. An array of user data on success.
     */
    public function findAdUser($username, $password)
    {
        try {
            $this->ad->connect('default');
            if ($this->provider->auth()->attempt($username, $password, true)) {
                $search = $this->provider->search();

                if (is_array($this->_config['select'])) {
                    if (!in_array('memberof', $this->_config['select'])) {
                        $this->_config['select'][] = 'memberof';
                    }

                    $search->select($this->_config['select']);
                }

                $user = $search->whereEquals('samaccountname', $username)->first();
                $attributes = $user->getAttributes();

                if (!is_array($this->_config['ignored'])) {
                    $this->_config['ignored'] = [];
                }

                $attributes = $this->_cleanAttributes($attributes);
                $attributes['groups'] = $this->_cleanGroups($attributes['memberof']);

                return $attributes;
            }

            return false;
        } catch (\Adldap\Exceptions\Auth\BindException $e) {
            throw new \RuntimeException('Failed to bind to LDAP server. Check Auth configuration settings.');
        }
    }
}
