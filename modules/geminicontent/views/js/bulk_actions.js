$(document).ready(function() {
    // --- Zarządzanie Zaznaczaniem ---
    function updateBulkButtons() {
        const checkedCount = $('.js-product-checkbox:checked').length;
        $('#bulk-generate-all, #bulk-generate-description, #bulk-generate-seo').prop('disabled', checkedCount === 0);
    }

    $('#checkall-products').on('click', function() {
        $('.js-product-checkbox').prop('checked', $(this).prop('checked'));
        updateBulkButtons();
    });

    $('body').on('click', '.js-product-checkbox', function() {
        if ($('.js-product-checkbox:checked').length === $('.js-product-checkbox').length) {
            $('#checkall-products').prop('checked', true);
        } else {
            $('#checkall-products').prop('checked', false);
        }
        updateBulkButtons();
    });

    // --- Logika Kolejki i Przetwarzania Masowego ---
    let productQueue = [];
    let totalTasks = 0;
    let currentTask = 0;
    let failedTasks = [];

    function startBulkProcess(action) {
        productQueue = $('.js-product-checkbox:checked').map((_, el) => $(el).val()).get();
        if (productQueue.length === 0) {
            alert('Proszę zaznaczyć przynajmniej jeden produkt.');
            return;
        }

        totalTasks = productQueue.length;
        currentTask = 0;
        failedTasks = [];

        // Przygotuj interfejs
        $('#bulk-action-progress-wrapper').slideDown();
        $('.js-product-checkbox, #checkall-products, .btn').prop('disabled', true);
        
        processNextInQueue(action);
    }

    function processNextInQueue(action) {
        if (productQueue.length === 0) {
            finishBulkProcess();
            return;
        }

        currentTask++;
        const productId = productQueue.shift();
        const row = $('#product-row-' + productId);
        const originalActionsCell = row.find('td:last').html();

        // Aktualizuj UI
        updateProgressBar();
        $('#bulk-action-status-text').text(`Przetwarzanie produktu ${currentTask} z ${totalTasks} (ID: ${productId})`);
        row.css('background-color', '#fcf8e3'); // Żółte tło dla przetwarzanego
        row.find('td:last').html('<i class="icon-spinner icon-spin"></i>');

        $.ajax({
            url: gemini_ajax_url,
            type: 'POST',
            dataType: 'json',
            data: { 
                ajax: true,
                action: action, // np. 'GenerateAll', 'GenerateDescription'
                id_product: productId 
            },
            success: function(response) {
                if (response.success) {
                    row.css('background-color', '#dff0d8'); // Zielone tło
                    row.find('td:last').html('<i class="icon-check text-success"></i> Ukończono');
                } else {
                    failedTasks.push({ id: productId, message: response.message });
                    row.css('background-color', '#f2dede'); // Czerwone tło
                    row.find('td:last').html(`<i class="icon-times text-danger"></i> Błąd: ${response.message || 'Nieznany'}`);
                }
                setTimeout(() => processNextInQueue(action), 500); // Mała przerwa między zapytaniami
            },
            error: function() {
                failedTasks.push({ id: productId, message: 'Błąd serwera (AJAX)' });
                row.css('background-color', '#f2dede');
                row.find('td:last').html('<i class="icon-times text-danger"></i> Krytyczny błąd serwera');
                setTimeout(() => processNextInQueue(action), 500);
            }
        });
    }

    function updateProgressBar() {
        const percentage = Math.round((currentTask / totalTasks) * 100);
        $('#bulk-action-progress-bar').css('width', percentage + '%');
        $('#bulk-action-progress-text').text(percentage + '%');
    }

    function finishBulkProcess() {
        let statusMessage = `Zakończono! Przetworzono ${totalTasks} produktów.`;
        if (failedTasks.length > 0) {
            statusMessage += ` (${failedTasks.length} z błędami). Strona zostanie odświeżona za 10 sekund.`;
            console.error('Produkty, których nie udało się przetworzyć:', failedTasks);
        } else {
            statusMessage += ` Strona zostanie odświeżona za 5 sekund.`;
        }
        $('#bulk-action-status-text').text(statusMessage);
        
        setTimeout(() => location.reload(), failedTasks.length > 0 ? 10000 : 5000);
    }

    // Przypisanie akcji do przycisków
    $('#bulk-generate-all').on('click', () => startBulkProcess('GenerateAll'));
    $('#bulk-generate-description').on('click', () => startBulkProcess('GenerateDescription'));
    $('#bulk-generate-seo').on('click', () => startBulkProcess('GenerateSeo'));
});