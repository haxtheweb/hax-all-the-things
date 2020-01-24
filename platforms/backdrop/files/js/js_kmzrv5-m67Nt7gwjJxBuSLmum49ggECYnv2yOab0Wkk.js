/**
 * @file layout.admin.js
 *
 * Behaviors for editing a layout.
 */

(function ($) {

"use strict";

/**
 * Behavior for showing a list of layouts.
 *
 * Detect flexbox support for displaying our list of layouts with vertical
 * height matching for each row of layout template icons.
 */
Backdrop.behaviors.gridFloatFallback = {
  attach: function() {
    var $body = $('body');
    if (!$body.hasClass('grid-float-fallback-processed') && !Backdrop.featureDetect.flexbox()) {
      $('head').append('<link rel="stylesheet" type="text/css" href="/core/modules/layout/css/grid-float.css">');
      $body.addClass('grid-float-fallback-processed');
    }
  }
};

})(jQuery);
;
(function ($) {

"use strict";

/**
 * Process elements with the .dropbutton class on page load.
 */
Backdrop.behaviors.dropButton = {
  attach: function (context, settings) {
    var $dropbuttons = $(context).find('.dropbutton-wrapper').once('dropbutton');
    if ($dropbuttons.length) {
      // Adds the delegated handler that will toggle dropdowns on click.
      var $body = $('body').once('dropbutton-click');
      if ($body.length) {
        $body.on('click', '.dropbutton-toggle', dropbuttonClickHandler);
      }
      // Initialize all buttons.
      for (var i = 0, il = $dropbuttons.length; i < il; i++) {
        DropButton.dropbuttons.push(new DropButton($dropbuttons[i], settings.dropbutton));
      }
    }
  }
};

/**
 * Delegated callback for opening and closing dropbutton secondary actions.
 */
function dropbuttonClickHandler (e) {
  e.preventDefault();
  $(e.target).closest('.dropbutton-wrapper').toggleClass('open');
}

/**
 * A DropButton presents an HTML list as a button with a primary action.
 *
 * All secondary actions beyond the first in the list are presented in a
 * dropdown list accessible through a toggle arrow associated with the button.
 *
 * @param DOMElement dropbutton
 *   A DOM element.
 *
 * @param {Object} settings
 *   A list of options including:
 *    - {String} title: The text inside the toggle link element. This text is
 *      hidden from visual UAs.
 */
function DropButton (dropbutton, settings) {
  // Merge defaults with settings.
  var options = $.extend({'title': Backdrop.t('List additional actions')}, settings);
  var $dropbutton = $(dropbutton);
  this.$dropbutton = $dropbutton;
  this.$list = $dropbutton.find('.dropbutton');
  // Find actions and mark them.
  this.$actions = this.$list.find('li').addClass('dropbutton-action');

  // Add the special dropdown only if there are hidden actions.
  if (this.$actions.length > 1) {
    // Identify the first element of the collection.
    var $primary = this.$actions.slice(0,1);
    // Identify the secondary actions.
    var $secondary = this.$actions.slice(1);
    $secondary.addClass('secondary-action');
    // Add toggle link.
    $primary.after(Backdrop.theme('dropbuttonToggle', options));
    // Bind mouse events.
    this.$dropbutton
      .addClass('dropbutton-multiple')
      .on({
        /**
         * Adds a timeout to close the dropdown on mouseleave.
         */
        'mouseleave.dropbutton': $.proxy(this.hoverOut, this),
        /**
         * Clears timeout when mouseout of the dropdown.
         */
        'mouseenter.dropbutton': $.proxy(this.hoverIn, this),
        /**
         * Similar to mouseleave/mouseenter, but for keyboard navigation.
         */
        'focusout.dropbutton': $.proxy(this.focusOut, this),
        'focusin.dropbutton': $.proxy(this.focusIn, this)
      });
  }
  else {
    this.$dropbutton.addClass('dropbutton-single');
  }
}

/**
 * Extend the DropButton constructor.
 */
$.extend(DropButton, {
  /**
   * Store all processed DropButtons.
   *
   * @type {Array}
   */
  dropbuttons: []
});

/**
 * Extend the DropButton prototype.
 */
$.extend(DropButton.prototype, {
  /**
   * Toggle the dropbutton open and closed.
   *
   * @param {Boolean} show
   *   (optional) Force the dropbutton to open by passing true or to close by
   *   passing false.
   */
  toggle: function (show) {
    var isBool = typeof show === 'boolean';
    show = isBool ? show : !this.$dropbutton.hasClass('open');
    this.$dropbutton.toggleClass('open', show);
  },

  hoverIn: function () {
    // Clear any previous timer we were using.
    if (this.timerID) {
      window.clearTimeout(this.timerID);
    }
  },

  hoverOut: function () {
    // Wait half a second before closing.
    this.timerID = window.setTimeout($.proxy(this, 'close'), 500);
  },

  open: function () {
    this.toggle(true);
  },

  close: function () {
    this.toggle(false);
  },

  focusOut: function(e) {
    this.hoverOut.call(this, e);
  },

  focusIn: function(e) {
    this.hoverIn.call(this, e);
  }
});

/**
 * A toggle is an interactive element often bound to a click handler.
 *
 * @param {Object} options
 *   - {String} title: (optional) The HTML anchor title attribute and
 *     text for the inner span element.
 *
 * @return {String}
 *   A string representing a DOM fragment.
 */
Backdrop.theme.prototype.dropbuttonToggle = function (options) {
  return '<li class="dropbutton-toggle"><button type="button" role="button"><span class="dropbutton-arrow"><span class="visually-hidden">' + options.title + '</span></span></button></li>';
};

// Expose constructor in the public space.
Backdrop.DropButton = DropButton;

})(jQuery);
;
(function ($) {

"use strict";

/**
 * Attach the tableResponsive function to Backdrop.behaviors.
 */
Backdrop.behaviors.tableResponsive = {
  attach: function (context, settings) {
    var $tables = $(context).find('table.responsive-enabled').once('tableresponsive');
    if ($tables.length) {
      for (var i = 0, il = $tables.length; i < il; i++) {
        TableResponsive.tables.push(new TableResponsive($tables[i]));
      }
    }
  }
};

/**
 * The TableResponsive object optimizes table presentation for all screen sizes.
 *
 * A responsive table hides columns at small screen sizes, leaving the most
 * important columns visible to the end user. Users should not be prevented from
 * accessing all columns, however. This class adds a toggle to a table with
 * hidden columns that exposes the columns. Exposing the columns will likely
 * break layouts, but it provides the user with a means to access data, which
 * is a guiding principle of responsive design.
 */
function TableResponsive (table) {
  this.table = table;
  this.$table = $(table);
  this.showText = Backdrop.t('Show all columns');
  this.hideText = Backdrop.t('Hide less important columns');
  // Store a reference to the header elements of the table so that the DOM is
  // traversed only once to find them.
  this.$headers = this.$table.find('th');
  // Add a link before the table for users to show or hide weight columns.
  this.$link = $('<a href="#" class="tableresponsive-toggle"></a>')
    .attr({
      'title': Backdrop.t('Show table cells that were hidden to make the table fit within a small screen.')
    })
    .on('click', $.proxy(this, 'eventhandlerToggleColumns'));

  this.$table.before($('<div class="tableresponsive-toggle-columns"></div>').append(this.$link));

  var _this = this;
  Backdrop.optimizedResize.add(function() {
    $.proxy(_this, 'eventhandlerEvaluateColumnVisibility');
    $(window).trigger('resize.tableresponsive');
  });
}

/**
 * Extend the TableResponsive function with a list of managed tables.
 */
$.extend(TableResponsive, {
  tables: []
});

/**
 * Associates an action link with the table that will show hidden columns.
 *
 * Columns are assumed to be hidden if their header has the class priority-low
 * or priority-medium.
 */
$.extend(TableResponsive.prototype, {
  eventhandlerEvaluateColumnVisibility: function (e) {
    var pegged = parseInt(this.$link.data('pegged'), 10);
    var hiddenLength = this.$headers.filter('.priority-medium:hidden, .priority-low:hidden').length;

    // If the table is not at all visible, do not manipulate the link.
    var tableVisible = this.$table.is(':visible');
    if (!tableVisible) {
      return;
    }

    // If the table has hidden columns, associate an action link with the table
    // to show the columns.
    if (hiddenLength > 0) {
      this.$link.show().text(this.showText);
    }
    // When the toggle is pegged, its presence is maintained because the user
    // has interacted with it. This is necessary to keep the link visible if the
    // user adjusts screen size and changes the visibilty of columns.
    if (!pegged && hiddenLength === 0) {
      this.$link.hide().text(this.hideText);
    }
  },
  // Toggle the visibility of columns classed with either 'priority-low' or
  // 'priority-medium'.
  eventhandlerToggleColumns: function (e) {
    e.preventDefault();
    var self = this;
    var $hiddenHeaders = this.$headers.filter('.priority-medium:hidden, .priority-low:hidden');
    this.$revealedCells = this.$revealedCells || $();
    // Reveal hidden columns.
    if ($hiddenHeaders.length > 0) {
      $hiddenHeaders.each(function (index, element) {
        var $header = $(this);
        var position = $header.prevAll('th').length;
        self.$table.find('tbody tr').each(function () {
          var $cells = $(this).find('td:eq(' + position + ')');
          $cells.show();
          // Keep track of the revealed cells, so they can be hidden later.
          self.$revealedCells = $().add(self.$revealedCells).add($cells);
        });
        $header.show();
        // Keep track of the revealed headers, so they can be hidden later.
        self.$revealedCells = $().add(self.$revealedCells).add($header);
      });
      this.$link.text(this.hideText).data('pegged', 1);
    }
    // Hide revealed columns.
    else {
      this.$revealedCells.hide();
      // Strip the 'display:none' declaration from the style attributes of
      // the table cells that .hide() added.
      this.$revealedCells.css('display', '');
      this.$link.text(this.showText).data('pegged', 0);
      // Refresh the toggle link.
      $(window).trigger('resize.tableresponsive');
    }
  }
});
// Make the TableResponsive object available in the Backdrop namespace.
Backdrop.TableResponsive = TableResponsive;

})(jQuery);
;
/**
 * @file
 * Attaches behaviors for the Contextual module.
 */

