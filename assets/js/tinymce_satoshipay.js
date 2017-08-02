(function() {
  tinymce.PluginManager.add('satoshipay', function(editor) {
    var attributes = {
      attachmentId: {
        name: 'attachment-id',
        wpName: 'id'
      },
      autoplay: {
        name: 'autoplay',
        title: 'Autoplay',
        defaultValue: false,
        unit: '',
        style: true
      },
      height: {
        name: 'height',
        title: 'Height',
        defaultValue: '100',
        unit: 'px',
        style: true
      },
      preview: {
        name: 'preview',
        title: 'Preview image',
        defaultValue: '',
        unit: '',
        style: true
      },
      width: {
        name: 'width',
        title: 'Width',
        defaultValue: '100',
        unit: 'px',
        style: true
      }
    };

    var types = [
      {
        name: 'audio',
        title: 'Audio',
        menuText: 'Add Paid Audio',
        libraryType: 'audio',
        attributes: [
          attributes.attachmentId,
          attributes.autoplay
        ]
      },
      {
        name: 'download',
        title: 'Download',
        menuText: 'Add Paid Download',
        attributes: [
          attributes.attachmentId
        ]
      },
      {
        name: 'image',
        title: 'Image',
        menuText: 'Add Paid Image',
        libraryType: 'image',
        attributes: [
          attributes.attachmentId,
          attributes.height,
          attributes.width,
          attributes.preview
        ]
      },
      {
        name: 'video',
        title: 'Video',
        menuText: 'Add Paid Video',
        libraryType: 'video',
        attributes: [
          attributes.attachmentId,
          attributes.height,
          attributes.width,
          attributes.autoplay,
          attributes.preview
        ]
      },
      {
        name: 'start',
        menuText: 'Insert Start Tag',
        noLibrary: true
      }
    ];

    types.forEach(function(type) {
      type.placeholderClass = 'satoshipay-tinymce-placeholder-' +  type.name;
      type.tagStart = '<!--satoshipay:' + type.name;
      type.tagEnd = '-->';
      type.tagTemplate = generateTagTemplate(type);
      type.placeholderTemplate = generatePlaceholderTemplate(type);
    });

    function replaceVariables(template, variables) {
      variables.forEach(function(variable) {
        template = template.replace(new RegExp('{{' + variable.key + '}}', 'g'), variable.value);
      });

      return template;
    }

    function getPlaceholderValues(type, html) {
      var values = [];

      if (!type.attributes) {
        return values;
      }

      type.attributes.forEach(function(attribute) {
        var re = new RegExp(' *data-' + attribute.name + ' *= *"(.*?)" *');
        if (attribute.style) {
          re = new RegExp(' *' + attribute.name + ' *: *(.*?) *' + attribute.unit + ' *;');
        }
        var matches = html.match(re);

        var attributeKey = attribute.name;
        var attributeValue = (matches) ? matches[1] : '';

        values.push({key: attributeKey, value: attributeValue});
      });

      return values;
    }

    function getValue(values, key) {
      var result = null;

      values.forEach(function(value) {
        if (value.key == key) {
          result = value.value;
        }
      });

      return result;
    }

    function getTagValues(type, html) {
      var values = [];

      if (!type.attributes) {
        return values;
      }

      type.attributes.forEach(function(attribute) {
        var re = new RegExp(' *' + attribute.name + ' *= *"(.*?)" *');
        var matches = html.match(re);

        var attributeKey = attribute.name;
        var attributeValue = (matches) ? matches[1] : attribute.defaultValue;

        values.push({key: attributeKey, value: attributeValue});
      });

      return values;
    }

    function findTags(html) {
      var tags = [];

      types.forEach(function(type) {
        html.replace(new RegExp(type.tagStart + '.*?' + type.tagEnd, 'g'), function(match) {
          var values = getTagValues(type, match);
          tags.push({
            match: match,
            placeholder: generatePlaceholder(type, values)
          });
        });
      });

      return tags;
    }

    function findPlaceholders(html) {
      var placeholders = [];

      types.forEach(function(type) {
        html.replace(new RegExp('<img[^>]*?class="' + type.placeholderClass + '"[^>]*?>', 'g'), function(match) {
          var values = getPlaceholderValues(type, match);
          placeholders.push({
            match: match,
            tag: generateTag(type, values)
          });
        });
      });

      return placeholders;
    }

    function getValuesFromLibraryItem(type, libraryItem) {
      var values = [];

      type.attributes.forEach(function(attribute) {
        var wpName = attribute.wpName ? attribute.wpName : attribute.name;

        if (libraryItem.hasOwnProperty(wpName)) {
          values.push({
            key: attribute.name,
            value: libraryItem[wpName]
          });
        }
      });

      return values;
    }

    function generatePlaceholder(type, values) {
      return replaceVariables(type.placeholderTemplate, values);
    }

    function generateTag(type, values) {
      return replaceVariables(type.tagTemplate, values);
    }

    function generateTagTemplate(type) {
      var attributes = '';

      if (type.attributes) {
        type.attributes.forEach(function(attribute) {
          attributes += ' ' + attribute.name + '="{{' + attribute.name + '}}"'
        });
      }

      return type.tagStart + attributes + type.tagEnd;
    }

    function generatePlaceholderTemplate(type) {
      var style = '';
      var dataAttributes = '';

      if (type.attributes) {
        type.attributes.forEach(function(attribute) {
          if (attribute.style) {
            style += attribute.name + ':{{' + attribute.name + '}}' + attribute.unit + ';';
          } else {
            dataAttributes += ' data-' + attribute.name + '="{{' + attribute.name + '}}"';
          }
        });
      }
      if (style != '') {
        style = ' style="' + style + '"';
      }

      var html = '<img src="' +
        tinymce.Env.transparentSrc +
        '" class="' +
        type.placeholderClass +
        '"' +
        style +
        dataAttributes +
        ' data-mce-resize="false" data-mce-placeholder="1" />';

      return html;
    }

    function generateModalContent(type, item) {
      var content = [
        {
          type: 'container',
          html: item.title + ' (' + item.mime + ', ' + item.filesizeHumanReadable + ')'
        }, {
          type: 'textbox',
          subtype: 'number',
          name: 'price',
          label: 'Price (satoshi)',
          value: item['price']
        }
      ];

      type.attributes.forEach(function(attribute) {
        if (attribute.name === 'autoplay') {
          content.push({
              type: 'checkbox',
              name: attribute.name,
              text: attribute.title,
              label: ' ',
              checked: (item[attribute.name] ? JSON.parse(item[attribute.name]) : attribute.defaultValue)
          });
        }

        if (attribute.name === 'preview') {
          content.push({
            type: 'container',
            label  : 'Preview image',
            layout: 'flex',
            direction: 'line',
            align: 'center',
            spacing: 5,
            items: [
              {
                type: 'textbox',
                name: 'preview',
                label: '',
                classes: 'satoshipay-preview-url',
                value: item['preview']
              }, {
                type: 'button',
                text: 'Choose ...',
                onclick: function(clickEvent) {
                  var mediaDialog = wp.media({
                    title: 'Select preview image',
                    library: {
                      type: 'image'
                    },
                    multiple: false,
                    button: {
                      text: 'Select'
                    },
                    previewElement: jQuery('input.mce-satoshipay-preview-url').get(0),
                  }).open();

                  mediaDialog.on('select', function(selectEvent) {
                    var image = mediaDialog.state().get('selection').first().toJSON();
                    var element = jQuery('input.mce-satoshipay-preview-url').get(0);
                    if (element) {
                      element.value = image.url
                    }
                  });
                }
              }
            ]
          });
        }

        if (!attribute.style || !item[attribute.name]) {
          return;
        }

        if ((attribute.name !== 'autoplay') && (attribute.name !== 'preview')) {
          content.push({
            type: 'textbox',
            name: attribute.name,
            label: (attribute.title ? attribute.title : attribute.name) + (attribute.unit ? ' (' + attribute.unit + ')' : ''),
            value: item[attribute.name]
          });
        }
      });

      return content;
    }

    function generateMenuItems() {
      var items = [];

      types.forEach(function(type) {
        var menuItem = {
          text: type.menuText
        };
        if (!type.noLibrary) {
          // Open media library
          menuItem.onclick = function() {
            var picker = wp.media({
              title: type.menuText,
              library: {type: type.libraryType},
              multiple: false,
              button: {text: 'Insert'}
            }).open();

            picker.on('select', function(e) {
              var item = picker.state().get('selection').first().toJSON();
              editor.windowManager.open({
                title: type.menuText,
                body: generateModalContent(type, item),
                onsubmit: submitData(type, item, editor.insertContent, editor)
              });
            });
          }
        } else {
          // Simply insert tag
          menuItem.onclick = function() {
            var content = editor.getContent({format: 'raw'});

            // Don't insert if tag already present
            if (content.indexOf(type.tagTemplate) !== -1) {
              return;
            }

            editor.insertContent(type.tagTemplate);
          }
        }

        items.push(menuItem);
      });

      return items;
    }

    function getTypeFromTag(tag) {
      var tagType = false;
      types.forEach(function(type) {
        if (tag.indexOf(type.tagStart) > -1) {
          tagType = type;
        }
      });

      return tagType;
    }

    function submitData(type, item, callback, scope) {
      return function(event) {
        item.autoplay = event.data.autoplay;
        item.height = parseInt(event.data.height);
        item.width = parseInt(event.data.width);
        item.price = parseInt(event.data.price);
        item.preview = event.data.preview;

        var ajaxData = new FormData();
        ajaxData.append('action', 'satoshipay-set-pricing');
        ajaxData.append('post_id', item.id);
        ajaxData.append('satoshipay_pricing_enabled', 1);
        ajaxData.append('satoshipay_pricing_satoshi', item.price);

        tinymce.util.XHR.send({
          url: ajaxurl,
          type: 'POST',
          data: ajaxData,
          success: function(ajaxResult) {
            var values = getValuesFromLibraryItem(type, item);
            var tag = generateTag(type, values);
            callback.call(scope, tag);
          }
        });
      }
    }

    function openEditModal(selection) {
      var tag = selection.getContent();
      var type = getTypeFromTag(tag);
      var currentValues = getTagValues(type, tag);
      var attachmentId = getValue(currentValues, 'attachment-id');

      wp.media.attachment(attachmentId).fetch().done(function(item) {
        item.height = getValue(currentValues, 'height');
        item.width = getValue(currentValues, 'width');
        item.autoplay = getValue(currentValues, 'autoplay');
        item.preview = getValue(currentValues, 'preview');

        editor.windowManager.open({
          title: 'Edit SatoshiPay ' + type.title,
          body: generateModalContent(type, item),
          onsubmit: submitData(type, item, selection.setContent, selection)
        });
      });
    }

    // Add menu button
    editor.addButton('satoshipay_start', {
      icon: 'icon satoshipay_tinymce_button_icon',
      text: 'SatoshiPay',
      type: 'menubutton',
      menu: generateMenuItems()
    });

    editor.addButton('satoshipay_placeholder_edit', {
      icon: 'dashicon dashicons-edit',
      tooltip: 'Edit',
      onclick: function() {
        openEditModal(editor.selection);
        editor.nodeChanged();
        editor.undoManager.add();
      }
    });

    editor.addButton('satoshipay_placeholder_remove', {
      icon: 'dashicon dashicons-no',
      tooltip: 'Remove',
      onclick: function() {
        editor.dom.remove(editor.selection.getNode());
        editor.nodeChanged();
        editor.undoManager.add();
      }
    });

    editor.once('preinit', function() {
      if (editor.wp && editor.wp._createToolbar) {
        toolbarFull = editor.wp._createToolbar([
          'satoshipay_placeholder_edit',
          'satoshipay_placeholder_remove'
        ]);
        toolbarRemove = editor.wp._createToolbar([
          'satoshipay_placeholder_remove'
        ]);
      }
    });

    editor.on('wptoolbar', function(e) {
      if (e.element.nodeName === 'IMG' && editor.wp.isPlaceholder(e.element) && e.element.className.indexOf('satoshipay-tinymce-placeholder-') > -1) {
        if (e.element.className.indexOf('satoshipay-tinymce-placeholder-start') > -1) {
          e.toolbar = toolbarRemove;
        } else {
          e.toolbar = toolbarFull;
        }
      }
    });

    // Set visual editor status bar hints
    editor.on('ResolveName', function(e) {
      types.forEach(function(type) {
        if (e.target.nodeName == 'IMG' && editor.dom.hasClass(e.target, type.placeholderClass)) {
          e.name = 'satoshipay-' + type.name;
        }
      });
    });

    // Replace comment tags with placeholder images
    editor.on('BeforeSetContent', function(e) {
      var tags = findTags(e.content);
      tags.forEach(function(tag) {
        e.content = e.content.replace(tag.match, tag.placeholder);
      });
    });

    // Replace placeholder images with comment tags
    editor.on('PostProcess', function(e) {
      var placeholders = findPlaceholders(e.content);
      placeholders.forEach(function(placeholder) {
        e.content = e.content.replace(placeholder.match, placeholder.tag);
      });
    });

  });
})();
