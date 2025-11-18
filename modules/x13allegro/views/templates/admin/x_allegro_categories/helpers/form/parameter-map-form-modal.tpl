<div class="modal-dialog" role="document">
    <form action="#" method="post" class="defaultForm form-horizontal">
        <input type="hidden" name="id_xallegro_category" value="{$categoryId}">
        <input type="hidden" name="xallegro_parameter_id" value="{$parameterId}">

        <div class="modal-content">
            <div class="modal-header x13allegro-modal-header">
                <button type="button" class="close x13allegro-modal-close xallegro-parameter-map-close"><span>&times;</span></button>
                <h4 class="x13allegro-modal-title">{l s='Mapowanie parametru' mod='x13allegro'}</h4>
                <h6 class="x13allegro-modal-title-small">{$parameterName} {if $parameterUnit}({$parameterUnit}){/if}</h6>
                <p class="help-block">{$categoryPath}</p>
            </div>

            <div class="modal-body x13allegro-modal-body">
                <ul class="nav nav-tabs" id="itemTab_parameterMap">
                    <li class="active">
                        <a href="#itemTab_parameterMap_default" id="navTab_parameterMap_default" aria-controls="itemTab_parameterMap_default" role="tab" data-toggle="tab">
                            {l s='Wartość domyślna' mod='x13allegro'}
                        </a>
                    </li>
                    <li>
                        <a href="#itemTab_parameterMap_mapping" id="navTab_parameterMap_mapping" aria-controls="itemTab_parameterMap_mapping" role="tab" data-toggle="tab">
                            {l s='Mapowanie' mod='x13allegro'} <span class="badge">{if !empty($mapValuesForm)}{$mapValuesForm|count}{/if}</span>
                        </a>
                    </li>
                </ul>

                <div class="tab-content panel clearfix" id="itemTab_parameterMap_Content" role="tabpanel">
                    <div class="tab-pane active" id="itemTab_parameterMap_default">
                        {$defaultParameterForm}
                    </div>
                    <div class="tab-pane" id="itemTab_parameterMap_mapping">
                        <table class="table xallegro-parameter-map-table">
                            <colgroup>
                                {if $parameterType != 'dictionary'}<col width="30px">{/if}
                                {if $parameterType == 'dictionary' || $isRangeValue}<col width="230px">{/if}
                                <col width="230px">
                                <col>
                                <col width="110px">
                            </colgroup>
                            <thead>
                                <tr>
                                    {if $parameterType != 'dictionary'}<th><i class="icon-sort"></i></th>{/if}
                                    {if $parameterType == 'dictionary' || $isRangeValue}
                                        <th>
                                            {if $parameterType == 'dictionary'}{l s='Wartość parametru Allegro' mod='x13allegro'}{else}{l s='Tryb mapowania' mod='x13allegro'}{/if}
                                        </th>
                                    {/if}
                                    <th>{if $parameterType == 'dictionary'}{l s='Ustaw jeśli' mod='x13allegro'}{else}{l s='Wstaw wartość' mod='x13allegro'}{/if}</th>
                                    <th>{l s='Wartość w sklepie' mod='x13allegro'}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                {if !empty($mapValuesForm)}
                                    {foreach $mapValuesForm as $mapValue}
                                        <tr data-saved="1">
                                            {if $parameterType != 'dictionary'}
                                                <td class="xallegro-parameter-sort text-center">
                                                    <i class="icon-bars"></i>
                                                </td>
                                            {/if}
                                            {if isset($mapValue.valueId)}
                                                <td class="xallegro-parameter-valueId">
                                                    <input type="hidden" name="xallegro_parameter_map[][valueId]" value="{$mapValue.valueId}" data-name="{$mapValue.valueId_name}" class="input-parameter-map">
                                                    <span>{$mapValue.valueId_name}</span>
                                                </td>
                                            {/if}
                                            <td class="xallegro-parameter-rule">
                                                <input type="hidden" name="xallegro_parameter_map[][rule]" value="{$mapValue.rule}" data-name="{$mapValue.rule_name}" class="input-parameter-map">
                                                {if isset($mapValue.ambiguous)}
                                                    <input type="hidden" name="xallegro_parameter_map[][ambiguous][rule]" value="{$mapValue.ambiguous.rule}" data-name="{$mapValue.ambiguous.rule_name}" class="input-ambiguous-parameter-map">
                                                {/if}
                                                <span>{$mapValue.rule_name}</span>
                                            </td>
                                            <td class="xallegro-parameter-ruleValue">
                                                {if is_array($mapValue.ruleValue)}
                                                    <input type="hidden" name="xallegro_parameter_map[][ruleValue][rangeMin]" value="{$mapValue.ruleValue.rangeMin}" data-name="{$mapValue.ruleValue.rangeMin_name}" class="input-parameter-map rangeMin">
                                                    <input type="hidden" name="xallegro_parameter_map[][ruleValue][rangeMax]" value="{$mapValue.ruleValue.rangeMax}" data-name="{$mapValue.ruleValue.rangeMax_name}" class="input-parameter-map rangeMax">
                                                    <span>
                                                        {if $mapValue.valueId == 'range_split'}
                                                            od: {$mapValue.ruleValue.rangeMin_name}<br>
                                                            do: {$mapValue.ruleValue.rangeMax_name}
                                                        {else}
                                                            {$mapValue.ruleValue.rangeMin_name}
                                                        {/if}
                                                    </span>
                                                {else}
                                                    <input type="hidden" name="xallegro_parameter_map[][ruleValue]" value="{$mapValue.ruleValue}" data-name="{$mapValue.ruleValue_name}" class="input-parameter-map">
                                                    {if isset($mapValue.ambiguous)}
                                                        <input type="hidden" name="xallegro_parameter_map[][ambiguous][ruleValue]" value="{$mapValue.ambiguous.ruleValue}" data-name="{$mapValue.ambiguous.ruleValue_name}" class="input-ambiguous-parameter-map">
                                                    {/if}
                                                    <span>
                                                        {$mapValue.ruleValue_name}
                                                        {if isset($mapValue.ambiguous)}
                                                            {if !empty($mapValue.ruleValue_name)}<br>{/if}
                                                            inna wartość: {$mapValue.ambiguous.rule_name}{if !empty($mapValue.ambiguous.ruleValue_name)} "{$mapValue.ambiguous.ruleValue_name}"{/if}
                                                        {/if}
                                                    </span>
                                                {/if}
                                            </td>
                                            <td class="text-right">
                                                <a title="Zapisz mapowanie" class="btn btn-primary xallegro-parameter-row-save" style="display: none;"><i class="icon-save"></i></a>
                                                <a title="Anuluj mapowanie" class="btn xallegro-parameter-row-quit" style="display: none;"><i class="icon-remove"></i></a>
                                                <a title="Edytuj mapowanie" class="btn xallegro-parameter-row-edit"><i class="icon-pencil"></i></a>
                                                <a title="Usuń mapowanie" class="btn xallegro-parameter-row-delete"><i class="icon-trash"></i></a>
                                            </td>
                                        </tr>
                                    {/foreach}
                                {/if}
                            </tbody>
                        </table>

                        <button class="btn btn-default xallegro-parameter-map-add">{l s='Dodaj nowe mapowanie' mod='x13allegro'}</button>

                        {if !$isDictionary || $isRangeValue || $hasAmbiguousValue}
                            <hr>
                            {if !$isDictionary}
                                <div class="help-block">
                                    <p>Mapowanie tego parametru można sortować.<br>W przypadku gdy pierwsza wartość sklepowa będzie pusta, zostanie wybrana następna.</p>
                                </div>
                            {/if}
                            {if $isRangeValue}
                                <div class="help-block">
                                    <p>Parametry zakresowe można mapować według dwóch trybów:</p>
                                    <ul>
                                        <li>z dwóch osobnych wartości sklepowych</li>
                                        <li>z jednej wartości sklepowej, zostanie ona rozdzielona po znakach "-" lub "x"</li>
                                    </ul>
                                </div>
                            {/if}
                            {if $hasAmbiguousValue}
                                <div class="help-block">
                                    <p>Po wybraniu wartości niejednoznacznej "{$ambiguousValue}", należy wybrać wartość jaka zostanie automatycznie uzupełniona w polu "inna wartość".</p>
                                </div>
                            {/if}
                        {/if}
                    </div>
                </div>
            </div>

            <div class="modal-footer x13allegro-modal-footer">
                <button type="button" class="btn btn-left btn-default xallegro-parameter-map-close">{l s='Anuluj' mod='x13allegro'}</button>
                <button type="button" class="btn btn-primary xallegro-parameter-map-submit">{l s='Zapisz mapowanie parametru' mod='x13allegro'}</button>
            </div>
        </div>
    </form>
