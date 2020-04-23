(function (Drupal) {
  document.getElementById('hax-settings').addEventListener('submit', function(e) {
    var values = document.getElementsByTagName('hax-element-list-selector')[0].getAppstoreValues();
    // set location to match CDN
    document.getElementById('edit-hax-project-location').value = values.provider.cdn;
    document.getElementById('edit-hax-project-location-other').value = values.provider.other;
    document.getElementById('edit-hax-project-pk').value = values.provider.pk;
    // store autoloader as blob
    document.getElementById('edit-hax-autoload-element-list').value = JSON.stringify(values.autoloader, null, 2);
    // these already are blobs for now
    document.getElementById('edit-hax-stax').value = values.stax;
    document.getElementById('edit-hax-blox').value = values.blox;
    // loop through key values for popular providers
    for (var key in values.apps) {
      if (document.getElementById('edit-hax-' + key + '-key')) {
        document.getElementById('edit-hax-' + key + '-key').value = values.apps[key];
      }
    }
  });
})(Drupal);