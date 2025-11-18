<div style="text-align: center; font-size: 14px;">
    <div id="allegroAuthStart">
        <p style="margin: 0 0 20px;">{l s='Powiąż konto z serwisem Allegro aby ukończyć proces autoryzacji.' mod='x13allegro'}</p>
        <a id="allegroAuthButton" href="{$accountAuthUrl}" data-account="{$accountId}" target="_blank" rel="nofollow" class="button btn btn-warning">{l s='Powiąż konto Allegro' mod='x13allegro'}</a>
    </div>

    <div id="allegroAuthProcess" style="display: none;">
        <i class="icon-cog" style="font-size: 24px; animation: fa-spin 2s infinite linear;"></i>
        <p>{l s='oczekiwanie na potwierdzenie...' mod='x13allegro'}</p>
    </div>

    <div id="allegroAuthSuccess" style="display: none;">
        <i class="icon-check" style="font-size: 24px; color: #60ba68"></i>
        <p style="margin: 0 0 20px;">
            <span style="color: #60ba68">{l s='Konto zostało pomyślnie zautoryzowane' mod='x13allegro'}</span><br>
            {l s='Rynek bazowy' mod='x13allegro'}: <span id="allegroAuthMarketplace"></span>
        </p>

        <div id="allegroAuthConfiguration"></div>

        <a id="allegroAuthButtonFinish" href="{$redirectUrl}&conf=4" class="button btn btn-success" style="display: none;">{l s='Zakończ' mod='x13allegro'}</a>
        <p style="font-size: 12px; font-style: italic; margin: 20px 0 0 0;">
            {capture configurationUrl}<a href="{$configurationUrl}" target="_blank">{/capture}
            {l s='Pamiętaj, że w każdej chwili możesz zmienić indywidualne ustawienia dla tego konta w [1]Konfiguracji modułu[/1]' tags=[$smarty.capture.configurationUrl] mod='x13allegro'}
        </p>
    </div>

    <div id="allegroAuthError" style="display: none;">
        <i class="icon-remove" style="font-size: 24px; color: #f73442"></i>
        <p id="allegroAuthErrorMsg" style="margin: 0 0 20px; color: #f73442"></p>
        <a href="{$redirectUrl}" class="button btn btn-default">{l s='Zakończ' mod='x13allegro'}</a>
    </div>
</div>
