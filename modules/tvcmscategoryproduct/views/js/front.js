/**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2025 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */
var storage;
var langId = document.getElementsByTagName("html")[0].getAttribute("lang");
var currentCatProdModule = tvthemename + "_catprod_" + langId;
var url = gettvcmscategoryproductlink;
jQuery(document).ready(function($) {
    storage = $.localStorage;

    function storageGet(key) {
        return "" + storage.get(currentCatProdModule + key);
    }

    function storageSet(key, value) {
        storage.set(currentCatProdModule + key, value);
    }

    function hide_category() {
        var totalCategory = $('.tvtabcategory-product-sub-title-block li').size();
        totalCategory = totalCategory - 2;
        var showCategory = 7;
        if (document.body.clientWidth >= 992 && document.body.clientWidth <= 1199) {
            showCategory = 6;
        } else if (document.body.clientWidth >= 768 && document.body.clientWidth <= 991) {
            showCategory = 6;
        }
        if (totalCategory > showCategory) {
            tvsub_category_product_sub_title_block_hide(totalCategory, showCategory);
            tvsub_category_hide();
            $('.tvtabcategory-product-sub-title-block .tvtabcategory-show').on('click', function(event) {
                tvsub_category_product_sub_title_block_show(totalCategory, showCategory);
                tvsub_category_show();
                event.stopPropagation();
            });
            $('.tvtabcategory-product-sub-title-block .tvtabcategory-hide').on('click', function(event) {
                tvsub_category_product_sub_title_block_hide(totalCategory, showCategory)
                tvsub_category_hide();
                event.stopPropagation();
            });

            function tvsub_category_product_sub_title_block_hide(totalCategory, showCategory) {
                var i = 1;
                $('.tvtabcategory-product-sub-title-block li').each(function() {
                    if (i > showCategory) {
                        $(this).addClass('hide');
                    }
                    i = i + 1;
                });
            }

            function tvsub_category_product_sub_title_block_show(totalCategory, showCategory) {
                $('.tvtabcategory-product-sub-title-block li').each(function() {
                    $(this).removeClass('hide');
                });
            }

            function tvsub_category_show() {
                $('.tvtabcategory-product-sub-title-block .show-hide').addClass('hide');
                $('.tvtabcategory-product-sub-title-block .tvtabcategory-hide').removeClass('hide');
            }

            function tvsub_category_hide() {
                $('.tvtabcategory-hide').addClass('hide');
                $('.tvtabcategory-product-sub-title-block .tvtabcategory-show').removeClass('hide');
            }
        }
    } //End hide Show categoery
    hide_category();
    /*********************** Start Ajax And Slider *******************************/
    $('.tvtabcategory-product-sub-title-block ul').children('li:first-child').addClass('active');
    var category_id = $('.tvtabcategory-product-sub-title-block li.active a').attr('data-category-id');
    var num_of_prod = $('.tvtabcategory-product-sub-title-block li.active a').attr('data-num-prod');

    $('.tvtabcategory-product-sub-title-block li').on('click', function(e) {
        e.preventDefault();
        if ($(this).hasClass('show-hide')) {
            return true;
        }
        $('.tvtabcategory-product-sub-title-block li').each(function() {
            $(this).removeClass('active');
        });
        $(this).addClass('active');
        var category_id = $(this).find('a').attr('data-category-id');
        var num_of_prod = $(this).find('a').attr('data-num-prod');

        StatusAjax = true;
        var param = {
            "url": url,
            "category_id": category_id,
            "num_of_prod": num_of_prod
        };
        getDataUsingAjax(param);
        $('.tvtabcategory-all-product-wrapper .ajaxData').fadeOut(150);
        $('.tvtabcategory-all-product-wrapper .load' + $category_id).fadeIn(580);
    });
    var StatusAjax = true;

    var getDataUsingAjax = function($param) {
        if ($('body').hasClass('page-index') && StatusAjax) {
            $url = $param.url;
            $category_id = $param.category_id;
            $num_of_prod = $param.num_of_prod;
            StatusAjax = false;
            /*****Load Cache*****/

            var data = "" + storageGet($param.category_id);
            if (!$('.tvtabcategory-all-product-wrapper').hasClass("load" + $param.category_id)) {
                if (data != "null") {
                    $('.tvtabcategory-all-product-wrapper').addClass("load" + $param.category_id);
                    $('.tvtabcategory-all-product-wrapper').append(data);
                    callSlider();
                    productTime(); //custom.js
                }
            }
            /*****Load Cache*****/
            $.ajax({
                type: 'POST',
                url: $url,
                data: 'category_id=' + $param.category_id + '&num_of_prod=' + $num_of_prod + '&token=' + static_token,
                success: function(data) {
                    var dataCache = "" + storageGet($param.category_id);
                    var result = "<div class='ajaxData load" + $param.category_id + "'>" + data + "</div>";
                    storageSet($param.category_id, result);
                    if (dataCache === 'null') {
                        $('.tvtabcategory-all-product-wrapper').addClass("load" + $param.category_id);
                        //$('.tvtabcategory-all-product-wrapper').html('');                        
                        $('.tvtabcategory-all-product-wrapper').append(result);
                        callSlider();
                        // $('.tvtabcategory-all-product-slider .tvproduct-wrapper').balance();
                        //customImgLazyLoad('.tvtabcategory-all-product-wrapper');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log(textStatus, errorThrown);
                }
            });

        }
    }
    var param = {
        "url": url,
        "category_id": category_id,
        "num_of_prod": num_of_prod
    };
    themevoltyCallEventsPush(getDataUsingAjax, param);

    /**************************** Start Slider ****************************************/
    function callSlider() {
        $('.tvcmstabcategory-product-slider-main-title-wrapper .tvtabcategory-product-pagination').remove();
        $('.tvtabcategory-all-product-content-box .tvtabcategory-all-product-slider').owlCarousel({
            loop: false,
            dots: false,
            nav: false,
            smartSpeed: tvMainSmartSpeed,
            responsive: {
                0: { items: 1 },
                320: { items: 1, slideBy: 1 },
                330: { items: 2, slideBy: 1 },
                400: { items: 2, slideBy: 1 },
                480: { items: 2, slideBy: 1 },
                650: { items: 2, slideBy: 1 },
                767: { items: 2, slideBy: 1 },
                768: { items: 2, slideBy: 1 },
                992: { items: 3, slideBy: 1 },
                1023: { items: 3, slideBy: 1 },
                1024: { items: 3, slideBy: 1 },
                1200: { items: 3, slideBy: 1 },
                1350: { items: 3, slideBy: 1 },
                1660: { items: 4, slideBy: 1 },
                1800: { items: 4, slideBy: 1 }
            }
        });
        $('.tvtabcategory-product-prev').click(function(e) {
            e.preventDefault();
            $('.tvtabcategory-all-product-content-box .tvtabcategory-all-product-slider .owl-nav .owl-prev').trigger('click');
        });
        $('.tvtabcategory-product-next').click(function(e) {
            e.preventDefault();
            $('.tvtabcategory-all-product-content-box .tvtabcategory-all-product-slider .owl-nav .owl-next').trigger('click');
        });
        // $(".tvtabcategory-all-product-content-box .tvtabcategory-all-product-info-box-dec").balance();
        //if($('.tvcmstabcategory-product-slider-main-title-wrapper').find('.tvtabcategory-product-pagination').length < 1){
            $(".tvcmstabcategory-product-slider .tvtabcategory-product-pagination").insertAfter('.tvcmstabcategory-product-slider .tvcmsmain-title-wrapper')
        
    }
});