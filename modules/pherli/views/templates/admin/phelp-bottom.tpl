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
{$banersHtml}
<div class="view-rest">
    <div class="box-auto small">
        <a name="changelog-btn"></a>
        <div id="changelog-shops" class="container-auto">
            <div class="header">
                    <span>
                        <?xml version="1.0"?>
                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1"
                             id="Capa_1" x="0px" y="0px" width="10px" height="10px" viewBox="0 0 369.793 369.792"
                             style="enable-background:new 0 0 369.793 369.792;" xml:space="preserve">
                            <g>
                                <g>
                                    <g>
                                        <g>
                                            <path
                                                    d="M320.83,140.434l-1.759-0.627l-6.87-16.399l0.745-1.685c20.812-47.201,19.377-48.609,15.925-52.031L301.11,42.61     c-1.135-1.126-3.128-1.918-4.846-1.918c-1.562,0-6.293,0-47.294,18.57L247.326,60l-16.916-6.812l-0.679-1.684     C210.45,3.762,208.475,3.762,203.677,3.762h-39.205c-4.78,0-6.957,0-24.836,47.825l-0.673,1.741l-16.828,6.86l-1.609-0.669     C92.774,47.819,76.57,41.886,72.346,41.886c-1.714,0-3.714,0.769-4.854,1.892l-27.787,27.16     c-3.525,3.477-4.987,4.933,16.915,51.149l0.805,1.714l-6.881,16.381l-1.684,0.651C0,159.715,0,161.556,0,166.474v38.418     c0,4.931,0,6.979,48.957,24.524l1.75,0.618l6.882,16.333l-0.739,1.669c-20.812,47.223-19.492,48.501-15.949,52.025L68.62,327.18     c1.162,1.117,3.173,1.915,4.888,1.915c1.552,0,6.272,0,47.3-18.561l1.643-0.769l16.927,6.846l0.658,1.693     c19.293,47.726,21.275,47.726,26.076,47.726h39.217c4.924,0,6.966,0,24.859-47.857l0.667-1.742l16.855-6.814l1.604,0.654     c27.729,11.733,43.925,17.654,48.122,17.654c1.699,0,3.717-0.745,4.876-1.893l27.832-27.219     c3.501-3.495,4.96-4.924-16.981-51.096l-0.816-1.734l6.869-16.31l1.64-0.643c48.938-18.981,48.938-20.831,48.938-25.755v-38.395     C369.793,159.95,369.793,157.914,320.83,140.434z M184.896,247.203c-35.038,0-63.542-27.959-63.542-62.3     c0-34.342,28.505-62.264,63.542-62.264c35.023,0,63.522,27.928,63.522,62.264C248.419,219.238,219.92,247.203,184.896,247.203z"
                                                    data-original="#000000" class="active-path" data-old_color="#000000"
                                                    fill="#29262E" />
                                        </g>
                                    </g>
                                </g>
                            </g>
                        </svg>
                        Changelog</span>
            </div>
            <div class="shops">
                <div class="changelog-info changelog-current">
                    <div class="changelog-row">
                        <h5><b>{$moduleName}</b> aktualna wersja <b>{$chlogInfo.module_version}</b> - {$chlogInfo.date_add}</h5>
                    </div>
                    <div class="shop-boxs">
                        {if !empty($chlogInfo.details)}
                            {foreach $chlogInfo.details as $detail}
                                <span class="label-{$detail->chlog_type}"><i class="fas fa-{if $detail->chlog_type == 1}plus-square{elseif $detail->chlog_type == 2}bug{elseif $detail->chlog_type == 3}minus-square{else}angle-right{/if}"></i> {$detail->chlog_description}</span>
                            {/foreach}
                        {/if}
                    </div>
                </div>

                {if $chlogInfoOther}
                    {foreach $chlogInfoOther as $item}
                        <div class="chlog-item changelog-info">
                            <div class="changelog-row">
                                <h5><b>{$moduleName}</b> wersja <b>{$item->module_version}</b> - {$item->date_add}</h5>
                            </div>
                            <div class="shop-boxs">
                                {if !empty($item->details)}
                                    {foreach $item->details as $detail}
                                        <span class="label-{$detail->chlog_type}"><i class="fas fa-{if $detail->chlog_type == 1}plus-square{elseif $detail->chlog_type == 2}bug{elseif $detail->chlog_type == 3}minus-square{else}angle-right{/if}"></i> {$detail->chlog_description}</span>
                                    {/foreach}
                                {/if}
                            </div>
                        </div>
                    {/foreach}
                {/if}
            </div>
        </div>
    </div>
</div>
