{$isModernLayout = version_compare($smarty.const._PS_VERSION_, '1.7.7.0', '>=')}

<div id="xallegro_product_settings_panel" class="panel bootstrap">
    <input type="hidden" name="x13allegro_product_extra" value="1">
    <input type="hidden" name="xallegro_product_custom_account_current" value="0">

    {if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '>=')}
        <div class="alert alert-warning" role="alert">
            <p class="alert-text">
                {l s='Aby poprawnie zapisać ustawienia tego produktu w module Integracja PrestaShop z Allegro, użyj przycisku "Zapisz ustawienia Allegro"' mod='x13allegro'}
            </p>
        </div>
    {/if}

    {include file="./_partials/admin-products-extra-save-button.tpl"}
</div>

<div id="xallegro_product_custom_panel" class="panel bootstrap">
    {if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '>=')}
        <h2>{l s='Ustawienia indywidualne produktu' mod='x13allegro'}</h2>
    {else}
        <h3>{l s='Ustawienia indywidualne produktu' mod='x13allegro'}</h3>
    {/if}

    <div class="form-group clearfix">
        <label for="xallegro_product_custom_account" class="control-label form-control-label col-lg-3">
            <span>{l s='Konto Allegro' mod='x13allegro'}</span>
        </label>
        <div class="col-lg-3">
            <select class="custom-select" name="xallegro_product_custom_account" id="xallegro_product_custom_account">
                <option value="0">{l s='-- wszystkie konta --' mod='x13allegro'}</option>
                {foreach $allegroAccounts as $account}
                    <option value="{$account->id}">
                        {$account->username}{if $account->sandbox} (sandbox){/if}
                    </option>
                {/foreach}
            </select>
        </div>
    </div>

    <div id="xallegro_product_custom_form">
        {$productCustomForm}
    </div>

    {include file="./_partials/admin-products-extra-save-button.tpl"}
</div>

<div id="xallegro_tags_panel" class="panel bootstrap">
    {if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '>=')}
        <h2>{l s='Tagi' mod='x13allegro'}</h2>
    {else}
        <h3>{l s='Tagi' mod='x13allegro'}</h3>
    {/if}

    {$tagManagerForm}

    {include file="./_partials/admin-products-extra-save-button.tpl"}
</div>

<div id="xallegro_images_additional_panel" class="panel bootstrap">
    {if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '>=')}
        <h2>{l s='Dodatkowe zdjęcia' mod='x13allegro'}</h2>
    {else}
        <h3>{l s='Dodatkowe zdjęcia' mod='x13allegro'}</h3>
    {/if}

    <div id="xallegro_images_additional">
        {$imagesAdditionalForm}
    </div>

    <div class="form-group">
        <div class="col-lg-9">
            <input type="file" name="xallegro_image_additional" style="display: none;">
            <button class="btn btn-default button bt-icon btn-outline-secondary addXAllegroImageAdditional" style="margin-top: 10px">
                {if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '>=')}<i class="material-icons">add_circle</i>{else}<i class="icon-plus-sign"></i>{/if} <span>{l s='Dodaj nowe zdjęcie' mod='x13allegro'}</span>
            </button>
        </div>
    </div>

    {include file="./_partials/admin-products-extra-save-button.tpl"}
</div>

<div id="xallegro_descriptions_additional_panel" class="panel bootstrap clearfix">
    {if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '>=')}
        <h2>{l s='Dodatkowe opisy' mod='x13allegro'}</h2>
    {else}
        <h3>{l s='Dodatkowe opisy' mod='x13allegro'}</h3>
    {/if}

    <div id="xallegro_description_additional">
        {$descriptionsAdditionalForm}
    </div>

    <div class="form-group">
        <div class="col-lg-9">
            <button class="btn btn-default button bt-icon btn-outline-secondary addXAllegroDescriptionAdditional" style="margin-top: 10px">
                {if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '>=')}<i class="material-icons">add_circle</i>{else}<i class="icon-plus-sign"></i>{/if} <span>{l s='Dodaj nowy opis' mod='x13allegro'}</span>
            </button>
        </div>
    </div>

    {include file="./_partials/admin-products-extra-save-button.tpl"}
