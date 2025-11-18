$(document).ready(function ()
{
    var allegroAuctionLink = $('.x13allegro-auction-link a');
    getAllegroAuctionLink();

    $(document).on('change', '.attribute_select, .attribute_radio', function() {
        getAllegroAuctionLink();
    });

    $(document).on('click', '.color_pick', function() {
        setTimeout(function(){
            getAllegroAuctionLink();
        }, 500);
    });

    function getAllegroAuctionLink()
    {
        $.ajax({
            url: allegroAuctionLink.data('controller'),
            method: 'post',
            async: true,
            dataType: 'json',
            data: {
                getAllegroAuctionLink: true,
                id_product: $('input[name="id_product"]').val(),
                id_product_attribute: $('input[name="id_product_attribute"]').val()
            },
            success: function(data)
            {
                if (data.result) {
                    allegroAuctionLink.attr('href', data.href).parent().show();
                }
                else {
                    allegroAuctionLink.attr('href', '#').parent().hide();
                }
            }
        });
    }
});
