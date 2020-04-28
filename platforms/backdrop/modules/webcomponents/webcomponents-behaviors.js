/**
 * @file
 * Ensure Backdrop updates reprocess all tags correctly
 */
(function ($) {
    $(document).ready(function(){
      if (window.WCAutoload && window.WCAutoload.process) {
        window.WCAutoload.process();
      }
    });
    Backdrop.behaviors.WebcomponentsAutoload = {
      attach: function(context) {
        if (window.WCAutoload && window.WCAutoload.process) {
          window.WCAutoload.process();
        }
      }
    };
  })(jQuery);