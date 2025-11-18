{extends file="helpers/list/list_footer.tpl"}

{block name="after"}
    <div class="alert alert-info">
        <p><b>Pamiętaj, że w niektórych sytuacjach autoryzacja może wygasnąć.</b></p>
        <p>Dzieje się tak w przypadku:</p>
        <ul>
            <li>wylogowania się ze wszystkich urządzeń (np. poprzez zakładkę "Logowanie i hasło" w panelu Allegro)</li>
            <li>zmiany hasła</li>
            <li>zmiany adresu e-mail</li>
            <li>blokady sprzedaży</li>
            <li>przekroczenia liczby aktywnych sesji (max. 20 otwartych sesji dla jednego użytkownika)</li>
            <li>braku aktywności na zautoryzowanym koncie przez 3 miesiące</li>
        </ul>
    </div>

    <script>
        $(document).ready(function() {
            var XAllegro = new X13Allegro();
            XAllegro.accountAuth();
        });
    </script>

    <div class="modal xaccount-authorization-modal" id="account_authorization_modal" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document" style="width: 650px;">
            <div class="modal-content">
                <div class="modal-header x13allegro-modal-header">
                    <button type="button" class="close x13allegro-modal-close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="x13allegro-modal-title">{l s='Autoryzacja konta' mod='x13allegro'}</h4>
                    <h6 class="x13allegro-modal-title-small"><span></span></h6>
                </div>
                <div class="modal-body x13allegro-modal-body"></div>
                <div class="modal-footer x13allegro-modal-footer"></div>
            </div>
        </div>
    </div>
{/block}
