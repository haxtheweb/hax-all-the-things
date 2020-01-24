/**
 * @file
 * Attaches administration-specific behavior for the Filter module.
 */

(function ($) {

Backdrop.behaviors.filterStatus = {
  attach: function (context, settings) {
    $('#filters-status-wrapper input.form-checkbox', context).once('filter-status', function () {
      var $checkbox = $(this);
      // Retrieve the tabledrag row belonging to this filter.
      var $row = $('#' + $checkbox.attr('id').replace(/-status$/, '-weight'), context).closest('tr');
      // Retrieve the vertical tab belonging to this filter.
      var tab = $('#' + $checkbox.attr('id').replace(/-status$/, '-settings'), context).data('verticalTab');

      // Bind click handler to this checkbox to conditionally show and hide the
      // filter's tableDrag row and vertical tab pane.
      $checkbox.bind('click.filterUpdate', function () {
        if ($checkbox.is(':checked')) {
          $row.show();
          if (tab) {
            tab.tabShow().updateSummary();
          }
        }
        else {
          $row.hide();
          if (tab) {
            tab.tabHide().updateSummary();
          }
        }
        // Restripe table after toggling visibility of table row.
        Backdrop.tableDrag['filter-order'].restripeTable();
      });

      // Attach summary for configurable filters (only for screen-readers).
      if (tab) {
        tab.fieldset.backdropSetSummary(function (tabContext) {
          return $checkbox.is(':checked') ? Backdrop.t('Enabled') : Backdrop.t('Disabled');
        });
      }

      // Trigger our bound click handler to update elements to initial state.
      $checkbox.triggerHandler('click.filterUpdate');
    });
  }
};

Backdrop.editorConfiguration = {

  /**
   * Must be called by a specific text editor's configuration whenever a feature
   * is added by the user.
   *
   * Triggers the backdropEditorFeatureAdded event on the document, which receives
   * a Backdrop.EditorFeature object.
   *
   * @param Backdrop.EditorFeature feature
   *   A text editor feature object.
   */
  addedFeature: function (feature) {
    $(document).trigger('backdropEditorFeatureAdded', feature);
  },

  /**
   * Must be called by a specific text editor's configuration whenever a feature
   * is removed by the user.
   *
   * Triggers the backdropEditorFeatureRemoved event on the document, which
   * receives a Backdrop.EditorFeature object.
   *
   * @param Backdrop.EditorFeature feature
   *   A text editor feature object.
   */
  removedFeature: function (feature) {
    $(document).trigger('backdropEditorFeatureRemoved', feature);
  },

  /**
   * Must be called by a specific text editor's configuration whenever a feature
   * is modified, i.e. has different rules.
   *
   * For example when the "Bold" button is configured to use the <b> tag instead
   * of the <strong> tag.
   *
   * Triggers the backdropEditorFeatureModified event on the document, which
   * receives a Backdrop.EditorFeature object.
   *
   * @param Backdrop.EditorFeature feature
   *   A text editor feature object.
   */
  modifiedFeature: function (feature) {
    $(document).trigger('backdropEditorFeatureModified', feature);
  },

  /**
   * Must be called by a specific text editor's configuration whenever a feature
   * is utilized by default at page load time.
   *
   * Triggers the backdropEditorFeatureInit event on the document, which
   * receives a Backdrop.EditorFeature object and the status of the feature.
   *
   * @param Backdrop.EditorFeature feature
   *   A text editor feature object.
   * @param bool enabled
   *   The initial state of the feature at load time.
   */
  initFeature: function (feature, status) {
    $(document).trigger('backdropEditorFeatureInit', [feature, status]);
  },

  /**
   * May be called by a specific text editor's configuration whenever a feature
   * is being added, to check whether it would require the filter settings to be
   * updated.
   *
   * The canonical use case is when a text editor is being enabled: preferably
   * this would not cause the filter settings to be changed; rather, the default
   * set of buttons (features) for the text editor should adjust itself to not
   * cause filter setting changes.
   *
   * Note: for filters to integrate with this functionality, it is necessary
   * that they implement Backdrop.filterSettingsForEditors[filterID].getRules().
   *
   * @param Backdrop.EditorFeature feature
   *   A text editor feature object.
   * @param Array combinedFilterRules
   *   An array of all filtering settings against which the feature should be
   *   checked. This list most commonly may be retrieved by calling
   *   Backdrop.filterConfiguration.getCombinedFilterRules().
   * @return Boolean
   *   Whether the given feature is allowed by the current filters.
   */
  featureIsAllowed: function (feature, combinedFilterRules) {
    var featureRule, featureAllowed;

    // If a feature has no requirements, return immediately.
    if (feature.rules.length === 0) {
      return true;
    }

    // Small helper for intersecting arrays.
    var _intersect = function(a, b) {
      var t, start;
      if (b.length > a.length) {
        t = b;
        b = a;
        a = t;
      }
      return a.filter(function (e) {
        for (var n = 0; n < b.length; n++) {
          if (b[n].indexOf('*') + 1 === b[n].length) {
            start = e.substr(b[n].length - 1);
            if (e.indexOf(start) === 0) {
              return true;
            }
          }
          else if (b[n] === e) {
            return true;
          }
        }
        return false;
      });
    };

    // Loop over each rule within the feature.
    for (var featureRuleIndex = 0; featureRuleIndex < feature.rules.length; featureRuleIndex++) {
      featureRule = feature.rules[featureRuleIndex];

      // Optional rules won't prevent a feature from being allowed.
      if (!featureRule.required) {
        continue;
      }

      // Check if the feature tag is allowed.
      var allowedIntersection, forbiddenIntersection;
      if (featureRule.tags) {
        allowedIntersection = _intersect(featureRule.tags, combinedFilterRules.allowedTags);
        forbiddenIntersection = _intersect(featureRule.tags, combinedFilterRules.forbiddenTags);
        // An explicit allowing of the tag.
        if (allowedIntersection.length && allowedIntersection.length === featureRule.tags.length) {
          featureAllowed = true;
        }
        // An explicit forbidding of the tag will override an allowed tag.
        if (forbiddenIntersection.length !== 0) {
          return false;
        }

        // Check if the different types of properties are allowed.
        var tagName, tagIndex, propertyType, propertyIndex;
        var propertyTypes = ['attributes', 'classes', 'styles'];
        for (tagIndex = 0; tagIndex < featureRule.tags.length; tagIndex++) {
          tagName = featureRule.tags[tagIndex];
          if (combinedFilterRules.properties.hasOwnProperty(tagName)) {
            for (propertyIndex = 0; propertyIndex < propertyTypes.length; propertyIndex++) {
              propertyType = propertyTypes[propertyIndex];
              allowedIntersection = _intersect(featureRule[propertyType], combinedFilterRules.properties[tagName].allowed[propertyType]);
              forbiddenIntersection = _intersect(featureRule[propertyType], combinedFilterRules.properties[tagName].forbidden[propertyType]);
              // Any not-allowed attributes will shortcut and return false.
              if ((allowedIntersection.length && allowedIntersection.length !== featureRule[propertyType].length) || forbiddenIntersection.length !== 0) {
                return false;
              }
            }
          }
          // Check again against the wildcards.
          if (combinedFilterRules.properties.hasOwnProperty('*')) {
            for (propertyIndex = 0; propertyIndex < propertyTypes.length; propertyIndex++) {
              propertyType = propertyTypes[propertyIndex];
              allowedIntersection = _intersect(featureRule[propertyType], combinedFilterRules.properties['*'].allowed[propertyType]);
              forbiddenIntersection = _intersect(featureRule[propertyType], combinedFilterRules.properties['*'].forbidden[propertyType]);
              if ((allowedIntersection.length && allowedIntersection.length !== featureRule[propertyType].length) || forbiddenIntersection.length !== 0) {
                return false;
              }
            }
          }
        }
      }
    }

    return featureAllowed;
  }
};

/**
 * A text editor feature object. Initialized with the feature name.
 *
 * Contains a set of HTML rules (Backdrop.EditorFeatureHTMLRule objects) that
 * describe which HTML tags, attributes, styles and classes are required (i.e.
 * essential for the feature to function at all) and which are allowed (i.e. the
 * feature may generate this, but they're not essential).
 *
 * It is necessary to allow for multiple HTML rules per feature: with just one
 * HTML rule per feature, there is not enough expressiveness to describe certain
 * cases. For example: a "table" feature would probably require the <table> tag,
 * and might allow for e.g. the "summary" attribute on that tag. However, the
 * table feature would also require the <tr> and <td> tags, but it doesn't make
 * sense to allow for a "summary" attribute on these tags. Hence these would
 * need to be split in two separate rules.
 *
 * HTML rules must be added with the addHTMLRule() method. A feature that has
 * zero HTML rules does not create or modify HTML.
 *
 * @param String name
 *   The name of the feature.
 *
 * @see Backdrop.EditorFeatureHTMLRule
 */
Backdrop.EditorFeature = function (name) {
  this.name = name;
  this.rules = [];
};

/**
 * Adds a HTML rule to the list of HTML rules for this feature.
 *
 * @param Backdrop.FilterHTMLRule rule
 *   A text editor feature HTML rule.
 */
Backdrop.EditorFeature.prototype.addHTMLRule = function (rule) {
  this.rules.push(rule);
};

/**
 * Constructor for an editor feature HTML rule. Intended to be used in
 * combination with Backdrop.EditorFeature.
 *
 * Examples:
 *  - required: true,
 *  - tags: ['<a>']
 *  - attributes: ['href', 'alt']
 *  - styles: ['color', 'text-decoration']
 *  - classes: ['external', 'internal']
 */
Backdrop.EditorFeatureHTMLRule = function (ruleDefinition) {
  this.required = true;
  this.tags = [];
  this.attributes = [];
  this.styles = [];
  this.classes = [];
  if (ruleDefinition) {
    var rule = this;
    $.each(ruleDefinition, function(key) {
      if (typeof rule[key] !== 'undefined') {
        rule[key] = ruleDefinition[key];
      }
    });
  }
};

/**
 * Constructor for a text filter status object. Initialized with the filter ID.
 *
 * Indicates whether the text filter is currently active (enabled) or not.
 *
 * Contains a set of HTML rules (Backdrop.FilterHTMLRule objects) that describe
 * which HTML tags are allowed or forbidden. They can also describe for a set of
 * tags (or all tags) which attributes, styles and classes are allowed and which
 * are forbidden.
 *
 * It is necessary to allow for multiple HTML rules per feature, for analogous
 * reasons as Backdrop.EditorFeature.
 *
 * HTML rules must be added with the addHTMLRule() method. A filter that has
 * zero HTML rules does not disallow any HTML.
 *
 * @param String name
 *   The name of the feature.
 *
 * @see Backdrop.FilterHTMLRule
 */
Backdrop.FilterStatus = function (name) {
  this.name = name;
  this.active = false;
  this.rules = [];
};

/**
 * Adds a HTML rule to the list of HTML rules for this filter.
 *
 * @param Backdrop.FilterHTMLRule rule
 *   A text filter HTML rule.
 */
Backdrop.FilterStatus.prototype.addHTMLRule = function (rule) {
  this.rules.push(rule);
};

/**
 * A text filter HTML rule object. Intended to be used in combination with
 * Backdrop.FilterStatus.
 *
 * A text filter rule object describes
 *  1. allowed or forbidden tags: (optional) whitelist or blacklist HTML tags
 *  2. restricted tag properties: (optional) whitelist or blacklist attributes,
 *     styles and classes on a set of HTML tags.
 *
 * Typically, each text filter rule object does either 1 or 2, not both.
 *
 * The structure can be very clearly seen below:
 *  1. use the "tags" key to list HTML tags, and set the "allow" key to either
 *     true (to allow these HTML tags) or false (to forbid these HTML tags). If
 *     you leave the "tags" key's default value (the empty array), no
 *     restrictions are applied.
 *  2. all nested within the "properties" key: use the "tags" subkey to list
 *     HTML tags to which you want to apply property restrictions, then use the
 *     "allowed" subkey to whitelist specific property values, and similarly use
 *     the "forbidden" subkey to blacklist specific property values.
 *
 * Examples:
 *  - Whitelist the "p", "strong" and "a" HTML tags:
 *    {
 *      tags: ['p', 'strong', 'a'],
 *      allow: true,
 *      properties: {
 *        tags: [],
 *        allowed: { attributes: [], styles: [], classes: [] },
 *        forbidden: { attributes: [], styles: [], classes: [] }
 *      }
 *    }
 *  - For the "a" HTML tag, only allow the "href" attribute and the "external"
 *    class and disallow the "target" attribute.
 *    {
 *      tags: [],
 *      allow: null,
 *      properties: {
 *        tags: ['a'],
 *        allowed: { attributes: ['href'], styles: [], classes: ['external'] },
 *        forbidden: { attributes: ['target'], styles: [], classes: [] }
 *      }
 *    }
 *  - For all tags, allow the "data-*" attribute (that is, any attribute that
 *    begins with "data-").
 *    {
 *      tags: [],
 *      allow: null,
 *      properties: {
 *        tags: ['*'],
 *        allowed: { attributes: ['data-*'], styles: [], classes: [] },
 *        forbidden: { attributes: [], styles: [], classes: [] }
 *      }
 *    }
 */
Backdrop.FilterHTMLRule = function () {
  return {
    // Allow or forbid tags.
    tags: [],
    allow: null,
    // Apply restrictions to properties set on tags.
    properties: {
      tags: [],
      allowed: {attributes: [], styles: [], classes: []},
      forbidden: {attributes: [], styles: [], classes: []}
    }
  };
};

/**
 * Tracks the configuration of all text filters in Backdrop.FilterStatus objects
 * for Backdrop.editorConfiguration.featureIsAllowedByFilters().
 */
Backdrop.filterConfiguration = {

  /**
   * Backdrop.FilterStatus objects, keyed by filter ID.
   */
  statuses: {},

  /**
   * Live filter setting parsers, keyed by filter ID, for those filters that
   * implement it.
   *
   * Filters should load the implementing JavaScript on the filter configuration
   * form and implement Backdrop.filterSettings[filterID].getRules(), which should
   * return an array of Backdrop.FilterHTMLRule objects.
   */
  liveSettingParsers: {},

  /**
   * Updates all Backdrop.FilterStatus objects to reflect the current state.
   *
   * Automatically checks whether a filter is currently enabled or not.
   *
   * If a filter implements a live setting parser, then that will be used to
   * keep the HTML rules for the Backdrop.FilterStatus object up-to-date.
   */
  update: function () {
    for (var filterID in Backdrop.filterConfiguration.statuses) {
      if (Backdrop.filterConfiguration.statuses.hasOwnProperty(filterID)) {
        // Update status.
        Backdrop.filterConfiguration.statuses[filterID].active = $('[name="filters[' + filterID + '][status]"]').is(':checked');

        // Update current rules.
        if (Backdrop.filterConfiguration.liveSettingParsers[filterID]) {
          Backdrop.filterConfiguration.statuses[filterID].rules = Backdrop.filterConfiguration.liveSettingParsers[filterID].getRules();
        }
      }
    }
  },

  /**
   * Retrieves all filter rules from each liveSettingParsers callback.
   */
  getAllFilterRules: function () {
    var allRules = [];
    var filterParser;
    for (var filterName in Backdrop.filterConfiguration.liveSettingParsers) {
      if (Backdrop.filterConfiguration.liveSettingParsers.hasOwnProperty(filterName)) {
        filterParser = Backdrop.filterConfiguration.liveSettingParsers[filterName];
        $.merge(allRules, filterParser.getRules());
      }
    }
    return allRules;
  },

  /**
   * Get a complete list of all filter rules, combined into a single list.
   */
  getCombinedFilterRules: function(filterRules) {
    filterRules = filterRules || this.getAllFilterRules();

    var propertyTag, propertyType, filterRule;
    var propertyTypes = ['attributes', 'classes', 'styles'];
    var allFilterInfo = {
      allowedTags: [],
      forbiddenTags: [],
      properties: {}
    };

    for (var filterRuleIndex = 0; filterRuleIndex < filterRules.length; filterRuleIndex++) {
      // Assemble tag lists.
      filterRule = filterRules[filterRuleIndex];
      if (filterRule.tags.length) {
        if (filterRule.allow) {
          $.merge(allFilterInfo.allowedTags, filterRule.tags);
        }
        else {
          $.merge(allFilterInfo.forbiddenTags, filterRule.tags)
        }
      }

      // Assemble list for each type of property.
      for (var propertyIndex = 0; propertyIndex < propertyTypes.length; propertyIndex++) {
        propertyType = propertyTypes[propertyIndex];
        for (var propertyTagIndex = 0; propertyTagIndex < filterRule.properties.tags.length; propertyTagIndex++) {
          propertyTag = filterRule.properties.tags[propertyTagIndex];
          if (!allFilterInfo.properties[propertyTag]) {
            allFilterInfo.properties[propertyTag] = {
              allowed: {},
              forbidden: {}
            };
          }
          if (!allFilterInfo.properties[propertyTag].allowed[propertyType]) {
            allFilterInfo.properties[propertyTag].allowed[propertyType] = [];
          }
          if (!allFilterInfo.properties[propertyTag].forbidden[propertyType]) {
            allFilterInfo.properties[propertyTag].forbidden[propertyType] = [];
          }

          $.merge(allFilterInfo.properties[propertyTag].allowed[propertyType], filterRule.properties.allowed[propertyType]);
          $.merge(allFilterInfo.properties[propertyTag].forbidden[propertyType], filterRule.properties.forbidden[propertyType]);
        }
      }
    }
    return allFilterInfo;
  }


};

/**
 * Initializes Backdrop.filterConfiguration.
 */
Backdrop.behaviors.initializeFilterConfiguration = {
  attach: function (context, settings) {
    var $context = $(context);

    $context.find('#filters-status-wrapper input.form-checkbox').once('filter-editor-status').each(function () {
      var $checkbox = $(this);
      var nameAttribute = $checkbox.attr('name');

      // The filter's checkbox has a name attribute of the form
      // "filters[<name of filter>][status]", parse "<name of filter>" from it.
      var filterID = nameAttribute.substring(8, nameAttribute.indexOf(']'));

      // Create a Backdrop.FilterStatus object to track the state (whether it's
      // active or not and its current settings, if any) of each filter.
      Backdrop.filterConfiguration.statuses[filterID] = new Backdrop.FilterStatus(filterID);
    });
  }
};

})(jQuery);
;
/**
 * @file
 * Attaches behavior for updating filter_html's settings automatically.
 */

