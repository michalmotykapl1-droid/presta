<div class="modal" id="fees_modal">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header x13allegro-modal-header">
                <button type="button" class="close x13allegro-modal-close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="x13allegro-modal-title">{l s='Jak integracja od X13 dolicza prowizje?' mod='x13allegro'}</h4>
            </div>
            <div class="modal-body x13allegro-modal-body">
                <p>
                    {l s='Od wersji 6.6.0 (sierpień 2021) dodaliśmy możliwość automatycznego dodawania opłat do wystawianych ofert.'}<br/><br/>
                    Doliczamy zarówno <strong>prowizję</strong> od sprzedaży jak i <strong>opłaty za promowanie</strong>.<br/>
                    Przy wystawianiu, do obliczenia prowizji oprócz ceny produktu doliczamy również koszt promowania (podzielony przez 10).<br/><br/>

                    Przykład obliczania kwoty, na podstawie produktu za <strong>100 zł brutto</strong>, w kategorii z prowizją 9%:
                </p>
                <br/>
                <h4>a) oferta <strong>bez opcji promowania</strong></h4>
                <table class="table table-bordered table-striped">
                    <tr>
                        <td>Kwota do obliczeń</td>
                        <td><strong>100 zł</strong></td>
                    </tr>
                    <tr>
                        <td>Produkt zostanie wystawiony za</td>
                        <td><strong>109,81 zł</strong></td>
                    </tr>
                    <tr>
                        <td>Realnie zarobiona kwota z Allegro</td>
                        <td><strong>99,92 zł</strong></td>
                    </tr>
                </table>
                <br/>
                <h4>b) oferta <strong>z wyróżnieniem - przykład z tabelką</strong></h4>
                <table class="table table-bordered table-striped">
                    <tr>
                        <td>Kwota do obliczeń</td>
                        <td><strong>101,90zł</strong>
                            <br/>100 zł produkt + 1,90 zł (19 zł/10 za wyróznienie)</td>
                    </tr>
                    <tr>
                        <td>Produkt zostanie wystawiony za</td>
                        <td><strong>120,49 zł</strong></td>
                    </tr>
                    <tr>
                        <td>Realnie zarobiona kwota z Allegro</td>
                        <td><strong>99,23 zł</strong></td>
                    </tr>
                </table>
                <br/>
                <h4>c) oferta <strong>z wyróżnieniem i promowaniem w dziale</strong></h4>
                <table class="table table-bordered table-striped">
                    <tr>
                        <td>Kwota do obliczeń</td>
                        <td><strong>104,80zł</strong>
                            <br/>100 zł produkt + 1,90 zł (19 zł/10 za wyróznienie) + 2,90 (29zł/10 za promowanie)</td>
                    </tr>
                    <tr>
                        <td>Produkt zostanie wystawiony za</td>
                        <td><strong>123,92 zł</strong></td>
                    </tr>
                    <tr>
                        <td>Realnie zarobiona kwota z Allegro</td>
                        <td><strong>99,59 zł</strong></td>
                    </tr>
                </table>
                <br/><br/>
                <p>
                    Pomimo obliczenia prowizji od sprzedaży, prowizji od sprzedaży oferty wyróżnionej i kosztów wystawienia,
                    realna kwota bez opłat Allegro będzie mimimalnie różna od zakładanej.
                    Sposób liczenia jaki jest obecnie dostępny w naszej integracji ma na celu maksymalne ograniczenie wpływu opłat na Państwa zysk.
                </p>
            </div>
            <div class="modal-footer x13allegro-modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{l s='Zamknij' mod='x13allegro'}</button>
            </div>
        </div>
    </div>
</div>
