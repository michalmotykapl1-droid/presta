(function($) {
  $(document).ready(function() {

    function showModal(id) {
        $('#geminiPreviewModal').data('current-id', id).modal('show');
    }

    function hideAllFields() {
        $('#gemini-description-fields, #gemini-seo-fields').hide();
    }

    function populateDescription(html) {
        if (html) {
            $('#gemini-description-fields').show();
            $('#gemini-preview-content').html(html);
            $('#gemini-source-content').val(html);
        }
    }

    function populateSeo(seo) {
        if (seo && Object.keys(seo).length > 0) {
            $('#gemini-seo-fields').show();
            $('#gemini-meta-title').val(seo.meta_title || '');
            $('#gemini-meta-description').val(seo.meta_description || '');
            $('#gemini-link-rewrite').val(seo.link_rewrite || '');
            $('#gemini-tags').val((seo.tags || []).join(', '));
        }
    }

    let originalButtonIconClass = '';

    function showPreloader(button) {
        originalButtonIconClass = $(button).find('i').attr('class');
        $(button).addClass('disabled').find('i').attr('class', 'icon-refresh icon-spin');
    }

    function hidePreloader(button) {
        $(button).removeClass('disabled').find('i').attr('class', originalButtonIconClass);
    }

    function updateTableRow(id, updatedFields) {
        const row = $(`#product-row-${id}`);
        if (!row.length) return;

        if (updatedFields.description) {
            const descCell = row.find('td').eq(5); // 6-ta kolumna
            descCell.html('<i class="icon-check text-success"></i>');
        }
        if (updatedFields.seo) {
            const seoCell = row.find('td').eq(6); // 7-ma kolumna
            seoCell.html('<i class="icon-check text-success"></i>');
        }

        const descIcon = row.find('td').eq(5).find('i').hasClass('icon-check');
        const seoIcon = row.find('td').eq(6).find('i').hasClass('icon-check');

        if (descIcon && seoIcon) {
            row.find('td').last().html(`<span class="text-success">Ukończono</span>`);
        }
    }

    // --- Obsługa przycisków dla promptu "Wszystko" ---
    $('#js-reset-all-in-one-prompt').on('click', function() {
        if (typeof default_all_in_one_prompt !== 'undefined' && default_all_in_one_prompt) {
            $('#gemini-prompt-all-in-one').val(default_all_in_one_prompt);
            showSuccessMessage('Domyślny prompt został przywrócony.');
        } else {
            showErrorMessage('Nie można załadować domyślnego promptu. Odśwież stronę.');
        }
    });

    // --- SEKCJA Z POPRAWKĄ: Obsługa przycisków dla promptu CSV ---
    $('#js-save-csv-prompt').on('click', function() {
        const prompt = $('#gemini-prompt-csv-process').val();
        $.ajax({
            url: `${gemini_ajax_url}&ajax=1&action=saveCsvPrompt`,
            method: 'POST',
            data: {
                prompt: prompt,
                action: 'saveCsvPrompt',
                ajax: true,
                token: prestashop.token // KLUCZOWA POPRAWKA: Dodanie tokenu bezpieczeństwa
            },
            dataType: 'json'
        }).done(function(resp) {
            if (resp.success) {
                showSuccessMessage(resp.message);
            } else {
                showErrorMessage(resp.message || 'Błąd zapisu promptu.');
            }
        }).fail(ajaxFailHandler);
    });

    $('#js-reset-csv-prompt').on('click', function() {
        $.ajax({
            url: `${gemini_ajax_url}&ajax=1&action=resetCsvPrompt`,
            method: 'GET',
            dataType: 'json'
        }).done(function(resp) {
            if (resp.success) {
                $('#gemini-prompt-csv-process').val(resp.prompt);
                showSuccessMessage('Domyślny prompt został przywrócony.');
            } else {
                showErrorMessage(resp.message || 'Nie można załadować domyślnego promptu.');
            }
        }).fail(ajaxFailHandler);
    });
    // --- KONIEC SEKCJI Z POPRAWKĄ ---

    $('.js-generate-description').on('click', function(e) {
      e.preventDefault();
      const button = this;
      const id = $(button).data('id-product');
      showPreloader(button);
      $.ajax({
        url: `${gemini_ajax_url}&ajax=1&action=generateDescription&id_product=${id}`,
        method: 'POST', dataType: 'json'
      }).done(function(resp) {
        hideAllFields();
        if (resp.success) {
          showModal(id);
          populateDescription(resp.data.description);
        } else {
          showErrorMessage(resp.message || 'Błąd generowania opisu');
        }
      }).fail(ajaxFailHandler).always(() => hidePreloader(button));
    });

    $('.js-generate-seo').on('click', function(e) {
      e.preventDefault();
      const button = this;
      const id = $(button).data('id-product');
      showPreloader(button);
      $.ajax({
        url: `${gemini_ajax_url}&ajax=1&action=generateSeo&id_product=${id}`,
        method: 'POST', dataType: 'json'
      }).done(function(resp) {
        hideAllFields();
        if (resp.success) {
          showModal(id);
          populateSeo(resp.data);
        } else {
          showErrorMessage(resp.message || 'Błąd generowania SEO');
        }
      }).fail(ajaxFailHandler).always(() => hidePreloader(button));
    });

    $('.js-generate-all').on('click', function(e) {
      e.preventDefault();
      const button = this;
      const id = $(button).data('id-product');
      showPreloader(button);
      $.ajax({
        url: `${gemini_ajax_url}&ajax=1&action=generateAll&id_product=${id}`,
        method: 'POST', dataType: 'json'
      }).done(function(resp) {
        hideAllFields();
        if (resp.success) {
          showModal(id);
          populateDescription(resp.data.creative);
          populateSeo(resp.data.seo);
        } else {
          showErrorMessage(resp.message || 'Błąd generowania wszystkich treści');
        }
      }).fail(ajaxFailHandler).always(() => hidePreloader(button));
    });

    $('#save-gemini-description').on('click', function() {
      const id = $('#geminiPreviewModal').data('current-id');
      const payload = {
          description: '',
          seo: {}
      };

      const updatedFields = {
          description: false,
          seo: false
      };

      if ($('#gemini-description-fields').is(':visible')) {
          payload.description = $('#gemini-source-content').val();
          updatedFields.description = true;
      }

      if ($('#gemini-seo-fields').is(':visible')) {
          payload.seo = {
              meta_title: $('#gemini-meta-title').val(),
              meta_description: $('#gemini-meta-description').val(),
              link_rewrite: $('#gemini-link-rewrite').val(),
              tags: $('#gemini-tags').val().split(',').map(tag => tag.trim()).filter(Boolean)
          };
          updatedFields.seo = true;
      }

      $.ajax({
        url: `${gemini_ajax_url}&ajax=1&action=saveDescription&id_product=${id}`,
        method: 'POST',
        data: payload,
        dataType: 'json'
      }).done(function(resp) {
        if (resp.success) {
          showSuccessMessage(resp.message);
          $('#geminiPreviewModal').modal('hide');
          updateTableRow(id, updatedFields);
        } else {
          showErrorMessage(resp.message || 'Błąd zapisu');
        }
      }).fail(ajaxFailHandler);
    });

    function ajaxFailHandler(xhr) {
        console.error('AJAX error', xhr);
        showErrorMessage('Wystąpił krytyczny błąd serwera. Sprawdź logi błędów PrestaShop po więcej informacji.');
    }

    function showErrorMessage(message) {
        if(typeof $ !== 'undefined' && $.growl) {
            $.growl.error({ title: "Błąd", message: message, duration: 5000 });
        } else {
            alert(message);
        }
    }

    function showSuccessMessage(message) {
        if(typeof $ !== 'undefined' && $.growl) {
            $.growl.notice({ title: "Sukces", message: message, duration: 3000 });
        } else {
            alert(message);
        }
    }

  });
})(jQuery);