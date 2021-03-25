/**
 * Prismatic Links plugin for Craft CMS
 *
 * PrismaticLinksField Field JS
 *
 * @author    Prismatic Bytes
 * @copyright Copyright (c) 2021 Prismatic Bytes
 * @link      https://prismaticbytes.com
 * @package   PrismaticLinks
 * @since     1.0.0
 */

;(function ($, window, document, undefined) {

  var pluginName = "PrismaticLinks",
    defaults = {};

  // Plugin constructor
  function Plugin(element, options) {
    this.element = element;

    this.options = $.extend({}, defaults, options);

    this._defaults = defaults;
    this._name = pluginName;

    this.init();
  }

  Plugin.prototype = {

    init: function (id) {
      var _this = this;

      $(function () {
        console.log('> _this', _this);

        var $inputField = $(_this.element).find('.prismaticlinks-field-left input');
        var $dataField = $('#' + _this.options.namespace);

        var $data = $dataField.val() ? JSON.parse($dataField.val()) : null;

        var $previewEl = $(_this.element).find('.prismaticlinks-preview');
        var $loadingEl = $(_this.element).find('.prismaticlinks-loading');
        var $errorEl = $(_this.element).find('.prismaticlinks-error');
        var $refreshEl = $(_this.element).find('.prismaticlinks-refresh');
        var $imageSelectorEl = $(_this.element).find('.prismaticlinks-image-selector');

        var debounce;

        $inputField.on('change', function () {
          clearTimeout(debounce);
          LoadPreview($inputField.val());
        });

        $inputField.on('keyup', function () {
          clearTimeout(debounce);
          debounce = setTimeout(function () {
            LoadPreview($inputField.val());
          }, 666);
        });

        $refreshEl.find('[toggle-prismaticlinks-refresh]').on('click', function () {
          LoadPreview($inputField.val());
        });

        if ($inputField.val()) {
          $refreshEl.show();
        }

        function renderImageSelector ($data) {
          if ($data.images.length < 2) {
            $data.image_offset = 1;
            $imageSelectorEl.hide();
          } else {
            $imageSelectorEl.show();
          }

          $data.image_offset = $data.image_offset || 0;

          $imageSelectorEl.find('[counter]').html(($data.image_offset + 1) + "/" + $data.images.length);

          $imageSelectorEl.find('[toggle-right]').prop('disabled', false);
          $imageSelectorEl.find('[toggle-left]').prop('disabled', false);

          if ($data.image_offset >= ($data.images.length - 1)) {
            $imageSelectorEl.find('[toggle-right]').prop('disabled', true);
          }
          if ($data.image_offset <= 0) {
            $imageSelectorEl.find('[toggle-left]').prop('disabled', true);
          }
        }

        $imageSelectorEl.find('[toggle-left]').unbind('click').on('click', function (e) {
          if ($(this).prop('disabled')) {
            return;
          }
          $data.image_offset--;
          renderImageSelector($data);

          if ($data.images[$data.image_offset]) {
            $data.image = $data.images[$data.image_offset];
            $previewEl.find('.prismaticlinks-image img').attr("src", $data.images[$data.image_offset]);
          }

          $dataField.val(JSON.stringify($data));
        });

        $imageSelectorEl.find('[toggle-right]').unbind('click').on('click', function (e) {
          if ($(this).prop('disabled')) {
            return;
          }
          $data.image_offset++;
          $dataField.val(JSON.stringify($data));
          renderImageSelector($data);

          if ($data.images[$data.image_offset]) {
            $data.image = $data.images[$data.image_offset];
            $previewEl.find('.prismaticlinks-image img').attr("src", $data.images[$data.image_offset]);
          }

          $dataField.val(JSON.stringify($data));
        });

        renderImageSelector($data);

        function LoadPreview (url) {
          $refreshEl.show();

          if (!url) {
            $dataField.val('');
            $(_this.element).find('.prismaticlinks-field-right').hide();
            return;
          } else {
            $(_this.element).find('.prismaticlinks-field-right').show();
          }

          $errorEl.hide();
          $previewEl.hide();

          $errorEl.html('');
          $previewEl.html('');

          $loadingEl.show();

          var request = $.ajax({
            url: "/actions/prismatic-links/default/parse",
            method: "GET",
            data: {
              url: url
            },
            dataType: "json",
            error: function (resp) {
              $loadingEl.hide();
              $errorEl.show();

              $data.image_offset = 1;
              $data.image = "";
              $data.images = [];
              $data.title = "";
              $data.description = "";

              renderImageSelector($data);

              if (resp && resp.responseJSON && resp.responseJSON.error) {
                $errorEl.html(resp.responseJSON.error);
              } else {
                $errorEl.html('An unknown error occurred');
              }

              $dataField.val(JSON.stringify({url: url}));
            },
            success: function (data) {
              console.log('> data', data);

              $data = data;

              renderImageSelector($data);

              $dataField.val(JSON.stringify($data));

              // =========

              var request = $.ajax({
                url: "/actions/prismatic-links/default/preview",
                method: "GET",
                data: {
                  data: JSON.stringify(data)
                },
                dataType: "html",
                success: function (data) {
                  console.log('> html', data);

                  $(_this.element).find('.prismaticlinks-preview').html(data);

                  $previewEl.show();
                  $loadingEl.hide();

                }
              });

              // =========

            }
          });
        }



      });
    }
  };

  // A really lightweight plugin wrapper around the constructor,
  // preventing against multiple instantiations
  $.fn[pluginName] = function (options) {
    return this.each(function () {
      if (!$.data(this, "plugin_" + pluginName)) {
        $.data(this, "plugin_" + pluginName,
          new Plugin(this, options));
      }
    });
  };

})(jQuery, window, document);
