(function ($) {
	$(document).ready(function () {
    // HAX the Press
    setTimeout(function(){
      var haxThePress = document.createElement('wysiwyg-hax');
      var temp = document.createElement('template');
      // target the TinyMCE area
      const content = document.querySelector('textarea#content');
      // replicate what it was providing to the light DOM
      haxThePress.fieldClass = 'wp-editor-area';
      haxThePress.fieldName = 'content';
      haxThePress.fieldId = 'content';
      haxThePress.openDefault = true;
      haxThePress.elementAlign = 'left';
      haxThePress.saveButtonSelector = document.querySelector('input[type="submit"]#publish');
      if (window.haxThePressConnector) {
        haxThePress.appStoreConnection = window.haxThePressConnector;
      }
      // set the template tag to the content previously in the textarea
      temp.innerHTML = content.value;
      // append the template tag into the wysiwyg
      haxThePress.appendChild(temp.cloneNode(true));
      // insert the field exactly where the tinymce was
      content.parentNode.insertBefore(haxThePress, content);
      // HAX the web
      content.parentNode.removeChild(content);
    }, 500);
  });
})(jQuery);