(function ($) {

Backdrop.contextualLinks = Backdrop.contextualLinks || {};

/**
 * Attaches outline behavior for regions associated with contextual links.
 */
Backdrop.behaviors.contextualLinks = {
  attach: function (context) {
    $('.contextual-links-wrapper', context).once('contextual-links', function () {
      var $wrapper = $(this);
      var $region = $wrapper.closest('.contextual-links-region');
      var $links = $wrapper.find('ul.contextual-links');
      var $trigger = $('<a class="contextual-links-trigger" href="#" />').text(Backdrop.t('Configure')).click(
        function () {
          $links.stop(true, true).slideToggle(100);
          $wrapper.toggleClass('contextual-links-active');
          return false;
        }
      );
      // Attach hover behavior to trigger and ul.contextual-links.
      $trigger.add($links).hover(
        function () { $region.addClass('contextual-links-region-active'); },
        function () { $region.removeClass('contextual-links-region-active'); }
      );
      // Hide the contextual links when user clicks a link or rolls out of the .contextual-links-region.
      $region.bind('mouseleave click', Backdrop.contextualLinks.mouseleave);
      $region.hover(
        function() { $trigger.addClass('contextual-links-trigger-active'); },
        function() { $trigger.removeClass('contextual-links-trigger-active'); }
      );
      // Prepend the trigger.
      $wrapper.prepend($trigger);
    });
  }
};

/**
 * Disables outline for the region contextual links are associated with.
 */
Backdrop.contextualLinks.mouseleave = function () {
  $(this)
    .find('.contextual-links-active').removeClass('contextual-links-active')
    .find('ul.contextual-links').hide();
};

})(jQuery);
;
(function ($) {

/**
 * Retrieves the summary for the first element.
 */
$.fn.backdropGetSummary = function () {
  var callback = this.data('summaryCallback');
  return (this[0] && callback) ? $.trim(callback(this[0])) : '';
};

/**
 * Sets the summary for all matched elements.
 *
 * @param callback
 *   Either a function that will be called each time the summary is
 *   retrieved or a string (which is returned each time).
 */
$.fn.backdropSetSummary = function (callback) {
  var self = this;

  // To facilitate things, the callback should always be a function. If it's
  // not, we wrap it into an anonymous function which just returns the value.
  if (typeof callback != 'function') {
    var val = callback;
    callback = function () { return val; };
  }

  return this
    .data('summaryCallback', callback)
    // To prevent duplicate events, the handlers are first removed and then
    // (re-)added.
    .unbind('formUpdated.summary')
    .bind('formUpdated.summary', function () {
      self.trigger('summaryUpdated');
    })
    // The actual summaryUpdated handler doesn't fire when the callback is
    // changed, so we have to do this manually.
    .trigger('summaryUpdated');
};

/**
 * Sends a 'formUpdated' event each time a form element is modified.
 */
Backdrop.behaviors.formUpdated = {
  attach: function (context) {
    // These events are namespaced so that we can remove them later.
    var events = 'change.formUpdated click.formUpdated blur.formUpdated keyup.formUpdated';
    $(context)
      // Since context could be an input element itself, it's added back to
      // the jQuery object and filtered again.
      .find(':input').addBack().filter(':input')
      // To prevent duplicate events, the handlers are first removed and then
      // (re-)added.
      .unbind(events).bind(events, function () {
        $(this).trigger('formUpdated');
      });
  }
};

/**
 * Prepopulate form fields with information from the visitor cookie.
 */
Backdrop.behaviors.fillUserInfoFromCookie = {
  attach: function (context, settings) {
    $('form.user-info-from-cookie').once('user-info-from-cookie', function () {
      var formContext = this;
      $.each(['name', 'mail', 'homepage'], function () {
        var $element = $('[name=' + this + ']', formContext);
        var cookie = $.cookie('Backdrop.visitor.' + this);
        if ($element.length && cookie) {
          $element.val(cookie);
        }
      });
    });
  }
};

})(jQuery);
;
