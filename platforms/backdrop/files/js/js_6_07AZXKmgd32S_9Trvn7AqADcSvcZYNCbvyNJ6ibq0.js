
(function ($) {

/**
 * Auto-hide summary textarea if empty and show hide and unhide links.
 */
Backdrop.behaviors.textSummary = {
  attach: function (context, settings) {
    $('.text-summary', context).once('text-summary', function () {
      var $widget = $(this).closest('div.field-type-text-with-summary');
      var $summaries = $widget.find('div.text-summary-wrapper');

      $summaries.once('text-summary-wrapper').each(function(index) {
        var $summary = $(this);
        var $summaryLabel = $summary.find('label').first();
        var $full = $widget.find('.text-full').eq(index).closest('.form-item');
        var $fullLabel = $full.find('label').first();

        // Create a placeholder label when the field cardinality is
        // unlimited or greater than 1.
        if ($fullLabel.length == 0) {
          $fullLabel = $('<label></label>').prependTo($full);
        }

        // Setup the edit/hide summary link.
        var $link = $('<span class="field-edit-link">(<a class="link-edit-summary" href="#">' + Backdrop.t('Hide summary') + '</a>)</span>');
        var $a = $link.find('a');
        var toggleClick = true;
        $link.bind('click', function (e) {
          if (toggleClick) {
            $summary.hide();
            $a.html(Backdrop.t('Edit summary'));
            $link.appendTo($fullLabel);
          }
          else {
            $summary.show();
            $a.html(Backdrop.t('Hide summary'));
            $link.appendTo($summaryLabel);
          }
          toggleClick = !toggleClick;
          return false;
        }).appendTo($summaryLabel);

        // If no summary is set, hide the summary field.
        if ($(this).find('.text-summary').val() == '') {
          $link.click();
        }
      });
    });
  }
};

})(jQuery);
;
(function ($) {

/**
 * Toggle the visibility of a fieldset using smooth animations.
 */
Backdrop.toggleFieldset = function (fieldset) {
  var $fieldset = $(fieldset);
  if ($fieldset.is('.collapsed')) {
    var $content = $('> .fieldset-wrapper', fieldset).hide();
    $fieldset
      .removeClass('collapsed')
      .find('> legend span.fieldset-legend-prefix').html(Backdrop.t('Hide'));
    $content.slideDown({
      duration: 'fast',
      easing: 'linear',
      complete: function () {
        Backdrop.collapseScrollIntoView(fieldset);
        $fieldset.trigger({ type: 'collapsed', value: false });
        $(window).triggerHandler('resize');
        fieldset.animating = false;
      },
      step: function () {
        // Scroll the fieldset into view.
        Backdrop.collapseScrollIntoView(fieldset);
      }
    });
  }
  else {
    $('> .fieldset-wrapper', fieldset).slideUp('fast', function () {
      $fieldset
        .addClass('collapsed')
        .find('> legend span.fieldset-legend-prefix').html(Backdrop.t('Show'));
      $fieldset.trigger({ type: 'collapsed', value: true });
      $(window).triggerHandler('resize');
      fieldset.animating = false;
    });
  }
};

/**
 * Scroll a given fieldset into view as much as possible.
 */
Backdrop.collapseScrollIntoView = function (node) {
  var h = document.documentElement.clientHeight || document.body.clientHeight || 0;
  var offset = document.documentElement.scrollTop || document.body.scrollTop || 0;
  var posY = $(node).offset().top;
  var fudge = 55;
  if (posY + node.offsetHeight + fudge > h + offset) {
    if (node.offsetHeight > h) {
      window.scrollTo(0, posY);
    }
    else {
      window.scrollTo(0, posY + node.offsetHeight - h + fudge);
    }
  }
};

Backdrop.behaviors.collapse = {
  attach: function (context, settings) {
    var hasHash = location.hash && location.hash != '#' && $(window).find(location.hash).length;
    $('fieldset.collapsible', context).once('collapse', function () {
      var $fieldset = $(this);
      // Expand fieldset if there are errors inside, or if it contains an
      // element that is targeted by the URI fragment identifier.
      var anchor = hasHash ? ', ' + location.hash : '';
      if ($fieldset.find('.error' + anchor).length) {
        $fieldset.removeClass('collapsed');
      }

      var summary = $('<span class="summary"></span>');
      $fieldset.
        bind('summaryUpdated', function () {
          var text = $.trim($fieldset.backdropGetSummary());
          summary.html(text ? ' (' + text + ')' : '');
        })
        .trigger('summaryUpdated');

      // Turn the legend into a clickable link, but retain span.fieldset-legend
      // for CSS positioning.
      var $legend = $('> legend .fieldset-legend', this);

      $('<span class="fieldset-legend-prefix element-invisible"></span>')
        .append($fieldset.hasClass('collapsed') ? Backdrop.t('Show') : Backdrop.t('Hide'))
        .prependTo($legend)
        .after(document.createTextNode(' '));

      // .wrapInner() does not retain bound events.
      var $link = $('<a class="fieldset-title" href="#"></a>')
        .prepend($legend.contents())
        .appendTo($legend)
        .click(function () {
          var fieldset = $fieldset.get(0);
          // Don't animate multiple times.
          if (!fieldset.animating) {
            fieldset.animating = true;
            Backdrop.toggleFieldset(fieldset);
          }
          return false;
        });

      $legend.append(summary);
    });
  }
};

})(jQuery);
;
(function ($) {

/**
 * A progressbar object. Initialized with the given id. Must be inserted into
 * the DOM afterwards through progressBar.element.
 *
 * method is the function which will perform the HTTP request to get the
 * progress bar state. Either "GET" or "POST".
 *
 * e.g. pb = new progressBar('myProgressBar');
 *      some_element.appendChild(pb.element);
 */
Backdrop.progressBar = function (id, updateCallback, method, errorCallback) {
  this.id = id;
  this.method = method || 'GET';
  this.updateCallback = updateCallback;
  this.errorCallback = errorCallback;

  // The WAI-ARIA setting aria-live="polite" will announce changes after users
  // have completed their current activity and not interrupt the screen reader.
  this.element = $('<div class="progress" aria-live="polite"></div>').attr('id', id);
  this.element.html('<div class="bar"><div class="filled"></div></div>' +
                    '<div class="percentage"></div>' +
                    '<div class="message">&nbsp;</div>');
};

/**
 * Set the percentage and status message for the progressbar.
 */
Backdrop.progressBar.prototype.setProgress = function (percentage, message) {
  if (percentage >= 0 && percentage <= 100) {
    $('div.filled', this.element).css('width', percentage + '%');
    $('div.percentage', this.element).html(percentage + '%');
  }
  $('div.message', this.element).html(message);
  if (this.updateCallback) {
    this.updateCallback(percentage, message, this);
  }
};

/**
 * Start monitoring progress via Ajax.
 */
Backdrop.progressBar.prototype.startMonitoring = function (uri, delay) {
  this.delay = delay;
  this.uri = uri;
  this.sendPing();
};

/**
 * Stop monitoring progress via Ajax.
 */
Backdrop.progressBar.prototype.stopMonitoring = function () {
  clearTimeout(this.timer);
  // This allows monitoring to be stopped from within the callback.
  this.uri = null;
};

/**
 * Request progress data from server.
 */
Backdrop.progressBar.prototype.sendPing = function () {
  if (this.timer) {
    clearTimeout(this.timer);
  }
  if (this.uri) {
    var pb = this;
    // When doing a post request, you need non-null data. Otherwise a
    // HTTP 411 or HTTP 406 (with Apache mod_security) error may result.
    $.ajax({
      type: this.method,
      url: this.uri,
      data: '',
      dataType: 'json',
      success: function (progress) {
        // Display errors.
        if (progress.status == 0) {
          pb.displayError(progress.data);
          return;
        }
        // Update display.
        pb.setProgress(progress.percentage, progress.message);
        // Schedule next timer.
        pb.timer = setTimeout(function () { pb.sendPing(); }, pb.delay);
      },
      error: function (xmlhttp) {
        pb.displayError(Backdrop.ajaxError(xmlhttp, pb.uri));
      }
    });
  }
};

/**
 * Display errors on the page.
 */
Backdrop.progressBar.prototype.displayError = function (string) {
  var error = $('<div class="messages error"></div>').html(string);
  $(this.element).before(error).hide();

  if (this.errorCallback) {
    this.errorCallback(this);
  }
};

})(jQuery);
;
(function ($) {

/**
 * Attaches the autocomplete behavior to all required fields.
 */
Backdrop.behaviors.autocomplete = {
  attach: function (context, settings) {
    var acdb = [];
    $('input.autocomplete', context).once('autocomplete', function () {
      var uri = this.value;
      if (!acdb[uri]) {
        acdb[uri] = new Backdrop.ACDB(uri);
      }
      var $input = $('#' + this.id.substr(0, this.id.length - 13))
        .attr('autocomplete', 'OFF')
        .attr('aria-autocomplete', 'list');
      $($input[0].form).submit(Backdrop.autocompleteSubmit);
      $input.parent()
        .attr('role', 'application')
        .append($('<span class="element-invisible" aria-live="assertive"></span>')
          .attr('id', $input[0].id + '-autocomplete-aria-live')
        );
      new Backdrop.jsAC($input, acdb[uri]);
    });
  }
};

/**
 * Prevents the form from submitting if the suggestions popup is open
 * and closes the suggestions popup when doing so.
 */
Backdrop.autocompleteSubmit = function () {
  var $autocomplete = $('#autocomplete');
  if ($autocomplete.length !== 0) {
    $autocomplete[0].owner.hidePopup();
  }
  return $autocomplete.length === 0;
};

/**
 * An AutoComplete object.
 */
Backdrop.jsAC = function ($input, db) {
  var ac = this;
  this.input = $input[0];
  this.ariaLive = $('#' + this.input.id + '-autocomplete-aria-live');
  this.db = db;

  $input
    .keydown(function (event) { return ac.onkeydown(this, event); })
    .keyup(function (event) { ac.onkeyup(this, event); })
    .blur(function () { ac.hidePopup(); ac.db.cancel(); });
};

/**
 * Handler for the "keydown" event.
 */
Backdrop.jsAC.prototype.onkeydown = function (input, e) {
  if (!e) {
    e = window.event;
  }
  switch (e.keyCode) {
    case 40: // down arrow.
      this.selectDown();
      return false;
    case 38: // up arrow.
      this.selectUp();
      return false;
    default: // All other keys.
      return true;
  }
};

/**
 * Handler for the "keyup" event.
 */
Backdrop.jsAC.prototype.onkeyup = function (input, e) {
  if (!e) {
    e = window.event;
  }
  switch (e.keyCode) {
    case 16: // Shift.
    case 17: // Ctrl.
    case 18: // Alt.
    case 20: // Caps lock.
    case 33: // Page up.
    case 34: // Page down.
    case 35: // End.
    case 36: // Home.
    case 37: // Left arrow.
    case 38: // Up arrow.
    case 39: // Right arrow.
    case 40: // Down arrow.
      return true;

    case 9:  // Tab.
    case 13: // Enter.
    case 27: // Esc.
      this.hidePopup(e.keyCode);
      return true;

    default: // All other keys.
      if (input.value.length > 0 && !input.readOnly) {
        this.populatePopup();
      }
      else {
        this.hidePopup(e.keyCode);
      }
      return true;
  }
};

/**
 * Puts the currently highlighted suggestion into the autocomplete field.
 */
Backdrop.jsAC.prototype.select = function (node) {
  this.input.value = $(node).data('autocompleteValue');
};

/**
 * Highlights the next suggestion.
 */
Backdrop.jsAC.prototype.selectDown = function () {
  if (this.selected && this.selected.nextSibling) {
    this.highlight(this.selected.nextSibling);
  }
  else if (this.popup) {
    var lis = $('li', this.popup);
    if (lis.length > 0) {
      this.highlight(lis.get(0));
    }
  }
};

/**
 * Highlights the previous suggestion.
 */
Backdrop.jsAC.prototype.selectUp = function () {
  if (this.selected && this.selected.previousSibling) {
    this.highlight(this.selected.previousSibling);
  }
};

/**
 * Highlights a suggestion.
 */
Backdrop.jsAC.prototype.highlight = function (node) {
  // Unhighlights a suggestion for "keyup" and "keydown" events.
  if (this.selected !== false) {
    $(this.selected).removeClass('selected');
  }
  $(node).addClass('selected');
  this.selected = node;
  $(this.ariaLive).html($(this.selected).html());
};

/**
 * Unhighlights a suggestion.
 */
Backdrop.jsAC.prototype.unhighlight = function (node) {
  $(node).removeClass('selected');
  this.selected = false;
  $(this.ariaLive).empty();
};

/**
 * Hides the autocomplete suggestions.
 */
Backdrop.jsAC.prototype.hidePopup = function (keycode) {
  // Select item if the right key or mousebutton was pressed.
  if (this.selected && ((keycode && keycode !== 46 && keycode !== 8 && keycode !== 27) || !keycode)) {
    this.input.value = $(this.selected).data('autocompleteValue');
  }
  // Hide popup.
  var popup = this.popup;
  if (popup) {
    this.popup = null;
    $(popup).fadeOut('fast', function () { $(popup).remove(); });
  }
  this.selected = false;
  $(this.ariaLive).empty();
};

/**
 * Positions the suggestions popup and starts a search.
 */
Backdrop.jsAC.prototype.populatePopup = function () {
  var $input = $(this.input);

  // Show popup.
  if (this.popup) {
    $(this.popup).remove();
  }
  this.selected = false;
  this.popup = $('<div id="autocomplete"></div>')[0];
  this.popup.owner = this;

  // Add the popup to the page and position.
  $("body").prepend(this.popup);
  var autocompletInstance = this;
  positionPopup();
  Backdrop.optimizedResize.add(positionPopup, 'autocompletePopup');

  // Do search.
  this.db.owner = this;
  this.db.search(this.input.value);

  function positionPopup() {
    // If the popup has been removed, remove this resize handler.
    if (!autocompletInstance.popup) {
      Backdrop.optimizedResize.remove('autocompletePopup');
      return;
    }

    var offset = $input.offset();
    var paddingLeft = parseInt($input.css('padding-left').replace('px', ''), 10);
    var paddingRight = parseInt($input.css('padding-right').replace('px', ''), 10);
    var paddingWidth = paddingLeft + paddingRight;

    // Because we use "fixed" position, the final location is the offset from
    // the document and the height of the element, minus scroll bar position.
    $(autocompletInstance.popup).css({
      top: ($input.outerHeight() + offset.top - $(document).scrollTop()) + 'px',
      left: (offset.left - $(document).scrollLeft()) + 'px',
      width: ($input.width() + paddingWidth) + 'px',
      position: 'fixed'
    });
  }
};

/**
 * Fills the suggestion popup with any matches received.
 */
Backdrop.jsAC.prototype.found = function (matches) {
  // If no value in the textfield, do not show the popup.
  if (!this.input.value.length) {
    return false;
  }

  // Prepare matches.
  var ac = this;
  var ul = $('<ul></ul>')
    .on('mousedown', 'li', function (e) { ac.select(this); })
    .on('mouseover', 'li', function (e) { ac.highlight(this); })
    .on('mouseout', 'li', function (e) { ac.unhighlight(this); });
  for (var key in matches) {
    if (matches.hasOwnProperty(key)) {
      $('<li></li>')
        .html($('<div></div>').html(matches[key]))
        .data('autocompleteValue', key)
        .appendTo(ul);
    }
  }

  // Show popup with matches, if any.
  if (this.popup) {
    if (ul.children().length) {
      $(this.popup).empty().append(ul).show();
      $(this.ariaLive).html(Backdrop.t('Autocomplete popup'));
    }
    else {
      $(this.popup).css({ visibility: 'hidden' });
      this.hidePopup();
    }
  }
};

Backdrop.jsAC.prototype.setStatus = function (status) {
  switch (status) {
    case 'begin':
      $(this.input).addClass('throbbing');
      $(this.ariaLive).html(Backdrop.t('Searching for matches...'));
      break;
    case 'cancel':
    case 'error':
    case 'found':
      $(this.input).removeClass('throbbing');
      break;
  }
};

/**
 * An AutoComplete DataBase object.
 */
Backdrop.ACDB = function (uri) {
  this.uri = uri;
  this.delay = 300;
  this.cache = {};
};

/**
 * Performs a cached and delayed search.
 */
Backdrop.ACDB.prototype.search = function (searchString) {
  var db = this;
  this.searchString = searchString;

  // See if this string needs to be searched for anyway. The pattern ../ is
  // stripped since it may be misinterpreted by the browser.
  searchString = searchString.replace(/^\s+|\.{2,}\/|\s+$/g, '');
  // Skip empty search strings, or search strings ending with a comma, since
  // that is the separator between search terms.
  if (searchString.length <= 0 ||
    searchString.charAt(searchString.length - 1) === ',') {
    return;
  }

  // See if this key has been searched for before.
  if (this.cache[searchString]) {
    return this.owner.found(this.cache[searchString]);
  }

  // Initiate delayed search.
  if (this.timer) {
    clearTimeout(this.timer);
  }
  this.timer = setTimeout(function () {
    db.owner.setStatus('begin');

    // Ajax GET request for autocompletion.
    $.ajax({
      type: 'GET',
      url: db.uri + '/' + encodeURIComponent(searchString),
      dataType: 'json',
      success: function (matches) {
        if (typeof matches.status === 'undefined' || matches.status !== 0) {
          db.cache[searchString] = matches;
          // Verify if these are still the matches the user wants to see.
          if (db.searchString === searchString) {
            db.owner.found(matches);
          }
          db.owner.setStatus('found');
        }
      },
      error: function (xmlhttp) {
        Backdrop.displayAjaxError(Backdrop.ajaxError(xmlhttp, db.uri));
      }
    });
  }, this.delay);
};

/**
 * Cancels the current autocomplete request.
 */
Backdrop.ACDB.prototype.cancel = function () {
  if (this.owner) {
    this.owner.setStatus('cancel');
  }
  if (this.timer) {
    clearTimeout(this.timer);
  }
  this.searchString = '';
};

})(jQuery);
;

