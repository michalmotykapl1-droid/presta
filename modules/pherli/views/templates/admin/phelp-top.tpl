{**
* NOTICE OF LICENSE
*
* This file is licenced under the Software License Agreement.
* With the purchase or the installation of the software in your application
* you accept the licence agreement.
*
* You must not modify, adapt or create derivative works of this source code
*
*  @author    PrestaHelp.com
*  @copyright 2019 PrestaHelp
*  @license   LICENSE.txt
*}

<div class="view">
    <div class="box-auto">
        <div class="container-auto">
            <div class="container-row">
                <div class="branding">
                    <img src="{$moduleAssets}img/Branding.png" alt="Prestahelp Branding">
                </div>
                <div class="inclusion">
                    <span class="inclusion-paczkomaty">{$moduleName}</span>
                    <span class="inclusion-inpost">({$moduleNameInfo}, v {$moduleVersion})</span>
                </div>
                <div class="buttons">
                    {if $lastestVersion}
                        <div class="btn-prestahelp green--bg">
                            <img src="{$moduleAssets}img/arrow_down.png" alt="Prestahelp Arrow">Twój moduł jest aktualny.
                            <a href="https://www.prestahelp.com/pl/glowna/348-modulowy-system-opisu-produktu-dla-prestashop-16-i-17.html" target="_blank">zostaw opinię <img src="{$moduleAssets}img/arrow_right.png" alt="Prestahelp Arrow"></a>
                        </div>
                    {else}
                        <div class="btn-prestahelp red--bg">
                            <img src="{$moduleAssets}img/close.png" alt="Prestahelp Close">Twój moduł jest nieaktualny.
                            (aktualna wersja {$currentModuleVersion})
                            <a href="{$updateLink}" target="_blank">Aktualizuj moduł <img src="{$moduleAssets}img/arrow_right-red.png" alt="Prestahelp Arrow"></a>
                        </div>
                    {/if}
                    <a id="chengelog-btn" href="#changelog-btn" class="chengelog-btn">changelog <img src="{$moduleAssets}img/chengelog_down.png" alt="Prestahelp Arrow"></a>
                </div>
                <div class="description">
                    {if $licence->time == 10}
                        <span class="successed">Licencja na moduł jest dożywotnia</span>
                    {else}
                        <span class="successes">Licencja{if $licence->time == 1} <b>(testowa)</b>{elseif $licence->time == 3} <b>(TMP)</b>{/if} na moduł ważna do: <b>{$licence_date}</b> (Wygasa za {$licence->date_expire_left->left})</span>
                    {/if}
                    {if $licence->time != 3 && $licence->time != 1}
                        {if $activeted.licence->licence_update == 1}
                            {if $licence->time_upd == 10}
                                <span class="successed">Aktualizacje modułu są dożywotnie</span>
                            {else}
                                <span class="successed">Aktualizacje modułu ważne do: <b>{$updated_date}</b> (Wygasa za {$licence->date_expire_update_left->left})</span>
                            {/if}
                        {else}
                            <span class="dangered">Licencja na aktualizacje modułu wygasła.</span>
                        {/if}
                        {if $activeted.licence->licence_support == 1}
                            {if $licence->time_sup == 10}
                                <span class="successed">Support jest dożywotni.</span>
                            {else}
                                <span class="successed">Support ważny do: <b>{$support_date}</b> (Wygasa za {$licence->date_expire_support_left->left})</span>
                            {/if}
                        {else}
                            <span class="dangered">Licencja na support modułu wygasła.</span>
                        {/if}
                    {/if}
                </div>
            </div>
            {if $activeted.licence->licence_support == 1}
            <div class="container-row middle">
                <div class="buttons">
                    <div class="support-btn">
                        <a href="http://helpdesk.prestahelp.com/client.php" target="_blank">
                            <div class="support-row img-row"><img src="{$moduleAssets}img/help.png" alt="Prestahelp Help"></div>
                            <div class="support-row">
                                <span>Masz problem <grey-color>z modułem?</grey-color></span>
                                <span class="light">Zgłoś go nam na <pom-color>HELP</pom-color><grey-color>DESK</grey-color>!</span>
                                <p>Wszystkie problemy prosimy zgłaszać tylko i wyłącznie przez system HelpDesk, nie rozwiązujemy problemów telefonicznie.</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            {/if}
        </div>
    </div>
</div>
