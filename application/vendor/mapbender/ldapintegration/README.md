# LDAP Integration Bundle for Mapbender


## How to install

1. Add bundle as entry to `composer.json` and after that install with from console with `composer install`

  ```json
  {
    "require": {
      "mapbender/ldapintegration": "*"
    },
    "repositories": [
      {"type": "git","url": "https://github.com/mapbender/ldapIntegrationBundle.git"}
    ]
  }
  
  ```

2. Add to `AppKernel.php`

  ```php
  //...
  $bundles = array(
    //...
    new IMAG\LdapBundle\IMAGLdapBundle(),
    new Mapbender\LdapIntegrationBundle\LdapIntegrationBundle(),
    //...
  //...
  
  ```
3. Add to `routing.yml`
  ```yml
  mapbender_ldapintegration:
    resource: "@MapbenderLdapIntegrationBundle/Controller/"
    type: annotation
  ```
4. Add to `parameters.yml`
  ```yml
  parameters:
    ldap_host: # Ldap-server hostname
    ldap_port: 389 # Ldap-server port 
    ldap_version: 3 # Ldap-server version 
    ldap_user_base_dn: ou=users # distinguished name where users are stored
    ldap_user_name_attribute: uid # attribute that determinante the username (login-name)
    ldap_role_base_dn: ou=groups # distinguished name where to get user roles
    ldap_role_name_attribute: cn # group name to use (Automated prefixed with "ROLE_" and slugified)
    ldap_role_user_attribute: memberUid # Attribute to check if user is in group
    ldap_role_user_id: username # How to determinante user in ldap_role_user_attribute. With username OR dn (distinguished name)!
    ldap_bind_dn: # distinguished name for prebind if ldap only allow access for binded request
    ldap_bind_pwd:  # password for prebinded user by distinguished name if ldap only allow access for binded request
    ldap_user_search_filter:                # Example: (ObjectClass=posixAccount) # if you want to filter users from ldap
  ```
  
5. Configure `security.yml`
  1. Add plaintext encoder for LdapUserEntity (you can use the default `Mapbender\LdapIntegrationBundle\Entity\LdapUser` or create your own)
    ```yml
    security:
      encoders:
        Mapbender\LdapIntegrationBundle\Entity\LdapUser: plaintext
    ```
  2. Configure provider and chain-provider (in this example we use mapbender user-auth at first and ldap as second in chain)
    ```yml
    security:
      providers:
        main:
          entity:
            class: FOM\UserBundle\Entity\User
            property: username
        ldap:
          id: imag_ldap.security.user.provider
        chain_provider:
            chain:
                providers: ["main", "ldap"]
    ```
  3. Pipe imag_ldap settings from `parameters.yml` and add `user_class` like configured in step 5.1.
    ```yml
    imag_ldap:
        client:
            host: %ldap_host%
            port: %ldap_port%
            version: %ldap_version% # Optional
            username: %ldap_bind_dn% # Optional
            password: %ldap_bind_pwd% # Optional
    #        network_timeout: 10 # Optional
    #        referrals_enabled: true # Optional
    #        bind_username_before: true # Optional
    #        skip_roles: true # Optional

        user:
            base_dn: %ldap_user_base_dn%
            filter: %ldap_user_search_filter% #Optional
            name_attribute:  %ldap_user_name_attribute%

        role:
            base_dn: %ldap_role_base_dn%
    #        filter: (ou=group) #Optional
            name_attribute:  %ldap_role_name_attribute%
            user_attribute: %ldap_role_user_attribute%
            user_id: %ldap_role_user_id%

        user_class: Mapbender\LdapIntegrationBundle\Entity\LdapUser
    ```
    
## Notice

At the moment you has to set `"minimum-stability": "dev"` in your project `composer.json`!