window.addEventListener('load', function(event){
    document.getElementsByTagName('form')[0].addEventListener('submit', function(e) {
      var values = document.getElementsByTagName('hax-element-list-selector')[0].getAppstoreValues();
      // in-case they are in this place
      if (values.provider) {
        values.providers = values.provider;
      }
      console.log(values);
      // set location to match CDN
      document.querySelector('input[name="haxtheweb_location"]').value = values.providers.cdn;
      document.querySelector('input[name="haxtheweb_location_other"]').value = values.providers.other;
      document.querySelector('input[name="haxtheweb_pk"]').value = values.providers.pk;
      // store autoloader as blob
      document.querySelector('input[name="haxtheweb_autoload_element_list"]').value = JSON.stringify(values.autoloader, null, 2);
      // these already are blobs for now
      document.querySelector('input[name="haxtheweb_stax"]').value = values.stax;
      document.querySelector('input[name="haxtheweb_blox"]').value = values.blox;
      // loop through key values for popular providers
      for (var key in values.apps) {
        // sanity check per app
        if (document.querySelector('input[name="haxtheweb_' + key + '_key"]')) {
          document.querySelector('input[name="haxtheweb_' + key + '_key"]').value = values.apps[key];
        }
      }
    });
});