</div>

<div class="modal_alert xallegro_modal_alert" id="xallegro_parameter_map_form_modal_alert_close">
    <div class="modal_alert-content">
        <div class="modal_alert-message">
            <h2>{l s='Czy na pewno rezygnujesz z zapisania mapowania dla tego parametru?' mod='x13allegro'}</h2>
            <p>{l s='Wprowadzone zmiany nie zostaną zapisane!'}</p>
        </div>
        <div class="modal_alert-buttons">
            <button type="button" class="btn btn-default modal_alert-cancel">{l s='Wróć' mod='x13allegro'}</button>
            <button type="button" class="btn btn-danger modal_alert-confirm">{l s='Rezygnuje' mod='x13allegro'}</button>
        </div>
    </div>
</div>

<div class="modal_alert xallegro_modal_alert" id="xallegro_parameter_map_form_modal_alert_before_save">
    <div class="modal_alert-content">
        <div class="modal_alert-message">
            <h2>{l s='Uzupełnij wszystkie kolumny aby zapisać aktulane mapowanie' mod='x13allegro'}</h2>
        </div>
        <div class="modal_alert-buttons">
            <button type="button" class="btn btn-primary modal_alert-confirm">{l s='Ok' mod='x13allegro'}</button>
        </div>
    </div>
</div>

<div class="modal_alert xallegro_modal_alert" id="xallegro_parameter_map_form_modal_alert_save">
    <div class="modal_alert-content">
        <div class="modal_alert-message">
            <h2>{l s='Najpierw zapisz lub anuluj aktualne mapowanie' mod='x13allegro'}</h2>
        </div>
        <div class="modal_alert-buttons">
            <button type="button" class="btn btn-primary modal_alert-confirm">{l s='Ok' mod='x13allegro'}</button>
        </div>
    </div>
</div>
