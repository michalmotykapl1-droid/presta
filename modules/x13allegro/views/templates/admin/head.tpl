<style>
.icon-AdminXAllegroMain:before{
    content: "\f0e3";
}

.xallegro-log-unread-badge{
    font-size: 10px;
    color: #fff;
    padding: 0 5px !important;
    margin-left: 5px !important;
    border-radius: 10px;
    background: #f54c3e;
    height: 15px;
    min-width: 5px;
    display: inline-block;
    text-align: center;
    line-height: 14px;
}
</style>
<script type="text/javascript">
    var xallegro_token = '{$token}';
    var xallegro_unread_logs = {$unreadLogs};

    $(function() {
        var $main_adminXAllegroMain = $('#maintab-AdminXAllegroMain');
        var $sub_adminXAllegroMain = $('#subtab-AdminXAllegroMain');
        var $sub_adminXAllegroLog = $('#subtab-AdminXAllegroLog');

        if (xallegro_unread_logs && $sub_adminXAllegroLog.length) {
            $main_adminXAllegroMain.find('a > span').append('<span class="xallegro-log-unread-badge">' + xallegro_unread_logs + '</span>');
            $sub_adminXAllegroMain.find('a > span').append('<span class="xallegro-log-unread-badge">' + xallegro_unread_logs + '</span>');
            $sub_adminXAllegroLog.find('a').append('<span class="xallegro-log-unread-badge">' + xallegro_unread_logs + '</span>');
        }
    });
</script>

{if $isAdminOrders}
    <style>
        .process-icon-truck::after {
            content: url('{$smarty.const.__PS_BASE_URI__}modules/x13allegro/img/AdminXAllegroMain.png');
            position: absolute;
            z-index: 1;
            top: 5px;
            right: -10px;
        }
        .process-icon-truck-177 {
          padding-right: 12px;
        }
        .process-icon-truck-177::after {
          top: 10px;
          left: 29px;
          text-align: left;
        }
    </style>
    <script type="text/javascript">
        $(document).ready(function() {
            {if $smarty.const._PS_VERSION_ < '1.7.7.0'}
                $('#toolbar-nav, .cc_button').prepend(
                    '<li>' +
                        '<a id="" class="toolbar_btn pointer" href="{$orderShippingLink}" title="">' +
                            '<i class="process-icon-truck icon-truck xallegro-order-shipping fa fa-truck"></i>' +
                            '<div>Numery śledzenia Allegro</div>' +
                        '</a>' +
                    '</li>'
                );
            {else}
                $('.toolbar-icons .wrapper').prepend(
                    '<a id="" class="btn btn-outline-secondary pointer" href="{$orderShippingLink}" title="" style="position: relative;">' +
                        '<i class="process-icon-truck process-icon-truck-177 material-icons">local_shipping</i>&nbsp;Numery śledzenia Allegro' +
                    '</a>'
                );
            {/if}
        });
    </script>
{elseif $isAdminStatuses}
    <script type="text/javascript">
        var xallegro_statuses = [{$allegroStatusesIds}];

        $(function() {
            if ($('input[name="id_order_state"]').length && xallegro_statuses.includes(parseInt($('input[name="id_order_state"]').val()))) {
                $('form#order_state_form input[type="checkbox"]').each(function(index, el) {
                    if ($(el).attr('name') == 'logable_on'
                        || $(el).attr('name') == 'shipped_on'
                        || $(el).attr('name') == 'paid_on'
                    ) {
                        $(el).prop('disabled', 'disabled');
                    }
                });
            }
        });
    </script>
{/if}