</div>

{if !empty($displayX13AllegroAdminProductsExtra)}
    <div id="xallegro_extra_panel" class="panel bootstrap clearfix">
        {$displayX13AllegroAdminProductsExtra}
        {include file="./_partials/admin-products-extra-save-button.tpl"}
    </div>
{/if}

<script type="text/javascript">
    var XAllegroCustomProductConfirmChangeAccount = "{l s='Zmiana konta spowoduje anulowanie niezapisanych zmian, czy na pewno chcesz kontynuować?' js=1}";
    var XAllegroCustomProductConfirmChangePrice = "{l s='Czy na pewno chcesz zmienić cenę kombinacji? Usunie to wpływ na główną cenę produktu przy zapisie.' js=1}";
    var XAllegroCustomProductConfirmChangePriceFlat = "{l s='Czy na pewno chcesz zmienić cenę podstawową produktu? Usunie to wpływ na kombinacje produktu przy zapisie.' js=1}";
    var XAllegroCustomProductConfirmPriceDelete = "{l s='Czy na pewno usunąć wszystkie ceny dedykowane dla Allegro tego produktu? Ta operacja jest nieodwracalna.' js=1}";

    $(function() {
        var XAllegro = new X13Allegro();
        XAllegro.ajaxUrl = "{$productsExtraController}";
        XAllegro.ajaxToken = "{$productsExtraToken}";
        XAllegro.productsExtra({$productId}, {$imagesAdditionalMaxCount}, {$descriptionsAdditionalMaxCount});

        {if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '<')}
            $(document).on('click', '#product-tab-content-ModuleX13allegro button[name*="submitAddproduct"]', function () {
                XAllegro.productsExtraBeforeSave();
            });
        {/if}
    });
</script>

<style>
  .xallegro-custom-price-helper i {
    background: #25b9d7;
    color: white;
    border-radius: 50%;
    margin-right: 3px;
    margin-top: -2px;
    font-size: 15px;
    cursor: pointer;
  }

  #xallegro_images_additional,
  #xallegro_description_additional {
    padding-left: 15px;
    padding-right: 15px;
  }
  #xallegro_images_additional .form-group,
  #xallegro_description_additional .form-group {
    -webkit-box-flex: 0;
    -ms-flex: 0 0 75%;
    flex: 0 0 75%;
    max-width: 75%;
  }

  .xallegro-image-additional-wrapper {
    padding: 10px;
    background-color: #f3f3f3;
  }
  .xallegro-image-additional-wrapper .img-thumbnail {
    float: left;
    display: inline-block;
    max-width: 150px;
    margin-right: 5px;
  }
  .xallegro-image-additional-wrapper .img-description {
    display: inline-block;
  }
  .xallegro-image-additional-wrapper .xallegro-image-additional-delete,
  .xallegro-image-additional-wrapper .xallegro-image-additional-update {
    float: right;
  }

  .xallegro-description-additional-wrapper {
    border: 1px solid #c7d6db;
    border-radius: 3px;
    background: #fff;
  }
  .xallegro-description-additional-wrapper label {
    display: block;
    margin: 0;
    background-color: #f3f3f3;
  }
  .xallegro-description-additional-wrapper .xallegro-description-additional-move {
    cursor: move !important;
  }
  .xallegro-description-additional-wrapper .xallegro-description-additional-delete {
    float: right;
  }
  .xallegro-description-additional-wrapper textarea {
    border: none;
  }

  #xallegro_description_additional .ui-sortable-placeholder {
    border: 1px dotted #c7d6db;
    visibility: visible !important;
  }
</style>

{if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '>=')}
    <style>
      .panel-footer > hr {
        margin-bottom: 10px;
      }
    </style>
{/if}