(function ($) {

Backdrop.behaviors.nodeFieldsetSummaries = {
  attach: function (context) {
    $('fieldset.node-form-revision-information', context).backdropSetSummary(function (context) {
      var revisionCheckbox = $('.form-item-revision input', context);

      // Return 'New revision' if the 'Create new revision' checkbox is checked,
      // or if the checkbox doesn't exist, but the revision log does. For users
      // without the "Administer content" permission the checkbox won't appear,
      // but the revision log will if the content type is set to auto-revision.
      if (revisionCheckbox.is(':checked') || (!revisionCheckbox.length && $('.form-item-log textarea', context).length)) {
        return Backdrop.t('New revision');
      }

      return Backdrop.t('No revision');
    });

    $('fieldset.node-form-author', context).backdropSetSummary(function (context) {
      var name = $('.form-item-name input', context).val() || Backdrop.settings.anonymous;
      var dateParts = [];
      $('.form-item-date input', context).each(function() {
        var datePart = $(this).val();
        if (datePart) {
          dateParts.push(datePart);
        }
      });
      var date = dateParts.join(' ');
      return date ?
        Backdrop.t('By @name on @date', { '@name': name, '@date': date }) :
        Backdrop.t('By @name', { '@name': name });
    });

    $('fieldset.node-form-options', context).backdropSetSummary(function (context) {
      var vals = [];

      // Status radio button.
      var $status = $(context).find('input[name="status"]:checked');
      if ($status.val() == 2) {
        var dateParts = [];
        $('.form-item-scheduled input', context).each(function() {
          var datePart = $(this).val();
          if (datePart) {
            dateParts.push(datePart);
          }
        });
        var date = dateParts.join(' ');
        vals.push(Backdrop.t('Scheduled for @date', { '@date': date }));
      }
      else {
        var statusLabel = $status.parent().text();
        vals.push(Backdrop.checkPlain($.trim(statusLabel)));
      }

      // Other checkboxes like Promoted and Sticky.
      $(context).find('input:checked').not($status).parent().each(function () {
        vals.push(Backdrop.checkPlain($.trim($(this).text())));
      });

      return vals.join(', ');
    });
  }
};

})(jQuery);
;
(function ($) {

"use strict";

/**
 * Attaches sticky table headers.
 */
Backdrop.behaviors.tableHeader = {
  attach: function (context) {
    if (!$.support.positionFixed) {
      return;
    }
    var $tables = $(context).find('table.sticky-enabled:not(.sticky-processed)').addClass('sticky-processed');
    for (var i = 0, il = $tables.length; i < il; i++) {
      TableHeader.tables.push(new TableHeader($tables[i]));
    }
  }
};

function scrollValue(position) {
  return document.documentElement[position] || document.body[position];
}

// Helper method to loop through tables and execute a method.
function forTables(method, arg) {
  var tables = TableHeader.tables;
  for (var i = 0, il = tables.length; i < il; i++) {
    tables[i][method](arg);
  }
}

function tableHeaderResizeHandler() {
  forTables('recalculateSticky');
}

var scrollTimer;
function tableHeaderOnScrollHandler() {
  clearTimeout(scrollTimer);
  scrollTimer = setTimeout(function() {
    forTables('onScroll');
  }, 50);
}

function tableHeaderOffsetChangeHandler() {
  // Compute the new offset value.
  TableHeader.computeOffsetTop();
  forTables('stickyPosition', TableHeader.offsetTop);
}

// Bind event that need to change all tables.
$(window).on({
  /**
   * Bind only one event to take care of calling all scroll callbacks.
   */
  'scroll.TableHeader': tableHeaderOnScrollHandler
});

/**
 * When resizing table width and offset top can change, recalculate everything.
 */
Backdrop.optimizedResize.add(tableHeaderResizeHandler);

// Bind to custom Backdrop events.
$(document).on({
  /**
   * Recalculate columns width when window is resized and when show/hide
   * weight is triggered.
   */
  'columnschange.TableHeader': tableHeaderResizeHandler,

  /**
   * Offset value vas changed by a third party script.
   */
  'offsettopchange.TableHeader': tableHeaderOffsetChangeHandler
});

/**
 * Constructor for the tableHeader object. Provides sticky table headers.
 * TableHeader will make the current table header stick to the top of the page
 * if the table is very long.
 *
 * Fire a custom "topoffsetchange" event to make TableHeader compute the
 * new offset value from the "data-offset-top" attributes of relevant elements.
 *
 * @param table
 *   DOM object for the table to add a sticky header to.*
 * @constructor
 */
function TableHeader(table) {
  var $table = $(table);
  this.$originalTable = $table;
  this.$originalHeader = $table.children('thead');
  this.$originalHeaderCells = this.$originalHeader.find('> tr > th');
  this.displayWeight = null;

  this.$originalTable.addClass('sticky-table');
  this.tableHeight = $table[0].clientHeight;
  this.tableOffset = this.$originalTable.offset();

  // React to columns change to avoid making checks in the scroll callback.
  this.$originalTable.bind('columnschange', {tableHeader: this}, function (e, display) {
    var tableHeader = e.data.tableHeader;
    if (tableHeader.displayWeight === null || tableHeader.displayWeight !== display) {
      tableHeader.recalculateSticky();
    }
    tableHeader.displayWeight = display;
  });

  // Create and display sticky header.
  this.createSticky();
}

/**
 * Store the state of TableHeader.
 */
$.extend(TableHeader, {
   /**
    * This will store the state of all processed tables.
    *
    * @type {Array}
    */
   tables: [],

   /**
    * Cache of computed offset value.
    *
    * @type {Number}
    */
   offsetTop: 0,

  /**
   * Sum all [data-offset-top] values and cache it.
   */
  computeOffsetTop: function () {
    var $offsets = $('[data-offset-top]').not('.sticky-header');
    var value, sum = 0;
    for (var i = 0, il = $offsets.length; i < il; i++) {
      value = parseInt($offsets[i].getAttribute('data-offset-top'), 10);
      sum += !isNaN(value) ? value : 0;
    }
    this.offsetTop = sum;
    return sum;
  }
});

/**
 * Extend TableHeader prototype.
 */
$.extend(TableHeader.prototype, {
  /**
   * Minimum height in pixels for the table to have a sticky header.
   */
  minHeight: 100,

  /**
   * Absolute position of the table on the page.
   */
  tableOffset: null,

  /**
   * Absolute position of the table on the page.
   */
  tableHeight: null,

  /**
   * Boolean storing the sticky header visibility state.
   */
  stickyVisible: false,

  /**
   * Create the duplicate header.
   */
  createSticky: function () {
    // Clone the table header so it inherits original jQuery properties.
    var $stickyHeader = this.$originalHeader.clone(true);
    // Hide the table to avoid a flash of the header clone upon page load.
    this.$stickyTable = $('<table class="sticky-header"/>')
      .css({
        visibility: 'hidden',
        position: 'fixed',
        top: '0px'
      })
      .append($stickyHeader)
      .insertBefore(this.$originalTable);

    this.$stickyHeaderCells = $stickyHeader.find('> tr > th');

    // Initialize all computations.
    this.recalculateSticky();
  },

  /**
   * Set absolute position of sticky.
   *
   * @param offsetTop
   * @param offsetLeft
   */
  stickyPosition: function (offsetTop, offsetLeft) {
    var css = {};
    if (!isNaN(offsetTop) && offsetTop !== null) {
      css.top = offsetTop + 'px';
    }
    if (!isNaN(offsetLeft) && offsetTop !== null) {
      css.left = offsetLeft + 'px';
    }
    return this.$stickyTable.css(css);
  },

  /**
   * Returns true if sticky is currently visible.
   */
  checkStickyVisible: function () {
    var scrollTop = scrollValue('scrollTop');
    var tableTop = this.tableOffset.top - TableHeader.offsetTop;
    var tableBottom = tableTop + this.tableHeight;
    var visible = false;

    if (tableTop < scrollTop && scrollTop < (tableBottom - this.minHeight)) {
      visible = true;
    }

    this.stickyVisible = visible;
    return visible;
  },

  /**
   * Check if sticky header should be displayed.
   *
   * This function is throttled to once every 250ms to avoid unnecessary calls.
   */
  onScroll: function () {
    // Track horizontal positioning relative to the viewport.
    this.stickyPosition(null, scrollValue('scrollLeft'));
    this.$stickyTable.css('visibility', this.checkStickyVisible() ? 'visible' : 'hidden');
  },

  /**
   * Event handler: recalculates position of the sticky table header.
   */
  recalculateSticky: function () {
    // Update table size.
    this.tableHeight = this.$originalTable[0].clientHeight;

    // Update offset.
    TableHeader.computeOffsetTop();
    this.tableOffset = this.$originalTable.offset();
    var leftOffset = parseInt(this.$originalTable.offset().left);
    this.stickyPosition(TableHeader.offsetTop, leftOffset);

    // Update columns width.
    var $that = null;
    var $stickyCell = null;
    var display = null;
    // Resize header and its cell widths.
    // Only apply width to visible table cells. This prevents the header from
    // displaying incorrectly when the sticky header is no longer visible.
    for (var i = 0, il = this.$originalHeaderCells.length; i < il; i++) {
      $that = $(this.$originalHeaderCells[i]);
      $stickyCell = this.$stickyHeaderCells.eq($that.index());
      display = $that.css('display');
      if (display !== 'none') {
        $stickyCell.css({'width': $that.width(), 'display': display});
      }
      else {
        $stickyCell.css('display', 'none');
      }
    }
    this.$stickyTable.css('width', this.$originalTable.width());
  }
});

// Calculate the table header positions on page load.
window.setTimeout(function() {
  $(window).triggerHandler('scroll.TableHeader');
}, 100);

// Expose constructor in the public space.
Backdrop.TableHeader = TableHeader;

}(jQuery));
;
/**
 * @file
 * Attaches behaviors for the Redirect module.
 */
