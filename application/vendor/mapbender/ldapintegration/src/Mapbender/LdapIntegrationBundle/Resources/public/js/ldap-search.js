(function($) {
  /**
   * Rostock ldap search frontend controller
   * 
   */
  if(!window.HRO_LDAP_SEARCH_ENABLED) {
     $(document).on('input', '#inputSearchGroupsAndUsers', function(){
      if(this.value.length > 2) {
        var url = $('#inputLdapSearchResults').attr('data-search-url'); // Fix for not working Mapbender object in backend
        console.log('url', url);
        $('#inputLdapSearchResults').load(url + this.value, function(response, status, xhr) {
          if ( status === "error" ) {
            $('#inputLdapSearchResults').html(xhr.statusText);
          }
        });
      } else {
        $('#inputLdapSearchResults').html('<p>Die LDAP-Suche wird erst nach 3 Buchstaben gestartet...</p>');
      }
    });
    window.HRO_LDAP_SEARCH_ENABLED = true;
  }
})(jQuery);
