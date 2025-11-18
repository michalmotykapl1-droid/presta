{*
  2007-2025 PrestaShop
  Academic Free License (AFL 3.0)
*}
{strip}
<div class="search-widget tvcmsheader-search"
     data-search-controller-url="{$search_controller_url|escape:'htmlall':'UTF-8'}">
  <div class="tvsearch-top-wrapper">
    <div class="tvheader-sarch-display">
      <div class="tvheader-search-display-icon">
        <div class="tvsearch-open">
          <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 30 30">
            <g>
              <polygon points="29.245,30 21.475,22.32 22.23,21.552 30,29.232"/>
              <circle fill="#FFD741" cx="13" cy="13" r="12.1"/>
              <circle fill="none" stroke="#000" stroke-miterlimit="10" cx="13" cy="13" r="12.5"/>
            </g>
          </svg>
        </div>
        <div class="tvsearch-close">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 20 20">
            <g>
              <rect x="9.63" y="-3.82" transform="matrix(0.7064 -0.7078 0.7078 0.7064 -4.1427 10.0132)" width="1" height="27.641"/>
            </g>
            <g>
              <rect x="9.63" y="-3.82" transform="matrix(-0.7064 -0.7078 0.7078 -0.7064 9.9859 24.1432)" width="1" height="27.641"/>
            </g>
          </svg>
        </div>
      </div>
    </div>

    <div class="tvsearch-header-display-wrappper tvsearch-header-display-full">
      <form method="get"
            action="{$search_controller_url|escape:'htmlall':'UTF-8'}">
        <input type="hidden" name="controller" value="search"/>
        <div class="tvheader-top-search">
          <div class="tvheader-top-search-wrapper-info-box">
            <input
              type="text"
              name="s"
              class="tvcmssearch-words"
              value="{$smarty.get.s|default:''|escape:'htmlall':'UTF-8'}"
              placeholder="{l s='Szukaj…' mod='tvcmssearch'}"
              aria-label="{l s='Szukaj…' mod='tvcmssearch'}"
              autocomplete="off"/>
          </div>
        </div>
        <div class="tvheader-top-search-wrapper">
          <button type="submit"
                  class="tvheader-search-btn"
                  aria-label="{l s='Szukaj…' mod='tvcmssearch'}">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 30 30">
              <g>
                <polygon points="29.245,30 21.475,22.32 22.23,21.552 30,29.232"/>
                <circle fill="#FFD741" cx="13" cy="13" r="12.1"/>
                <circle fill="none" stroke="#000" stroke-miterlimit="10" cx="13" cy="13" r="12.5"/>
              </g>
            </svg>
          </button>
        </div>
      </form>

      <div class="tvsearch-result"></div>
    </div>
  </div>
</div>
{/strip}