(function ($) {

"use strict";

Backdrop.behaviors.redirectFieldsetSummaries = {
  attach: function (context) {
    $('fieldset.redirect-list', context).backdropSetSummary(function (context) {
      if ($('table.redirect-list tbody td.empty', context).length) {
        return Backdrop.t('No redirects');
      }
      else {
        var redirects = $('table.redirect-list tbody tr', context).length;
        return Backdrop.formatPlural(redirects, '1 redirect', '@count redirects');
      }
    });
  }
};

})(jQuery);
;
/**
 * @file
 * Attaches behaviors for the Path module.
 */
(function ($) {

"use strict";

Backdrop.behaviors.pathFieldsetSummaries = {
  attach: function (context) {
    $(context).find('fieldset.path-form').backdropSetSummary(function (element) {
      var $element = $(element);
      var path = $element.find('[name="path[alias]"]').val();
      var automatic = $element.find('[name="path[auto]"]').prop('checked');

      if (automatic) {
        return Backdrop.t('Automatic alias');
      }
      if (path) {
        return Backdrop.t('Alias: @alias', { '@alias': path });
      }
      else {
        return Backdrop.t('No alias');
      }
    });
  }
};

})(jQuery);
;
(function ($) {

Backdrop.behaviors.menuFieldsetSummaries = {
  attach: function (context) {
    var $context = $(context);
    $context.find('fieldset.menu-link-form').backdropSetSummary(function () {
      if ($context.find('.form-item-menu-enabled input').is(':checked')) {
        return Backdrop.checkPlain($context.find('.form-item-menu-link-title input').val());
      }
      else {
        return Backdrop.t('Not in menu');
      }
    });
  }
};

/**
 * Automatically fill in a menu link title, if possible.
 */
Backdrop.behaviors.menuLinkAutomaticTitle = {
  attach: function (context) {
    $(context).find('fieldset.menu-link-form').each(function () {
      // Try to find menu settings widget elements as well as a 'title' field in
      // the form, but play nicely with user permissions and form alterations.
      var $checkbox = $('.form-item-menu-enabled input', this);
      var $link_title = $('.form-item-menu-link-title input', context);
      var $title = $(this).closest('form').find('.form-item-title input');
      // Bail out if we do not have all required fields.
      if (!($checkbox.length && $link_title.length && $title.length)) {
        return;
      }
      // If there is a link title already, mark it as overridden. The user expects
      // that toggling the checkbox twice will take over the node's title.
      if ($checkbox.is(':checked') && $link_title.val().length) {
        $link_title.data('menuLinkAutomaticTitleOveridden', true);
      }
      // Whenever the value is changed manually, disable this behavior.
      $link_title.keyup(function () {
        $link_title.data('menuLinkAutomaticTitleOveridden', true);
      });
      // Global trigger on checkbox (do not fill-in a value when disabled).
      $checkbox.change(function () {
        if ($checkbox.is(':checked')) {
          if (!$link_title.data('menuLinkAutomaticTitleOveridden')) {
            $link_title.val($title.val());
          }
        }
        else {
          $link_title.val('');
          $link_title.removeData('menuLinkAutomaticTitleOveridden');
        }
        $checkbox.closest('fieldset.vertical-tabs-pane').trigger('summaryUpdated');
        $checkbox.trigger('formUpdated');
      });
      // Take over any title change.
      $title.keyup(function () {
        if (!$link_title.data('menuLinkAutomaticTitleOveridden') && $checkbox.is(':checked')) {
          $link_title.val($title.val());
          $link_title.val($title.val()).trigger('formUpdated');
        }
      });
    });
  }
};

})(jQuery);
;
/**
 * @file
 * Attaches comment behaviors to the node form.
 */

