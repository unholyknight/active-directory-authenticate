# Active Directory Authentication for [CakePHP 3](https://cakephp.org/)

Extends CakePHP's built-in authentication types to offer form authentication for Active Directory users over LDAP using [Adldap2](https://github.com/Adldap2/Adldap2/).

## Installing With Composer

Install Active Directory Authentication by adding the following to your `composer.json` file:

    "require": {
        "unholyknight/active-directory-authenticate": "master"
    }

Run composer's update command to download the plugin.

## Loading Into A CakePHP Application

After installing with Composer, load the plugin:

```php
// add to config/bootstrap.php
Plugin::load('ActiveDirectoryAuthenticate');
```

## Adding Active Directory Authentication

Authentication is handled similarly to Cake's native **FormAuthenticate**. This includes FormAuthenticate's core configuration options as well as some expanded options for Active Directory connectivity and queried data.

To add the Active Directory authentication component to your application open your **src/Controller/AppController.php** file and add the following lines in the **initialize()** function. Extended options are further described below. For more information on CakePHP's core FormAuthenticate and associated options see the [Cookbook's authentication example](http://book.cakephp.org/3.0/en/tutorials-and-examples/blog-auth-example/auth.html).

```php
public function initialize()
{
    //...

    $this->loadComponent('Auth', [
        'authenticate' => [
            'ActiveDirectoryAuthenticate.Adldap' => [
                'config' => [
                    'account_suffix' => '@corp.acme.org',
                    'base_dn' => 'dc=corp,dc=acme,dc=org',
                    'domain_controllers' => ['ACME-DC01.corp.acme.org']
                ]
            ]
        ]
    ]);

    //...
}
```

## Configuration Options

### 'config'

The config key must contain an array which describes your environment so that a connection can be made. In many cases this only needs to include the account suffix, domain controllers and base dn.

```php
'config' => [
    'account_suffix' => '@corp.acme.org',
    'base_dn' => 'dc=corp,dc=acme,dc=org',
    'domain_controllers' => ['ACME-DC01.corp.acme.org']
]
```

Expanded options include support for ssl, tls and non-standard ports. See the full list of available options on [Adldap2's configuration docs](https://github.com/Adldap2/Adldap2/blob/master/docs/configuration.md).

### 'select'

The select key can either be an array of attributes to return or null (defaults to null). These attributes will depend on the LDAP attributes available from your Active Directory environment.

If set to null then all available information will be returned for the user.

```php
'select' => [
    'displayName',
    'samaccountname',
    'telephonenumber',
    'mail'
]
```

[Microsoft's Active Directory Schema Documentation](https://msdn.microsoft.com/en-us/library/ms675090(v=vs.85).aspx) is a good resource for referencing available LDAP attributes, but these may vary depending on your Active Directory environment.

### 'ignored'

The ignored key is an array of keys for which you do not want data returned. By default the ignored array contains 'distinguishedname', 'dn', 'objectcategory' and 'objectclass' in order to clean up the data that is returned to the Auth component. Set ignored to null or a blank array if you would like to retrieve all keys.

```php
'ignored' => [
    'distinguishedname',
    'dn',
    'objectcategory',
    'objectclass'
]
```

## Group Membership Handling

The authenticated user's groups are always retrieved and returned in the memberof and groups keys.

memberof contains an array of the user's Active Directory groups in their original format.

```php
'memberof' => [
    0 => 'CN=Admins,OU=Applications,OU=Groups,DC=acme,DC=org',
    1 => 'CN=WordPress Editors,OU=Applications,OU=Groups,DC=acme,DC=org',
    2 => 'CN=Google Apps Users,OU=Applications,OU=Groups,DC=acme,DC=org',
    3 => 'CN=Members,OU=Security,OU=Groups,DC=acme,DC=org'
]
```

groups contains an array of the user's Active Directory groups by name only. You may find this useful when defining granular access to controllers and actions.

```php
'groups' => [
    0 => 'Admins',
    1 => 'WordPress Editors',
    2 => 'Google Apps Users',
    3 => 'Members'
]
```

## Testing

Tests are not yet written for this plugin. Once they are available, version 1.0 will be tagged and released.
