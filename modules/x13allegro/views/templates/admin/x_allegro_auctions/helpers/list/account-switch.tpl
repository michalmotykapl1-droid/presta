{if !empty($allegroAccounts)}
    <form id="account_switch_form" class="defaultForm clearfix bootstrap" action="#" method="post">
        <div class="panel col-lg-12" x-name="allegro_account">
            <fieldset>
                <div class="panel-heading">
                    <img alt="" src="../modules/x13allegro/img/AdminXAllegroMain.png" /> {l s='Zmień konto Allegro' mod='x13allegro'}
                </div>

                <div class="row">
                    <div class="form-wrapper col-sm-4 col-lg-3">
                        <label for="id_allegro_account" class="control-label">{l s='Wybierz konto' mod='x13allegro'}</label>
                        <div class="margin-form">
                            <select id="id_xallegro_account" class="change_allegro_account fixed-width-xxl" name="id_xallegro_account" onchange="javascript:location.href=this.value;">
                                {foreach from=$allegroAccounts item="allegroAccount"}
                                    <option value="{$allegroAccount.changeUrl}" {if $allegroAccount.id == $currentAccountId}selected="selected"{/if}>{$allegroAccount.username}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>

                    <div class="col-sm-4 col-lg-3">
                        {if $currentAccount}
                            <p><strong>{l s='Zalogowane konto' mod='x13allegro'}:</strong></p>
                            <p>
                                <strong>{if isset($currentAccount->firstName)}{$currentAccount->firstName}{/if} {if isset($currentAccount->lastName)}{$currentAccount->lastName}{/if}</strong> <i>({$currentAccount->login})</i><br>
                                {if isset($currentAccount->company) && isset($currentAccount->company->name)}{l s='Firma' mod='x13allegro'}: {$currentAccount->company->name}{/if}<br/>
                                {if $baseMarketplace}{l s='Marketplace' mod='x13allegro'}: {$baseMarketplace}{/if}
                            </p>
                        {/if}
                    </div>
                </div>
            </fieldset>
        </div>
    </form>
{/if}
