(function (Drupal) {
  Drupal.behaviors.WebcomponentsAutoloadBehavior = {
    attach: function (context, settings) {
      if (window.WCAutoload && window.WCAutoload.process) {
        window.WCAutoload.process();
      }
    }
  };
})(Drupal);