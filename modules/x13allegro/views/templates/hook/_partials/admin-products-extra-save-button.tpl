{if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '>=')}
    <div class="panel-footer">
        <hr />
        <button type="submit" name="submitAddProductXAllegro" class="btn btn-primary float-right"> {l s='Zapisz ustawienia Allegro' mod='x13allegro'}</button>
    </div>
{else}
    <div class="panel-footer">
        <a href="{$link->getAdminLink('AdminProducts')|escape:'html':'UTF-8'}" class="btn btn-default"><i class="process-icon-cancel"></i> {l s='Cancel'}</a>
        <button type="submit" name="submitAddproduct" class="btn btn-default pull-right"><i class="process-icon-loading"></i> {l s='Save'}</button>
        <button type="submit" name="submitAddproductAndStay" class="btn btn-default pull-right"><i class="process-icon-loading"></i> {l s='Save and stay'}</button>
    </div>
{/if}