(function ($) {

"use strict";

/**
 * Implement a live setting parser to prevent text editors from automatically
 * enabling buttons that are not allowed by this filter's configuration.
 */
if (Backdrop.filterConfiguration) {
  Backdrop.filterConfiguration.liveSettingParsers.filter_html = {
    getRules: function () {
      var currentValue = $('#edit-filters-filter-html-settings-allowed-html').val();
      var rules = [];
      var rule;

      // Build a FilterHTMLRule that reflects the hard-coded behavior that
      // strips all "style" attribute and all "on*" attributes.
      rule = new Backdrop.FilterHTMLRule();
      rule.properties.tags = ['*'];
      rule.properties.forbidden.attributes = ['style', 'on*'];
      rules.push(rule);

      // Build a FilterHTMLRule that reflects the current settings.
      rule = new Backdrop.FilterHTMLRule();
      var parseSetting = Backdrop.behaviors.filterFilterHtmlUpdating._parseSetting;
      rule.allow = true;
      rule.tags = parseSetting(currentValue);
      rules.push(rule);

      return rules;
    }
  };
}

Backdrop.behaviors.filterFilterHtmlUpdating = {

  // The form item contains the "Allowed HTML tags" setting.
  $allowedHTMLFormItem: null,

  // The description for the "Allowed HTML tags" field.
  $allowedHTMLDescription: null,

  // The user-entered tag list of $allowedHTMLFormItem.
  userTags: null,

  // The auto-created tag list thus far added.
  autoTags: null,

  // Track which new features have been added to the text editor.
  newFeatures: {},

  attach: function (context, settings) {
    var that = this;
    $(context).find('[name="filters[filter_html][settings][allowed_html]"]').once('filter-filter_html-updating').each(function () {
      that.$allowedHTMLFormItem = $(this);
      that.$allowedHTMLDescription = that.$allowedHTMLFormItem.closest('.form-item').find('.description');
      that.userTags = that._parseSetting(this.value);

      // Update the new allowed tags based on added text editor features.
      $(document)
          .on('backdropEditorFeatureAdded', function (e, feature) {
            that.newFeatures[feature.name] = feature;
            that._updateAllowedTags();
          })
          .on('backdropEditorFeatureModified', function (e, feature) {
            if (that.newFeatures.hasOwnProperty(feature.name)) {
              that.newFeatures[feature.name] = feature;
              that._updateAllowedTags();
            }
          })
          .on('backdropEditorFeatureRemoved', function (e, feature) {
            if (that.newFeatures.hasOwnProperty(feature.name)) {
              delete that.newFeatures[feature.name];
              that._updateAllowedTags();
            }
          });

      // When the allowed tags list is manually changed, update userTags.
      that.$allowedHTMLFormItem.on('change.updateUserTags', function () {
        var tagList = that._parseSetting(this.value);
        var userTags = [];
        for (var n in tagList) {
          if ($.inArray(tagList[n], that.autoTags) === -1) {
            userTags.push(tagList[n]);
          }
        }
        that.userTags = userTags;
      });
    });
  },

  /**
   * Updates the "Allowed HTML tags" setting and shows an informative message.
   */
  _updateAllowedTags: function () {
    // Update the list of auto-created tags.
    this.autoTags = this._calculateAutoAllowedTags(this.userTags, this.newFeatures);

    // Remove any previous auto-created tag message.
    this.$allowedHTMLDescription.find('.editor-update-message').remove();

    // If any auto-created tags: insert message and update form item.
    if (this.autoTags.length > 0) {
      this.$allowedHTMLDescription.append(Backdrop.theme('filterFilterHTMLUpdateMessage', this.autoTags));
      this.$allowedHTMLFormItem.val(this._generateSetting(this.userTags) + ' ' + this._generateSetting(this.autoTags));
    }
    // Restore to original state.
    else {
      this.$allowedHTMLFormItem.val(this._generateSetting(this.userTags));
    }
  },

  /**
   * Calculates which HTML tags the added text editor buttons need to work.
   *
   * The filter_html filter is only concerned with the required tags, not with
   * any properties, nor with each feature's "allowed" tags.
   *
   * @param Array userAllowedTags
   *   The list of user-defined allowed tags.
   * @param Object newFeatureRules
   *   A list of Backdrop.EditorFeature objects' rules, keyed by their name.
   *
   * @return Array
   *   A list of new allowed tags.
   */
  _calculateAutoAllowedTags: function (userAllowedTags, newFeatures) {
    var autoTags = [];
    for (var featureName in newFeatures) {
      if (newFeatures.hasOwnProperty(featureName)) {
        var newFeature = newFeatures[featureName];
        for (var ruleNumber = 0; ruleNumber < newFeature.rules.length; ruleNumber++) {
          var rule = newFeature.rules[ruleNumber];
          for (var tagNumber = 0; tagNumber < rule.tags.length; tagNumber++) {
            var tagName = rule.tags[tagNumber];
            if ($.inArray(tagName, userAllowedTags) === -1 && $.inArray(tagName, autoTags)) {
              autoTags.push(tagName);
            }
          }
        }
      }
    }
    return autoTags;
  },

  /**
   * Parses the value of this.$allowedHTMLFormItem.
   *
   * @param String setting
   *   The string representation of the setting. e.g. "<p> <br> <a>"
   *
   * @return Array
   *   The array representation of the setting. e.g. ['p', 'br', 'a']
   */
  _parseSetting: function (setting) {
    return setting.length ? setting.substring(1, setting.length - 1).split('> <') : [];
  },

  /**
   * Generates the value of this.$allowedHTMLFormItem.
   *
   * @param Array setting
   *   The array representation of the setting. e.g. ['p', 'br', 'a']
   *
   * @return Array
   *   The string representation of the setting. e.g. "<p> <br> <a>"
   */
  _generateSetting: function (tags) {
    return tags.length ? '<' + tags.join('> <') + '>' : '';
  }

};

/**
 * Theme function for the filter_html update message.
 *
 * @param Array tags
 *   An array of the new tags that are to be allowed.
 * @return
 *   The corresponding HTML.
 */
Backdrop.theme.prototype.filterFilterHTMLUpdateMessage = function (tags) {
  var html = '';
  var tagList = '<' + tags.join('> <') + '>';
  html += '<p class="editor-update-message">';
  html += Backdrop.t('Based on the text editor configuration, these tags have automatically been added: <strong>@tag-list</strong>.', {'@tag-list': tagList});
  html += '</p>';
  return html;
};

})(jQuery);
;
