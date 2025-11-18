{if !empty($allegroAccounts)}
    <div id="x13gpsrAllegro">
        <div style="margin-bottom: 15px;">
            <a href="#" id="x13gpsrAllegroRefresh" class="btn btn-default"><i class="icon-refresh"></i> {l s='Odśwież dane Allegro' mod='x13allegro'}</a>
        </div>

        <div id="x13gpsrAllegroWrapper">
            <ul class="nav nav-tabs" id="tabX13GPSRAllegro">
                {foreach from=$allegroAccounts item=allegroAccount name=allegroAccount}
                    <li{if $smarty.foreach.allegroAccount.index == 0} class="active"{/if}>
                        <a href="#{$allegroAccount.accountId}" aria-controls="{$allegroAccount.accountId}" role="tab" data-toggle="tab">{$allegroAccount.accountUsername}</a>
                    </li>
                {/foreach}
            </ul>

            <div role="tabpanel" class="tab-content panel" id="tabContentX13GPSRAllegro">
                {foreach from=$allegroAccounts item=allegroAccount name=allegroAccount}
                    <div class="tab-pane{if $smarty.foreach.allegroAccount.index == 0} active{/if}" id="{$allegroAccount.accountId}">
                        {if $allegroAccount.accountLogged}
                            {block x13gpsrFormModifier}{/block}
                        {else}
                            <div class="alert alert-info">
                                {l s='Autoryzuj konto, aby zmienić te ustawienia' mod='x13allegro'}
                            </div>
                        {/if}
                    </div>
                {/foreach}
            </div>
        </div>
    </div>

    <script type="text/javascript">
        $(function() {
            chosenInit();

            $(document).on('click', '#x13gpsrAllegroRefresh', function (e) {
                e.preventDefault();

                var $button = $(this);
                var $wrapper = $(this).closest('#x13gpsrAllegro').find('#x13gpsrAllegroWrapper');
                var activeTabId = $wrapper.find('#tabX13GPSRAllegro li.active > a').attr('aria-controls');

                $button.prop('disabled', true).addClass('disabled');
                $wrapper.slideUp();

                var ajaxData = {
                    ajax: 1,
                    action: 'refreshFormModifier',
                    formType: '{$formType}',
                    formObjectId: {$formObjectId|intval}
                };

                $.post('{$gpsrAdapterUrl}', ajaxData)
                    .then(function (response) {
                        response = JSON.parse(response);

                        var $formModifier = $(response.formModifier);
                        $wrapper.html($formModifier.find('#x13gpsrAllegroWrapper').html());
                        $wrapper.find('#tabX13GPSRAllegro li > a[aria-controls="' + activeTabId + '"]').trigger('click');

                        $wrapper.slideDown();
                        $button.prop('disabled', false).removeClass('disabled');

                        chosenInit();
                    });
            });

            $(document).on('click', '.x13gpsr-allegro-create', function (e) {
                e.preventDefault();

                var $button = $(this);
                $button.prop('disabled', true).addClass('disabled');

                var ajaxData = {
                    ajax: 1,
                    action: 'createGPRS',
                    formType: '{$formType}',
                    formObjectId: {$formObjectId|intval},
                    allegroAccountId: $button.closest('.tab-pane').attr('id')
                };

                $.post('{$gpsrAdapterUrl}', ajaxData)
                    .then(function (response) {
                        response = JSON.parse(response);

                        if (!response.success) {
                            showErrorMessage(response.message);
                        } else {
                            showSuccessMessage(response.message);
                            $button.closest('#x13gpsrAllegro').find('#x13gpsrAllegroRefresh').trigger('click');
                        }

                        $button.prop('disabled', false).removeClass('disabled');
                    });
            });

            function chosenInit() {
                $('#tabContentX13GPSRAllegro').find('select').chosen({
                    width: '100%'
                });
            }
        });
    </script>
{else}
    <div class="alert alert-info">
        {l s='Brak aktywnych kont Allegro' mod='x13allegro'}
    </div>
{/if}
