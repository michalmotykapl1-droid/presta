
<div class="gc-preview-wrap">
  <div class="row">
    <div class="col-lg-6">
      <h3>Po zmianie (AI + zaznaczenia)</h3>
      <h1>{$product_preview.name|escape:'html':'UTF-8'}</h1>
      {if $product_preview.meta_title}<p><small><strong>Meta title:</strong> {$product_preview.meta_title|escape:'html':'UTF-8'}</small></p>{/if}
      {if $product_preview.meta_description}<p><small><strong>Meta description:</strong> {$product_preview.meta_description|escape:'html':'UTF-8'}</small></p>{/if}
      {if $product_preview.link_rewrite}<p><small><strong>URL:</strong> /{$product_preview.link_rewrite|escape:'html':'UTF-8'}.html</small></p>{/if}
      {if $product_preview.sklad}
        <h4>Skład</h4>
        <ul>
          {foreach from="|"|explode:$product_preview.sklad item=i}
            {if $i|trim != ''}<li>{$i|escape:'html':'UTF-8'}</li>{/if}
          {/foreach}
        </ul>
      {/if}
      {if $product_preview.wartosci}
        <h4>Wartości</h4>
        <ul>
          {foreach from="|"|explode:$product_preview.wartosci item=v}
            {if $v|trim != ''}<li>{$v|escape:'html':'UTF-8'}</li>{/if}
          {/foreach}
        </ul>
      {/if}
      <div class="desc">{$product_preview.description nofilter}</div>
    </div>
    <div class="col-lg-6">
      <h3>Obecnie na sklepie</h3>
      <h1>{$product_current.name|escape:'html':'UTF-8'}</h1>
      {if $product_current.meta_title}<p><small><strong>Meta title:</strong> {$product_current.meta_title|escape:'html':'UTF-8'}</small></p>{/if}
      {if $product_current.meta_description}<p><small><strong>Meta description:</strong> {$product_current.meta_description|escape:'html':'UTF-8'}</small></p>{/if}
      {if $product_current.link_rewrite}<p><small><strong>URL:</strong> /{$product_current.link_rewrite|escape:'html':'UTF-8'}.html</small></p>{/if}
      <div class="desc">{$product_current.description nofilter}</div>
    </div>
  </div>
</div>