(function ($) {

Backdrop.behaviors.commentFieldsetSummaries = {
  attach: function (context) {
    var $context = $(context);
    $context.find('fieldset.comment-node-settings-form').backdropSetSummary(function () {
      var vals = [];
      var status = $context.find('.form-item-comment input:checked').next('label').text().replace(/^\s+|\s+$/g, '');
      vals.push(Backdrop.checkPlain(status));
      if ($.trim(status) != 'Open') {
        if ($context.find(".form-item-comment-hidden input:checked").length) {
          vals.push(Backdrop.t('Hidden'));
        }
      }
      return Backdrop.checkPlain(vals.join(', '));
    });

    // Provide the summary for the node type form.
    $context.find('fieldset.comment-node-type-settings-form').backdropSetSummary(function() {
      var vals = [];

      // Default comment setting.
      vals.push($context.find(".form-item-comment-default input:checked").parent().find('label').text().replace(/^\s+|\s+$/g, ''));

      // Comments per page.
      var number = parseInt($context.find(".form-item-comment-per-page select option:selected").val());
      vals.push(Backdrop.t('@number comments per page', {'@number': number}));

      // Threading.
      if ($context.find(".form-item-comment-mode input:checked").length) {
        vals.push(Backdrop.t('Threaded'));
      }
      else {
        vals.push(Backdrop.t('Flat list'));
      }

      // Automatic comment closer setting.
      if ($context.find(".form-item-comment-close-enabled input:checked").length) {
        var number = parseInt($context.find(".form-item-comment-close-days input").val());
        vals.push(Backdrop.t('Automatically close comments after @number days', {'@number': number}));
      }

      return Backdrop.checkPlain(vals.join(', '));
    });
  }
};

})(jQuery);
;
