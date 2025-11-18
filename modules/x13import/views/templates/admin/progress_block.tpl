<style>
  #ximport_progress_block {
    display: none;
    padding: 10px 15px;
    margin-bottom: 15px;
    border-radius: 4px;
    border-left: 3px solid #2794AB;
    background: #DCF4F9;
  }

  #ximport_progress_block h2 {
    margin: 10px 0;
  }
  #ximport_progress_block h2 i {
    font-size: 25px;
    margin-right: 8px;
  }

  #ximport_progress_block .x-list {
    margin: 10px 0;
  }

  #ximport_progress_block .x-bar {
    display: none;
    margin: 10px 0;
  }
  #ximport_progress_block .x-bar .x-bar-visual {
    height: 20px;
    background: #FFF;
    border-bottom: 1px solid #2794AB;
  }
  #ximport_progress_block .x-bar .x-bar-visual > div {
    width: 0;
    height: 100%;
    background: #2794AB;
  }

  #ximport_progress_block .x-list .x-list-element {
    margin-bottom: 2px;
  }
  #ximport_progress_block .x-list .x-list-element .x-list-wholesaler {
    font-size: 13px;
    font-weight: bold;
  }
  #ximport_progress_block .x-list .x-list-element .x-list-wholesaler-action {
    font-size: 12px;
    font-style: italic;
  }
  #ximport_progress_block .x-list .x-list-element .x-list-wholesaler-action.badge {
    padding: 1px 8px;
  }
</style>

<div id="ximport_progress_block">
    <h2 class="x-header"></h2>
    <h4 class="x-description"></h4>

    <div class="x-bar">
        <div class="x-bar-visual"><div></div></div>
    </div>

    <div class="x-form"></div>
    <div class="x-content"></div>
    <div class="x-close"><a href="#" class="btn btn-default">{l s='Zamknij' mod='x13import'}</a></div>
</div>
<div id="_pattern_ximport_progress_block" style="display: none;">
    <ul id="_pattern_list_wholesalers">
        <li class="x-list-element">
            <span class="x-list-wholesaler"></span>:&nbsp;
            <span class="x-list-wholesaler-action">{l s='oczekiwanie' mod='x13import'}...</span>
        </li>
    </ul>
    <div id="_pattern_content">
        <p class="x-message-before"></p>
        <ul class="x-list"></ul>
        <p class="x-message-after"></p>
        <p class="x-refresh-button" style="display: none;">
            <a href="{$currentIndex}&token={$token}" class="btn btn-warning"><i class="icon-refresh"></i></a>
        </p>
    </div>
</div>
