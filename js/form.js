(function ($, Drupal) {

  "use strict";

  /**
   * Registers behaviours related to the flexible layout form.
   */
  Drupal.behaviors.FileBrowserView = {
    attach: function (context) {
      function getAddButton ($element, type) {
        var $add = $('<a href="#" class="button"></a>');
        $add.addClass('flb-add-' + type);
        $add.text(Drupal.t('Add ' + (type === 'row' ? 'Column' : 'Row')));
        $add.click(function (event) {
          event.preventDefault();
          type === 'row' ? addColumn($element) : addRow($element);
        });
        return $add;
      }

      function getNameField ($element) {
        var $container = $('<div class="form-item" />');
        var $label = $('<label for="flb-name">' + Drupal.t('Name') + ':</label>');
        var $name = $('<input class="form-text" name="flb-name" type="text" />');
        $name.val($element.children('.name').text());
        $name.keyup(function () {
          $element.children('.name').text($(this).val());
        });
        return $container.append($label).append($name);
      }

      function getClasses ($element) {
        return $element.attr('class').replace(/(flb\-|ui\-)[^\s]+/g, '').trim();
      }

      function getClassesField ($element, type) {
        var $container = $('<div class="form-item" />');
        var $label = $('<label for="flb-classes">' + Drupal.t('Classes') + '</label>');
        var $classes = $('<input class="form-text" name="flb-classes" type="text" />');
        $classes.val(getClasses($element));
        $classes.keyup(function () {
          $element.attr('class', $classes.val() + ' flb-' + type);
        });
        return $container.append($label).append($classes);
      }

      function getRemoveButton ($element) {
        var $remove = $('<a href="#" class="button alert">' + Drupal.t('Remove') + '</a>');
        $remove.click(function (event) {
          event.preventDefault();
          $element.remove();
          $(this).parent().dialog('close');
        });
        return $remove;
      }

      function bindDialog ($element) {
        var type = $element.hasClass('flb-row') ? 'row' : 'column';
        $element.click(function (event) {
          event.preventDefault();
          event.stopPropagation();
          var $dialog = $('<div class="flb-dialog"></div>');
          var depth  = $element.parents('.flb-row').length;
          $dialog.append(getNameField($element));
          $dialog.append(getClassesField($element, type));

          if (depth > 0) {
            $dialog.append(getRemoveButton($element));
          }
          if (depth < 2) {
            $dialog.append(getAddButton($element, type));
          }
          var modal = Drupal.dialog($dialog, {
            title: 'Configure ' + type,
            minWidth: 320
          });
          modal.showModal();

        });
      }

      function addColumn ($row, settings) {
        var $column = $('<div class="flb-column columns"><div class="name">Column</div></div>');
        if (settings) {
          $column.addClass(settings.classes);
          $column.children('.name').text(settings.name);
          $column.attr('data-machine-name', settings.machine_name);
        }
        else {
          var num = $row.closest('.flb').find('.flb-column').length + 1;
          $column.attr('data-machine-name', 'column_' + num);
        }
        bindDialog($column);
        if (!settings) {
          $column.addClass('small-3');
        }
        $row.append($column);
        return $column;
      }

      function addRow ($column, settings) {
        var $row = $('<div class="flb-row row"><div class="name">Row</div></div>');
        if (settings) {
          $row.addClass(settings.classes);
          $row.children('.name').text(settings.name);
          $row.attr('data-machine-name', settings.machine_name);
        }
        else {
          var num = $column.closest('.flb').find('.flb-row').length + 1;
          $row.attr('data-machine-name', 'row_' + num);
        }
        $row.sortable({
          connectWith: '.flb-row',
          items: '> .flb-column',
          tolerance: 'pointer'
        });
        bindDialog($row);
        $column.append($row);
        return $row;
      }

      function serializeLayout ($element) {
        var machine_name = $element.attr('data-machine-name');
        var serializedLayout = {};

        serializedLayout[machine_name] = {
          name: $element.children('.name').text(),
          classes: getClasses($element),
          children: {},
          type: $element.hasClass('flb-row') ? 'row' : 'column'
        };
        $element.children('.flb-column,.flb-row').each(function () {
          $.extend(serializedLayout[machine_name].children, serializeLayout($(this)));
        });
        return serializedLayout;
      }

      function unserializeLayout (settings, $element) {
        for (var i in settings) {
          settings[i].machine_name = i;
          var $child = settings[i].type === 'row' ? addRow($element, settings[i]) : addColumn($element, settings[i]);

          if (!$.isEmptyObject(settings[i].children)) {
            unserializeLayout(settings[i].children, $child);
          }
        }
      }

      function init ($container) {
        $container.addClass('flb');
        var $jsonField = $container.parent().find('.flexible-layout-json-field');
        var observer = new MutationObserver(function () {
          var serializedLayout = serializeLayout($container.children('.flb-row').first());
          $jsonField.val(JSON.stringify(serializedLayout));
        });
        observer.observe($container[0], {
          attributes: true,
          childList: true,
          characterData: true,
          subtree: true
        });
        var layout = JSON.parse($jsonField.val());
        if (typeof layout === 'object' && layout.row_1.name) {
          unserializeLayout(layout, $container);
        }
        else {
          addRow($container);
        }
      }

      var $container = $('.flexible-layout-container', context).once('flexible-layout-init');
      if ($container.length) {
        init($container);
      }
    }
  };

}(jQuery, Drupal));
